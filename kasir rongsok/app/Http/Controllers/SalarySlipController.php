<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BusinessSetting;
use App\Models\PaymentMethod;
use App\Models\SalarySlip;
use App\Models\User;
use App\Services\FormatHelper;
use App\Services\WhatsAppGatewayService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SalarySlipController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->query('period_month');
        $search = $request->query('search');

        $query = SalarySlip::with(['employeeUser', 'creator', 'paymentMethod'])
            ->when($period, function ($q) use ($period) {
                $q->where('period_month', $period);
            })
            ->when($search, function ($q) use ($search) {
                $q->where('employee_name', 'LIKE', "%{$search}%")
                    ->orWhere('slip_number', 'LIKE', "%{$search}%");
            })
            ->latest('payment_date');

        $slips = $query->paginate(15)->withQueryString();
        $totalPayroll = (float) SalarySlip::when($period, fn($q) => $q->where('period_month', $period))->sum('net_salary');

        // Available periods for filter
        $periods = SalarySlip::select('period_month')->distinct()->orderBy('period_month', 'desc')->pluck('period_month');

        return view('salary_slips.index', compact('slips', 'totalPayroll', 'periods', 'period', 'search'));
    }

    public function create(): View
    {
        $users = User::where('is_active', true)->orderBy('name')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        $currentMonth = Carbon::now()->isoFormat('MMMM Y');

        return view('salary_slips.create', compact('users', 'paymentMethods', 'currentMonth'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_name' => 'required|string|max:150',
            'user_id' => 'nullable|exists:users,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'period_month' => 'required|string|max:50',
            'payment_date' => 'required|date',
            'basic_salary' => 'required|numeric|min:0',
            'allowance_amount' => 'nullable|numeric|min:0',
            'bonus_amount' => 'nullable|numeric|min:0',
            'debt_deduction' => 'nullable|numeric|min:0',
            'other_deduction' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $basic = (float) $validated['basic_salary'];
        $allowance = (float) ($validated['allowance_amount'] ?? 0);
        $bonus = (float) ($validated['bonus_amount'] ?? 0);
        $debt = (float) ($validated['debt_deduction'] ?? 0);
        $other = (float) ($validated['other_deduction'] ?? 0);

        $totalIncome = $basic + $allowance + $bonus;
        $totalDeduction = $debt + $other;
        $netSalary = max(0, $totalIncome - $totalDeduction);

        $date = Carbon::parse($validated['payment_date']);
        $prefix = 'SLIP-' . $date->format('ymd') . '-';
        $latest = SalarySlip::where('slip_number', 'LIKE', $prefix . '%')->orderBy('id', 'desc')->value('slip_number');
        $num = $latest ? ((int) substr($latest, strlen($prefix)) + 1) : 1;
        $slipNumber = $prefix . str_pad((string) $num, 3, '0', STR_PAD_LEFT);

        $slip = SalarySlip::create([
            'slip_number' => $slipNumber,
            'employee_name' => $validated['employee_name'],
            'user_id' => $validated['user_id'] ?? null,
            'created_by' => Auth::id(),
            'payment_method_id' => $validated['payment_method_id'] ?? null,
            'period_month' => $validated['period_month'],
            'payment_date' => $date,
            'basic_salary' => $basic,
            'allowance_amount' => $allowance,
            'bonus_amount' => $bonus,
            'total_income' => $totalIncome,
            'debt_deduction' => $debt,
            'other_deduction' => $other,
            'total_deduction' => $totalDeduction,
            'net_salary' => $netSalary,
            'status' => 'paid',
            'notes' => $validated['notes'] ?? null,
        ]);

        ActivityLog::record(
            action: 'salary_slip_created',
            subject: $slip,
            description: "Membuat slip gaji {$slip->slip_number} untuk {$slip->employee_name} senilai Rp " . number_format($netSalary, 0, ',', '.')
        );

        return redirect()->route('salary-slips.show', $slip)->with('success', "Slip gaji {$slip->slip_number} berhasil dibuat!");
    }

    public function show(SalarySlip $salarySlip): View
    {
        $salarySlip->load(['employeeUser', 'creator', 'paymentMethod']);
        $settings = BusinessSetting::pluck('value', 'key')->all();

        return view('salary_slips.show', compact('salarySlip', 'settings'));
    }

    public function print(SalarySlip $salarySlip): View
    {
        $salarySlip->load(['employeeUser', 'creator', 'paymentMethod']);
        $settings = BusinessSetting::pluck('value', 'key')->all();

        return view('salary_slips.print', compact('salarySlip', 'settings'));
    }

    /**
     * Tampilkan Preview / Download PDF Slip Gaji (Task 14).
     */
    public function downloadPdf(Request $request, SalarySlip $salarySlip)
    {
        $salarySlip->load(['employeeUser', 'creator', 'paymentMethod']);
        $settings = BusinessSetting::pluck('value', 'key')->all();

        $pdf = Pdf::loadView('salary_slips.pdf', compact('salarySlip', 'settings'))
            ->setPaper('a5', 'landscape');

        $filename = "Slip_Gaji_{$salarySlip->slip_number}_{$salarySlip->employee_name}.pdf";

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Preview single Salary Slip WhatsApp message and PDF metadata.
     */
    public function previewWa(SalarySlip $salarySlip): JsonResponse
    {
        $salarySlip->load(['employeeUser', 'creator', 'paymentMethod']);
        $settings = BusinessSetting::pluck('value', 'key')->all();

        $phone = $salarySlip->employeeUser?->phone ?? $salarySlip->employee_phone ?? '';
        $companyName = $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur';

        $msg = "*SLIP GAJI KARYAWAN RESMI*\n";
        $msg .= "*{$companyName}*\n";
        $msg .= "----------------------------------------\n";
        $msg .= "🔖 No. Slip : *{$salarySlip->slip_number}*\n";
        $msg .= "👤 Karyawan : *{$salarySlip->employee_name}*\n";
        $msg .= "🗓️ Periode  : *{$salarySlip->period_month}*\n";
        $msg .= "📅 Tanggal  : " . $salarySlip->payment_date->format('d/m/Y') . "\n";
        $msg .= "----------------------------------------\n";
        $msg .= "💵 *PENGHASILAN:*\n";
        $msg .= "  • Gaji Pokok  : " . FormatHelper::rupiah($salarySlip->basic_salary) . "\n";
        if ($salarySlip->allowance_amount > 0) {
            $msg .= "  • Tunjangan   : " . FormatHelper::rupiah($salarySlip->allowance_amount) . "\n";
        }
        if ($salarySlip->bonus_amount > 0) {
            $msg .= "  • Bonus/Lembur: " . FormatHelper::rupiah($salarySlip->bonus_amount) . "\n";
        }
        $msg .= "  *Total Kotor  : " . FormatHelper::rupiah($salarySlip->total_income) . "*\n";
        if ($salarySlip->total_deduction > 0) {
            $msg .= "✂️ *POTONGAN:*\n";
            if ($salarySlip->debt_deduction > 0) {
                $msg .= "  • Potong Kasbon: -" . FormatHelper::rupiah($salarySlip->debt_deduction) . "\n";
            }
            if ($salarySlip->other_deduction > 0) {
                $msg .= "  • Potongan Lain: -" . FormatHelper::rupiah($salarySlip->other_deduction) . "\n";
            }
            $msg .= "  *Total Potongan: -" . FormatHelper::rupiah($salarySlip->total_deduction) . "*\n";
        }
        $msg .= "----------------------------------------\n";
        $msg .= "💰 *GAJI BERSIH DITERIMA:*\n";
        $msg .= "👉 *" . FormatHelper::rupiah($salarySlip->net_salary) . "*\n";
        $msg .= "Metode Bayar: " . ($salarySlip->paymentMethod?->name ?? 'Kas Tunai') . "\n";
        $msg .= "----------------------------------------\n";
        $msg .= "📄 *Terlampir berkas dokumen PDF resmi Slip Gaji Karyawan*.\n";
        $msg .= "Terima kasih atas dedikasi dan kerja keras Anda! 🙏";

        $pdfFilename = "Slip_Gaji_{$salarySlip->slip_number}.pdf";
        $pdfUrl = route('salary-slips.pdf', $salarySlip);

        return response()->json([
            'success' => true,
            'phone' => $phone,
            'message' => $msg,
            'pdf_filename' => $pdfFilename,
            'pdf_url' => $pdfUrl,
            'file_description' => "Dokumen PDF Resmi Slip Gaji ({$salarySlip->employee_name})",
        ]);
    }

    /**
     * Send Salary Slip summary to WhatsApp (with PDF attachment).
     */
    public function sendWa(Request $request, SalarySlip $salarySlip)
    {
        $salarySlip->load(['employeeUser', 'creator', 'paymentMethod']);
        $settings = BusinessSetting::pluck('value', 'key')->all();

        $phone = $request->input('phone', $salarySlip->employeeUser?->phone ?? $salarySlip->employee_phone);
        $sendPdf = $request->boolean('send_pdf', true);
        $caption = $request->input('caption') ?: $request->input('message');
        $companyName = $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur';

        if (empty($phone)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Nomor WhatsApp karyawan wajib diisi.'], 422);
            }
            return back()->with('error', 'Nomor WhatsApp karyawan wajib diisi.');
        }

        if (empty($caption)) {
            $caption = "*SLIP GAJI KARYAWAN RESMI*\n";
            $caption .= "*{$companyName}*\n";
            $caption .= "🔖 No. Slip : *{$salarySlip->slip_number}*\n";
            $caption .= "👤 Karyawan : *{$salarySlip->employee_name}*\n";
            $caption .= "💰 Gaji Bersih: *" . FormatHelper::rupiah($salarySlip->net_salary) . "*\n";
            $caption .= "📄 Terlampir berkas dokumen PDF resmi Slip Gaji.";
        }

        $waService = app(WhatsAppGatewayService::class);
        $filename = "Slip_Gaji_{$salarySlip->slip_number}.pdf";

        if ($sendPdf) {
            $pdf = Pdf::loadView('salary_slips.pdf', compact('salarySlip', 'settings'))
                ->setPaper('a4', 'portrait');
            $fileBase64 = base64_encode($pdf->output());

            $res = $waService->sendFile($phone, $fileBase64, $filename, $caption);
        } else {
            $res = $waService->sendMessage($phone, $caption);
        }

        if ($request->wantsJson()) {
            return response()->json($res);
        }

        return back()->with($res['success'] ? 'success' : 'error', $res['message'] ?? 'Pesan terkirim');
    }

    /**
     * Export Batch Salary Slips as PDF (A4 Portrait).
     */
    public function batchPdf(Request $request)
    {
        $period = $request->query('period_month');
        $search = $request->query('search');

        $slips = SalarySlip::with(['employeeUser', 'creator', 'paymentMethod'])
            ->when($period, fn($q) => $q->where('period_month', $period))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('employee_name', 'LIKE', "%{$search}%")
                        ->orWhere('slip_number', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('payment_date', 'desc')
            ->get();

        $totalNetSalary = (float) $slips->sum('net_salary');
        $settings = BusinessSetting::pluck('value', 'key')->all();

        $pdf = Pdf::loadView('salary_slips.batch_pdf', compact('slips', 'settings', 'period', 'totalNetSalary'))
            ->setPaper('a4', 'portrait');

        $filename = 'Rekap_Penggajian_Karyawan_' . ($period ?: date('Y-m')) . '.pdf';

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Preview Global Salary Slips WhatsApp message and batch PDF metadata.
     */
    public function previewWaGlobal(Request $request): JsonResponse
    {
        $period = $request->query('period_month');
        $search = $request->query('search');

        $slips = SalarySlip::when($period, fn($q) => $q->where('period_month', $period))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('employee_name', 'LIKE', "%{$search}%")
                        ->orWhere('slip_number', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('payment_date', 'desc')
            ->get();

        $count = $slips->count();
        $totalNetSalary = (float) $slips->sum('net_salary');
        $companyName = BusinessSetting::get('company_name', 'UD. BERKAH RONGSOK MAKMUR');

        $msg = "*REKAPITULASI PENGGAJIAN KARYAWAN (PAYROLL)*\n";
        $msg .= "*{$companyName}*\n";
        $msg .= "Periode: " . ($period ?: 'Semua Periode') . " | Tanggal: " . date('d/m/Y') . "\n\n";
        $msg .= "🔖 Total Karyawan Dibayar: *{$count} Slip Gaji*\n";
        $msg .= "💰 Total Beban Gaji Bersih: *" . FormatHelper::rupiah($totalNetSalary) . "*\n\n";

        if ($count > 0) {
            $msg .= "📋 *Rincian Karyawan:*\n";
            $previewList = $slips->take(6);
            foreach ($previewList as $idx => $s) {
                $num = $idx + 1;
                $gaji = FormatHelper::rupiah($s->net_salary);
                $msg .= "{$num}. {$s->employee_name} ({$s->slip_number}) - {$gaji}\n";
            }
            if ($count > 6) {
                $r = $count - 6;
                $msg .= "... dan {$r} slip lainnya tercantum lengkap dalam berkas PDF.\n";
            }
            $msg .= "\n";
        }

        $msg .= "📄 *Terlampir berkas PDF dokumen rekapitulasi penggajian karyawan*.\n";
        $msg .= "Diterbitkan resmi oleh Sistem Kasir Rongsok.";

        $pdfFilename = "Rekap_Gaji_Karyawan_" . ($period ?: date('Y-m')) . ".pdf";
        $pdfUrl = route('salary-slips.batch-pdf', $request->query());

        return response()->json([
            'success' => true,
            'count' => $count,
            'total_amount' => $totalNetSalary,
            'message' => $msg,
            'pdf_filename' => $pdfFilename,
            'pdf_url' => $pdfUrl,
            'file_description' => "Dokumen PDF Rekapitulasi Gaji Karyawan ({$count} Slip)",
        ]);
    }

    /**
     * Send Global Salary Slips Recap via WhatsApp (with PDF attachment).
     */
    public function sendWaGlobal(Request $request)
    {
        $period = $request->input('period_month');
        $search = $request->input('search');
        $phone = $request->input('phone');
        $sendPdf = $request->boolean('send_pdf', true);
        $caption = $request->input('caption') ?: $request->input('message');

        if (empty($phone)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Nomor WhatsApp tujuan wajib diisi.'], 422);
            }
            return back()->with('error', 'Nomor WhatsApp tujuan wajib diisi.');
        }

        $slips = SalarySlip::with(['employeeUser', 'creator', 'paymentMethod'])
            ->when($period, fn($q) => $q->where('period_month', $period))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('employee_name', 'LIKE', "%{$search}%")
                        ->orWhere('slip_number', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('payment_date', 'desc')
            ->get();

        $totalNetSalary = (float) $slips->sum('net_salary');
        $settings = BusinessSetting::pluck('value', 'key')->all();
        $companyName = $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR';

        if (empty($caption)) {
            $caption = "*REKAPITULASI PENGGAJIAN KARYAWAN (PAYROLL)*\n";
            $caption .= "*{$companyName}*\n";
            $caption .= "🔖 Total: {$slips->count()} Slip | Total Gaji: " . FormatHelper::rupiah($totalNetSalary) . "\n";
            $caption .= "📄 Terlampir berkas PDF dokumen rekapitulasi penggajian karyawan.";
        }

        $waService = app(WhatsAppGatewayService::class);
        $filename = "Rekap_Gaji_Karyawan_" . ($period ?: date('Y-m')) . ".pdf";

        if ($sendPdf) {
            $pdf = Pdf::loadView('salary_slips.batch_pdf', compact('slips', 'settings', 'period', 'totalNetSalary'))
                ->setPaper('a4', 'portrait');
            $fileBase64 = base64_encode($pdf->output());

            $res = $waService->sendFile($phone, $fileBase64, $filename, $caption);
        } else {
            $res = $waService->sendMessage($phone, $caption);
        }

        if ($request->wantsJson()) {
            return response()->json($res);
        }

        return back()->with($res['success'] ? 'success' : 'error', $res['message'] ?? 'Status terkirim.');
    }

    /**
     * Export Salary Slips to Excel (Task 18).
     */
    public function exportExcel(Request $request)
    {
        $period = $request->query('period_month');
        $slips = SalarySlip::with(['employeeUser', 'creator', 'paymentMethod'])
            ->when($period, fn($q) => $q->where('period_month', $period))
            ->orderBy('payment_date', 'desc')
            ->get();

        $filename = 'Rekap_Gaji_' . date('Ymd_His') . '.xls';

        return response()->view('salary_slips.excel', compact('slips', 'period'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    public function destroy(SalarySlip $salarySlip): RedirectResponse
    {
        $num = $salarySlip->slip_number;
        $salarySlip->delete();

        ActivityLog::record(
            action: 'salary_slip_deleted',
            description: "Menghapus slip gaji {$num}"
        );

        return redirect()->route('salary-slips.index')->with('success', "Slip gaji {$num} berhasil dihapus.");
    }
}

