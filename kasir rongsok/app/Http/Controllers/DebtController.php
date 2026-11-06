<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\PaymentMethod;
use App\Models\Supplier;
use App\Services\DebtService;
use App\Services\FormatHelper;
use App\Services\WhatsAppGatewayService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DebtController extends Controller
{
    public function __construct(
        protected DebtService $debtService
    ) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $type = $request->query('borrower_type');
        $loanCategory = $request->query('loan_category');
        $search = $request->query('search');

        $query = Debt::with(['supplier', 'user', 'payments'])
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($type, function ($q) use ($type) {
                $q->where('borrower_type', $type);
            })
            ->when($loanCategory, function ($q) use ($loanCategory) {
                $q->where('loan_category', $loanCategory);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('borrower_name', 'LIKE', "%{$search}%")
                        ->orWhere('debt_number', 'LIKE', "%{$search}%")
                        ->orWhere('borrower_phone', 'LIKE', "%{$search}%");
                });
            })
            ->latest('date');

        $debts = $query->paginate(15)->withQueryString();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        // Summary KPI
        $totalInitial = (float) Debt::sum('initial_amount');
        $totalPaid = (float) Debt::sum('paid_amount');
        $totalRemaining = (float) Debt::sum('remaining_amount');

        $justCreatedDebt = null;
        if (session('just_created_debt_id')) {
            $justCreatedDebt = Debt::with(['supplier', 'user'])->find(session('just_created_debt_id'));
        }

        return view('debts.index', compact('debts', 'suppliers', 'paymentMethods', 'status', 'type', 'loanCategory', 'search', 'totalInitial', 'totalPaid', 'totalRemaining', 'justCreatedDebt'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'borrower_name' => 'required|string|max:150',
            'borrower_phone' => 'nullable|string|max:30',
            'borrower_type' => 'required|in:supplier,karyawan,lainnya',
            'loan_category' => 'nullable|in:modal,hutang,komitmen',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:60',
            'bank_account_name' => 'nullable|string|max:150',
            'save_to_supplier_bank' => 'nullable|boolean',
            'date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:date',
            'initial_amount' => 'required|numeric|gt:0',
            'guarantee_info' => 'nullable|string|max:255',
            'witness_name' => 'nullable|string|max:150',
            'agreement_notes' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $debt = $this->debtService->createDebt($validated, Auth::user());
            $categoryLabel = $debt->getCategoryLabel();
            return redirect()->route('debts.index')
                ->with('success', "Transaksi {$categoryLabel} {$debt->debt_number} untuk {$debt->borrower_name} berhasil dicatat.")
                ->with('just_created_debt_id', $debt->id);
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(Debt $debt): View
    {
        $debt->load(['supplier', 'user', 'payments.user', 'payments.paymentMethod', 'payments.purchaseTransaction']);
        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        return view('debts.show', compact('debt', 'paymentMethods'));
    }

    public function pay(Request $request, Debt $debt): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|gt:0',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $payment = $this->debtService->recordPayment(
                debt: $debt,
                amount: (float) $validated['amount'],
                paymentMethodId: $validated['payment_method_id'] ?? null,
                notes: $validated['notes'] ?? 'Pembayaran cicilan / pelunasan langsung',
                user: Auth::user()
            );

            return redirect()->route('debts.print-payment', $payment)
                ->with('success', "Pembayaran {$payment->payment_number} berhasil dicatat!");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function printPayment(Request $request, DebtPayment $payment): View
    {
        $payment->load(['debt.supplier', 'user', 'paymentMethod']);
        $format = $request->query('format', BusinessSetting::get('default_printer_type', 'thermal_58'));
        $settings = BusinessSetting::pluck('value', 'key')->all();

        return view('debts.print_payment', compact('payment', 'format', 'settings'));
    }

    /**
     * Printable view of Debt / Loan Agreement Letter (Surat Perjanjian Hutang Piutang / Modal - Task 17).
     */
    public function agreement(Debt $debt): View
    {
        $debt->load(['supplier', 'user']);
        $settings = BusinessSetting::pluck('value', 'key')->all();
        $owner = \App\Models\User::where('role', \App\Models\User::ROLE_SUPER_ADMIN)->first();
        $ownerName = $settings['owner_name'] ?? ($owner?->name ?? 'Bapak Hartono (Owner)');

        return view('debts.agreement', compact('debt', 'settings', 'ownerName'));
    }

    /**
     * Tampilkan Preview / Download PDF Surat Perjanjian Hutang Piutang (Task 17).
     */
    public function agreementPdf(Request $request, Debt $debt)
    {
        $debt->load(['supplier', 'user']);
        $settings = BusinessSetting::pluck('value', 'key')->all();
        $owner = \App\Models\User::where('role', \App\Models\User::ROLE_SUPER_ADMIN)->first();
        $ownerName = $settings['owner_name'] ?? ($owner?->name ?? 'Bapak Hartono (Owner)');

        $pdf = Pdf::loadView('debts.agreement_pdf', compact('debt', 'settings', 'ownerName'))
            ->setPaper('a4', 'portrait');

        $cleanNum = str_replace(['/', ' '], '_', $debt->agreement_number ?: $debt->debt_number);
        $filename = "Surat_Perjanjian_{$cleanNum}.pdf";

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Preview Debt Agreement WhatsApp message and PDF metadata.
     */
    public function previewAgreementWa(Debt $debt): JsonResponse
    {
        $debt->load(['supplier', 'user']);
        $settings = BusinessSetting::pluck('value', 'key')->all();
        $owner = \App\Models\User::where('role', \App\Models\User::ROLE_SUPER_ADMIN)->first();
        $ownerName = $settings['owner_name'] ?? ($owner?->name ?? 'Bapak Hartono (Owner)');

        $companyName = $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur';
        $phone = $debt->borrower_phone ?? '';

        $title = $debt->getAgreementTitle();
        $label = $debt->getCategoryLabel();

        $msg = "*{$title}*\n";
        $msg .= "*{$companyName}*\n";
        $msg .= "----------------------------------------\n";
        $msg .= "🔖 No. Perjanjian: *" . ($debt->agreement_number ?: ('SPH-SBL-' . $debt->debt_number)) . "*\n";
        $msg .= "🔖 No. Referensi : {$debt->debt_number}\n";
        $msg .= "🏷️ Kategori     : *{$label}*\n";
        $msg .= "🗓️ Tanggal       : " . $debt->date->format('d/m/Y') . "\n";
        $msg .= "👤 Pihak Pertama : *{$ownerName}* (Owner)\n";
        $msg .= "👤 Pihak Kedua   : *{$debt->borrower_name}* (Penerima)\n";
        if ($debt->borrower_phone) {
            $msg .= "📞 HP Penerima   : {$debt->borrower_phone}\n";
        }
        $msg .= "----------------------------------------\n";
        $msg .= "💰 *NOMINAL " . strtoupper($label) . ":*\n";
        $msg .= "👉 *" . FormatHelper::rupiah($debt->initial_amount) . "*\n";
        $msg .= "👉 _(" . FormatHelper::terbilang($debt->initial_amount) . " Rupiah)_\n";
        if ($debt->due_date) {
            $msg .= "⏳ Batas Waktu   : " . $debt->due_date->format('d/m/Y') . "\n";
        }
        if ($debt->guarantee_info) {
            $msg .= "🔒 Jaminan/Agunan: {$debt->guarantee_info}\n";
        }
        $msg .= "----------------------------------------\n";
        $msg .= "📄 *Terlampir berkas PDF resmi {$title} (2 Lembar: Asli Owner & Salinan Peminjam)*.\n";
        $commodityTerm = BusinessSetting::commodityTerm();
        $msg .= "Dokumen ini dicatat sah pada sistem kasir depo {$commodityTerm}.";

        $cleanNum = str_replace(['/', ' '], '_', $debt->agreement_number ?: $debt->debt_number);
        $pdfFilename = "SPH_{$cleanNum}.pdf";
        $pdfUrl = route('debts.agreement-pdf', $debt);

        return response()->json([
            'success' => true,
            'phone' => $phone,
            'message' => $msg,
            'pdf_filename' => $pdfFilename,
            'pdf_url' => $pdfUrl,
            'file_description' => "Dokumen PDF {$title} ({$debt->debt_number})",
        ]);
    }

    /**
     * Send Debt Agreement summary to WhatsApp (with PDF attachment).
     */
    public function agreementWa(Request $request, Debt $debt)
    {
        $debt->load(['supplier', 'user']);
        $settings = BusinessSetting::pluck('value', 'key')->all();
        $owner = \App\Models\User::where('role', \App\Models\User::ROLE_SUPER_ADMIN)->first();
        $ownerName = $settings['owner_name'] ?? ($owner?->name ?? 'Bapak Hartono (Owner)');

        $phone = $request->input('phone', $debt->borrower_phone);
        $sendPdf = $request->boolean('send_pdf', true);
        $caption = $request->input('caption') ?: $request->input('message');
        $companyName = $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur';

        if (empty($phone)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Nomor WhatsApp peminjam wajib diisi.'], 422);
            }
            return back()->with('error', 'Nomor WhatsApp peminjam wajib diisi.');
        }

        if (empty($caption)) {
            $title = $debt->getAgreementTitle();
            $label = $debt->getCategoryLabel();
            $caption = "*{$title}*\n";
            $caption .= "*{$companyName}*\n";
            $caption .= "----------------------------------------\n";
            $caption .= "🔖 No. Perjanjian: *" . ($debt->agreement_number ?: ('SPH-SBL-' . $debt->debt_number)) . "*\n";
            $caption .= "🏷️ Kategori      : *{$label}*\n";
            $caption .= "👤 Owner Depo    : *{$ownerName}*\n";
            $caption .= "👤 Penerima Dana : *{$debt->borrower_name}*\n";
            $caption .= "💰 Nominal Dana  : *" . FormatHelper::rupiah($debt->initial_amount) . "*\n";
            $caption .= "📄 Terlampir berkas resmi {$title} (A4 - 2 Lembar Rangkap).";
        }

        $waService = app(WhatsAppGatewayService::class);
        $cleanNum = str_replace(['/', ' '], '_', $debt->agreement_number ?: $debt->debt_number);
        $filename = "SPH_{$cleanNum}.pdf";

        if ($sendPdf) {
            $pdf = Pdf::loadView('debts.agreement_pdf', compact('debt', 'settings', 'ownerName'))
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
     * Preview / Unduh PDF Riwayat & Saldo Hutang Piutang sesuai status saat ini.
     */
    public function pdf(Request $request, Debt $debt)
    {
        $debt->load(['supplier', 'user']);
        $payments = $debt->payments()
            ->with(['paymentMethod', 'user', 'purchaseTransaction'])
            ->oldest('payment_date')
            ->get();
        $settings = BusinessSetting::pluck('value', 'key')->all();
        $owner = \App\Models\User::where('role', \App\Models\User::ROLE_SUPER_ADMIN)->first();
        $ownerName = $settings['owner_name'] ?? ($owner?->name ?? 'Bapak Hartono (Owner)');

        $pdf = Pdf::loadView('debts.history_pdf', compact('debt', 'payments', 'settings', 'ownerName'))
            ->setPaper('a4', 'portrait');

        $borrowerClean = preg_replace('/[^a-zA-Z0-9_\-]/', '_', trim($debt->borrower_name ?: 'Peminjam'));
        $borrowerClean = trim(preg_replace('/_+/', '_', $borrowerClean), '_');
        $cleanNum = str_replace(['/', ' '], '_', $debt->debt_number);
        $filename = "Riwayat_Hutang_{$borrowerClean}_{$cleanNum}.pdf";

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Preview Debt History & Balance WhatsApp message and PDF metadata.
     */
    public function previewWa(Debt $debt): JsonResponse
    {
        $debt->load(['supplier', 'user']);
        $settings = BusinessSetting::pluck('value', 'key')->all();
        $owner = \App\Models\User::where('role', \App\Models\User::ROLE_SUPER_ADMIN)->first();
        $ownerName = $settings['owner_name'] ?? ($owner?->name ?? 'Bapak Hartono (Owner)');

        $companyName = $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur';
        $phone = $debt->borrower_phone ?? '';

        $statusLabel = match ($debt->status) {
            'paid' => 'LUNAS (LUNAS PENUH)',
            'partial', 'partially_paid' => 'SEBAGIAN TERBAYAR (TERCICIL)',
            default => 'BELUM LUNAS',
        };

        $categoryLabel = $debt->getCategoryLabel();
        $paymentCount = $debt->payments()->count();

        $msg = "*RIWAYAT & SALDO " . strtoupper($categoryLabel) . "*\n";
        $msg .= "*{$companyName}*\n";
        $msg .= "----------------------------------------\n";
        $msg .= "🔖 No. Hutang/Pinjaman: *{$debt->debt_number}*\n";
        $msg .= "🏷️ Kategori           : *{$categoryLabel}*\n";
        $msg .= "🗓️ Tanggal Pinjam      : " . $debt->date->format('d/m/Y') . "\n";
        $msg .= "👤 Peminjam / Debitur : *{$debt->borrower_name}*\n";
        if ($debt->due_date) {
            $msg .= "⏳ Jatuh Tempo         : " . $debt->due_date->format('d/m/Y') . "\n";
        }
        $msg .= "----------------------------------------\n";
        $msg .= "💰 Pinjaman Awal      : *" . FormatHelper::rupiah($debt->initial_amount) . "*\n";
        $msg .= "💵 Total Diangsur     : *" . FormatHelper::rupiah($debt->paid_amount) . "*\n";
        $msg .= "⚠️ Sisa Saldo Hutang   : *" . FormatHelper::rupiah($debt->remaining_amount) . "*\n";
        $msg .= "📌 Status Saat Ini    : *{$statusLabel}*\n";
        $msg .= "----------------------------------------\n";
        $msg .= "📋 Riwayat Cicilan    : {$paymentCount} kali pembayaran tercatat.\n";
        $msg .= "📄 *Terlampir berkas PDF resmi Kartu Riwayat & Posisi Saldo Hutang Piutang*.\n";
        $commodityTerm = BusinessSetting::commodityTerm();
        $msg .= "Diterbitkan resmi oleh Sistem Kasir {$commodityTerm}.";

        $cleanNum = str_replace(['/', ' '], '_', $debt->debt_number);
        $pdfFilename = "Riwayat_Hutang_{$cleanNum}.pdf";
        $pdfUrl = route('debts.pdf', $debt);

        return response()->json([
            'success' => true,
            'phone' => $phone,
            'message' => $msg,
            'pdf_filename' => $pdfFilename,
            'pdf_url' => $pdfUrl,
            'file_description' => "Dokumen PDF Riwayat & Saldo Hutang ({$debt->debt_number})",
        ]);
    }

    /**
     * Send Debt History & Balance PDF to WhatsApp.
     */
    public function sendWa(Request $request, Debt $debt)
    {
        $debt->load(['supplier', 'user']);
        $settings = BusinessSetting::pluck('value', 'key')->all();
        $owner = \App\Models\User::where('role', \App\Models\User::ROLE_SUPER_ADMIN)->first();
        $ownerName = $settings['owner_name'] ?? ($owner?->name ?? 'Bapak Hartono (Owner)');

        $phone = $request->input('phone', $debt->borrower_phone);
        $sendPdf = $request->boolean('send_pdf', true);
        $caption = $request->input('caption') ?: $request->input('message');
        $companyName = $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur';

        if (empty($phone)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Nomor WhatsApp peminjam wajib diisi.'], 422);
            }
            return back()->with('error', 'Nomor WhatsApp peminjam wajib diisi.');
        }

        if (empty($caption)) {
            $statusLabel = match ($debt->status) {
                'paid' => 'LUNAS',
                'partial', 'partially_paid' => 'SEBAGIAN TERBAYAR',
                default => 'BELUM LUNAS',
            };
            $categoryLabel = $debt->getCategoryLabel();
            $caption = "*RIWAYAT & SALDO " . strtoupper($categoryLabel) . "*\n";
            $caption .= "*{$companyName}*\n";
            $caption .= "----------------------------------------\n";
            $caption .= "🔖 No. Hutang    : *{$debt->debt_number}*\n";
            $caption .= "👤 Peminjam      : *{$debt->borrower_name}*\n";
            $caption .= "💰 Pinjaman Awal : *" . FormatHelper::rupiah($debt->initial_amount) . "*\n";
            $caption .= "💵 Total Diangsur: *" . FormatHelper::rupiah($debt->paid_amount) . "*\n";
            $caption .= "⚠️ Sisa Saldo    : *" . FormatHelper::rupiah($debt->remaining_amount) . "*\n";
            $caption .= "📌 Status        : *{$statusLabel}*\n";
            $caption .= "📄 Terlampir berkas PDF resmi Kartu Riwayat & Saldo Hutang.";
        }

        $waService = app(WhatsAppGatewayService::class);
        $borrowerClean = preg_replace('/[^a-zA-Z0-9_\-]/', '_', trim($debt->borrower_name ?: 'Peminjam'));
        $borrowerClean = trim(preg_replace('/_+/', '_', $borrowerClean), '_');
        $cleanNum = str_replace(['/', ' '], '_', $debt->debt_number);
        $filename = "Riwayat_Hutang_{$borrowerClean}_{$cleanNum}.pdf";

        if ($sendPdf) {
            $payments = $debt->payments()
                ->with(['paymentMethod', 'user', 'purchaseTransaction'])
                ->oldest('payment_date')
                ->get();
            $pdf = Pdf::loadView('debts.history_pdf', compact('debt', 'payments', 'settings', 'ownerName'))
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
     * Export Batch Debts Recap as PDF (A4 Portrait).
     */
    public function batchPdf(Request $request)
    {
        $status = $request->query('status');
        $type = $request->query('borrower_type');
        $search = $request->query('search');

        $debts = Debt::with(['supplier', 'user', 'payments'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($type, fn($q) => $q->where('borrower_type', $type))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('borrower_name', 'LIKE', "%{$search}%")
                        ->orWhere('debt_number', 'LIKE', "%{$search}%")
                        ->orWhere('agreement_number', 'LIKE', "%{$search}%");
                });
            })
            ->latest('date')
            ->get();

        $totalAmount = (float) $debts->sum('amount');
        $totalPaid = (float) $debts->sum(fn($d) => $d->amount - $d->remaining_amount);
        $totalRemaining = (float) $debts->sum('remaining_amount');
        $settings = BusinessSetting::pluck('value', 'key')->all();

        $pdf = Pdf::loadView('debts.batch_pdf', compact('debts', 'settings', 'status', 'type', 'totalAmount', 'totalPaid', 'totalRemaining'))
            ->setPaper('a4', 'portrait');

        $filename = 'Rekap_Hutang_Modal_' . date('Ymd_His') . '.pdf';

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Preview Global Debts WhatsApp message and batch PDF metadata.
     */
    public function previewWaGlobal(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $type = $request->query('borrower_type');
        $search = $request->query('search');

        $debts = Debt::with(['supplier'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($type, fn($q) => $q->where('borrower_type', $type))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('borrower_name', 'LIKE', "%{$search}%")
                        ->orWhere('debt_number', 'LIKE', "%{$search}%");
                });
            })
            ->latest('date')
            ->get();

        $count = $debts->count();
        $totalAmount = (float) $debts->sum('amount');
        $totalPaid = (float) $debts->sum(fn($d) => $d->amount - $d->remaining_amount);
        $totalRemaining = (float) $debts->sum('remaining_amount');
        $companyName = BusinessSetting::get('company_name', 'UD. BERKAH RONGSOK MAKMUR');

        $msg = "*REKAPITULASI BUKU KASBON & PINJAMAN MODAL*\n";
        $msg .= "*{$companyName}*\n";
        $msg .= "Status: " . ($status ? strtoupper($status) : 'SEMUA') . " | Tanggal: " . date('d/m/Y') . "\n\n";
        $msg .= "🔖 Total Debitur     : *{$count} Pinjaman*\n";
        $msg .= "💰 Total Pinjaman    : *" . FormatHelper::rupiah($totalAmount) . "*\n";
        $msg .= "💵 Telah Diangsur    : *" . FormatHelper::rupiah($totalPaid) . "*\n";
        $msg .= "⏳ Sisa Piutang Aktif: *" . FormatHelper::rupiah($totalRemaining) . "*\n\n";

        if ($count > 0) {
            $msg .= "📋 *Debitur Kasbon:* \n";
            $previewList = $debts->take(6);
            foreach ($previewList as $idx => $d) {
                $num = $idx + 1;
                $rem = FormatHelper::rupiah($d->remaining_amount);
                $msg .= "{$num}. {$d->borrower_name} ({$d->debt_number}) - Sisa: {$rem}\n";
            }
            if ($count > 6) {
                $r = $count - 6;
                $msg .= "... dan {$r} pinjaman lainnya tercantum lengkap dalam berkas PDF.\n";
            }
            $msg .= "\n";
        }

        $msg .= "📄 *Terlampir berkas PDF dokumen Buku Kasbon & Rekap Pinjaman Modal*.\n";
        $commodityTerm = BusinessSetting::commodityTerm();
        $msg .= "Diterbitkan resmi oleh Sistem Kasir {$commodityTerm}.";

        $pdfFilename = "Rekap_Hutang_Modal_" . date('Ymd_His') . ".pdf";
        $pdfUrl = route('debts.batch-pdf', $request->query());

        return response()->json([
            'success' => true,
            'count' => $count,
            'total_amount' => $totalRemaining,
            'message' => $msg,
            'pdf_filename' => $pdfFilename,
            'pdf_url' => $pdfUrl,
            'file_description' => "Dokumen PDF Rekapitulasi Kasbon & Pinjaman ({$count} Data)",
        ]);
    }

    /**
     * Send Global Debts Recap via WhatsApp (with PDF attachment).
     */
    public function sendWaGlobal(Request $request)
    {
        $status = $request->input('status');
        $type = $request->input('borrower_type');
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

        $debts = Debt::with(['supplier', 'user', 'payments'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($type, fn($q) => $q->where('borrower_type', $type))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('borrower_name', 'LIKE', "%{$search}%")
                        ->orWhere('debt_number', 'LIKE', "%{$search}%");
                });
            })
            ->latest('date')
            ->get();

        $totalAmount = (float) $debts->sum('amount');
        $totalPaid = (float) $debts->sum(fn($d) => $d->amount - $d->remaining_amount);
        $totalRemaining = (float) $debts->sum('remaining_amount');
        $settings = BusinessSetting::pluck('value', 'key')->all();
        $companyName = $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR';

        if (empty($caption)) {
            $caption = "*REKAPITULASI BUKU KASBON & PINJAMAN MODAL*\n";
            $caption .= "*{$companyName}*\n";
            $caption .= "🔖 Total: {$debts->count()} Pinjaman | Sisa Piutang: " . FormatHelper::rupiah($totalRemaining) . "\n";
            $caption .= "📄 Terlampir berkas PDF dokumen Buku Kasbon & Rekap Pinjaman Modal.";
        }

        $waService = app(WhatsAppGatewayService::class);
        $filename = "Rekap_Hutang_Modal_" . date('Ymd_His') . ".pdf";

        if ($sendPdf) {
            $pdf = Pdf::loadView('debts.batch_pdf', compact('debts', 'settings', 'status', 'type', 'totalAmount', 'totalPaid', 'totalRemaining'))
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
     * Export Debts to Excel (Task 18).
     */
    public function exportExcel(Request $request)
    {
        $status = $request->query('status');
        $type = $request->query('borrower_type');

        $debts = Debt::with(['supplier', 'user', 'payments'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($type, fn($q) => $q->where('borrower_type', $type))
            ->latest('date')
            ->get();

        $filename = 'Rekap_Hutang_Piutang_' . date('Ymd_His') . '.xls';

        return response()->view('debts.excel', compact('debts'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}

