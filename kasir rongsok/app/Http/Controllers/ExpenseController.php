<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Models\ActivityLog;
use App\Models\BusinessSetting;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Services\FormatHelper;
use App\Services\WhatsAppGatewayService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $categoryId = $request->query('category_id');
        $search = $request->query('search');
        $allDates = $request->query('all_dates') == '1';

        // Default to today (tanggal saat itu) so historical expenses are not accumulated unless requested
        if ($allDates) {
            $startDate = null;
            $endDate = null;
            $selectedMonth = null;
            $selectedYear = null;
        } else {
            [$startDate, $endDate, $selectedMonth, $selectedYear] = FormatHelper::resolveDateFilter(
                $request,
                Carbon::today()->toDateString(),
                Carbon::today()->toDateString()
            );
        }

        $query = Expense::with(['category', 'user', 'paymentMethod'])
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->where('expense_category_id', $categoryId);
            })
            ->when($startDate, function ($q) use ($startDate) {
                $q->whereDate('expense_date', '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                $q->whereDate('expense_date', '<=', $endDate);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('description', 'LIKE', "%{$search}%")
                        ->orWhere('recipient', 'LIKE', "%{$search}%")
                        ->orWhere('expense_number', 'LIKE', "%{$search}%");
                });
            })
            ->latest('expense_date');

        $expenses = $query->paginate(15)->withQueryString();
        $categories = ExpenseCategory::orderBy('name')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        $totalAmount = (float) Expense::when($startDate, fn($q) => $q->whereDate('expense_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('expense_date', '<=', $endDate))
            ->when($categoryId, fn($q) => $q->where('expense_category_id', $categoryId))
            ->sum('amount');

        return view('expenses.index', compact('expenses', 'categories', 'paymentMethods', 'categoryId', 'startDate', 'endDate', 'search', 'totalAmount', 'allDates', 'selectedMonth', 'selectedYear'));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (!empty($request->input('new_category_name'))) {
            $cat = ExpenseCategory::firstOrCreate([
                'name' => trim($request->input('new_category_name')),
            ]);
            $data['expense_category_id'] = $cat->id;
        }

        unset($data['new_category_name']);

        $date = Carbon::parse($data['expense_date']);

        $prefix = 'PPJ-EXP-' . $date->format('ymd') . '-';
        $latest = Expense::where('expense_number', 'LIKE', $prefix . '%')
            ->orWhere('expense_number', 'LIKE', 'EXP-' . $date->format('ymd') . '-%')
            ->orderBy('id', 'desc')
            ->value('expense_number');

        $num = $latest ? ((int) substr($latest, strrpos($latest, '-') + 1) + 1) : 1;
        $data['expense_number'] = $prefix . str_pad((string) $num, 4, '0', STR_PAD_LEFT);
        $data['user_id'] = Auth::id();

        $expense = Expense::create($data);

        ActivityLog::record(
            action: 'expense_created',
            subject: $expense,
            description: "Mencatat pengeluaran operasional: {$expense->description} senilai Rp " . number_format($expense->amount, 0, ',', '.')
        );

        return back()->with('success', "Pengeluaran {$expense->expense_number} berhasil dicatat.");
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $desc = $expense->description;
        $expense->delete();

        ActivityLog::record(
            action: 'expense_deleted',
            description: "Menghapus pengeluaran: {$desc}"
        );

        return back()->with('success', 'Pengeluaran berhasil dihapus.');
    }

    /**
     * Store new expense category (Task 12).
     */
    public function storeCategory(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:expense_categories,name',
        ], [
            'name.required' => 'Nama kategori pengeluaran wajib diisi.',
            'name.unique' => 'Kategori dengan nama tersebut sudah ada.',
        ]);

        $cat = ExpenseCategory::create(['name' => trim($request->input('name'))]);

        return back()->with('success', "Kategori '{$cat->name}' berhasil ditambahkan ke daftar.");
    }

    /**
     * Delete expense category (Task 12).
     */
    public function destroyCategory(ExpenseCategory $category): RedirectResponse
    {
        $count = $category->expenses()->count();
        if ($count > 0) {
            return back()->with('error', "Kategori '{$category->name}' tidak dapat dihapus karena sedang digunakan oleh {$count} transaksi pengeluaran.");
        }

        $name = $category->name;
        $category->delete();

        return back()->with('success', "Kategori '{$name}' berhasil dihapus dari dropdown.");
    }

    /**
     * Print Preview for Operational Expenses (In-app HTML preview matching PDF).
     */
    public function print(Request $request): View
    {
        $categoryId = $request->query('category_id');
        $search = $request->query('search');
        [$startDate, $endDate] = FormatHelper::resolveDateFilter($request);

        $expenses = Expense::with(['category', 'user', 'paymentMethod'])
            ->when($categoryId, fn($q) => $q->where('expense_category_id', $categoryId))
            ->when($startDate, fn($q) => $q->whereDate('expense_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('expense_date', '<=', $endDate))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('description', 'LIKE', "%{$search}%")
                        ->orWhere('recipient', 'LIKE', "%{$search}%")
                        ->orWhere('expense_number', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('expense_date', 'asc')
            ->get();

        $totalAmount = (float) $expenses->sum('amount');
        $settings = BusinessSetting::pluck('value', 'key')->all();

        return view('expenses.print', compact('expenses', 'totalAmount', 'startDate', 'endDate', 'settings', 'categoryId', 'search'));
    }

    /**
     * Export Operational Expenses PDF (Task 13).
     */
    public function exportPdf(Request $request)
    {
        $categoryId = $request->query('category_id');
        $search = $request->query('search');
        [$startDate, $endDate] = FormatHelper::resolveDateFilter($request);

        $expenses = Expense::with(['category', 'user', 'paymentMethod'])
            ->when($categoryId, fn($q) => $q->where('expense_category_id', $categoryId))
            ->when($startDate, fn($q) => $q->whereDate('expense_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('expense_date', '<=', $endDate))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('description', 'LIKE', "%{$search}%")
                        ->orWhere('recipient', 'LIKE', "%{$search}%")
                        ->orWhere('expense_number', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('expense_date', 'asc')
            ->get();

        $totalAmount = (float) $expenses->sum('amount');
        $settings = BusinessSetting::pluck('value', 'key')->all();

        $pdf = Pdf::loadView('expenses.pdf', compact('expenses', 'totalAmount', 'startDate', 'endDate', 'settings'))
            ->setPaper('a4', 'portrait');

        $filename = 'Laporan_Biaya_Operasional_' . date('Ymd') . '.pdf';

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Preview Expenses summary WhatsApp message and PDF metadata.
     */
    public function previewWa(Request $request): JsonResponse
    {
        [$startDate, $endDate] = FormatHelper::resolveDateFilter($request);
        $categoryId = $request->query('category_id');
        $search = $request->query('search');

        $expenses = Expense::with(['category', 'paymentMethod'])
            ->when($categoryId, fn($q) => $q->where('expense_category_id', $categoryId))
            ->when($startDate, fn($q) => $q->whereDate('expense_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('expense_date', '<=', $endDate))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('description', 'LIKE', "%{$search}%")
                        ->orWhere('recipient', 'LIKE', "%{$search}%")
                        ->orWhere('expense_number', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('expense_date', 'asc')
            ->get();

        $totalAmount = (float) $expenses->sum('amount');
        $count = $expenses->count();

        $companyName = BusinessSetting::get('company_name', 'UD. Berkah Rongsok Makmur');
        $periodStr = ($startDate && $endDate) ? "{$startDate} s/d {$endDate}" : date('d/m/Y');

        $msg = "*LAPORAN BIAYA OPERASIONAL*\n";
        $msg .= "*{$companyName}*\n";
        $msg .= "Periode: {$periodStr}\n\n";
        $msg .= "🔖 Total Transaksi : *{$count} Beban Operasional*\n";
        $msg .= "💰 Total Biaya     : *" . FormatHelper::rupiah($totalAmount) . "*\n\n";
        $msg .= "*Rincian Per Kategori:*\n";

        $byCategory = $expenses->groupBy(fn($e) => $e->category?->name ?? 'Lain-lain');
        foreach ($byCategory as $catName => $items) {
            $catTotal = $items->sum('amount');
            $msg .= "• {$catName}: " . FormatHelper::rupiah($catTotal) . "\n";
        }

        $msg .= "\n📄 *Terlampir berkas PDF resmi rincian pengeluaran operasional*.\n";
        $msg .= "Diterbitkan resmi oleh Sistem Keuangan Kasir Rongsok.";

        $dateSuffix = ($startDate && $endDate) ? "{$startDate}_sd_{$endDate}" : date('Ymd_His');
        $pdfFilename = "Laporan_Biaya_Operasional_{$dateSuffix}.pdf";
        $pdfUrl = route('expenses.pdf', $request->query());
        $printUrl = route('expenses.print', $request->query());

        return response()->json([
            'success' => true,
            'count' => $count,
            'total_amount' => $totalAmount,
            'message' => FormatHelper::cleanCaption($msg),
            'pdf_filename' => $pdfFilename,
            'pdf_url' => $pdfUrl,
            'print_url' => $printUrl,
            'file_description' => "Dokumen PDF Resmi Biaya Operasional ({$count} Transaksi)",
        ]);
    }

    /**
     * Send Expenses summary via WhatsApp (with PDF attachment).
     */
    public function sendWa(Request $request)
    {
        $phone = $request->input('phone');
        [$startDate, $endDate] = FormatHelper::resolveDateFilter($request);
        $categoryId = $request->input('category_id');
        $search = $request->input('search');
        $sendPdf = $request->boolean('send_pdf', true);
        $caption = FormatHelper::cleanCaption($request->input('caption') ?: $request->input('message'));

        if (empty($phone)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor WhatsApp tujuan wajib diisi.',
                ], 422);
            }
            return back()->with('error', 'Nomor WhatsApp tujuan wajib diisi.');
        }

        $expenses = Expense::with(['category', 'user', 'paymentMethod'])
            ->when($categoryId, fn($q) => $q->where('expense_category_id', $categoryId))
            ->when($startDate, fn($q) => $q->whereDate('expense_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('expense_date', '<=', $endDate))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('description', 'LIKE', "%{$search}%")
                        ->orWhere('recipient', 'LIKE', "%{$search}%")
                        ->orWhere('expense_number', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('expense_date', 'asc')
            ->get();

        $totalAmount = (float) $expenses->sum('amount');
        $count = $expenses->count();

        $settings = BusinessSetting::pluck('value', 'key')->all();
        $companyName = $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur';
        $periodStr = ($startDate && $endDate) ? "{$startDate} s/d {$endDate}" : date('d/m/Y');

        if (empty($caption)) {
            $caption = "*LAPORAN BIAYA OPERASIONAL*\n";
            $caption .= "*{$companyName}*\n";
            $caption .= "Periode: {$periodStr}\n\n";
            $caption .= "🔖 Total Transaksi : *{$count} Transaksi*\n";
            $caption .= "💰 Total Biaya     : *" . FormatHelper::rupiah($totalAmount) . "*\n\n";
            $caption .= "📄 Terlampir berkas PDF dokumen rincian biaya operasional.";
        }
        $caption = FormatHelper::cleanCaption($caption);

        $waService = app(WhatsAppGatewayService::class);
        $dateSuffix = ($startDate && $endDate) ? "{$startDate}_sd_{$endDate}" : date('Ymd_His');
        $filename = "Laporan_Biaya_Operasional_{$dateSuffix}.pdf";

        if ($sendPdf) {
            $pdf = Pdf::loadView('expenses.pdf', compact('expenses', 'totalAmount', 'startDate', 'endDate', 'settings'))
                ->setPaper('a4', 'portrait');
            $pdfRaw = $pdf->output();
            $fileBase64 = base64_encode($pdfRaw);

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
     * Export Expenses to Excel (Task 18).
     */
    public function exportExcel(Request $request)
    {
        $categoryId = $request->query('category_id');
        [$startDate, $endDate] = FormatHelper::resolveDateFilter($request);

        $expenses = Expense::with(['category', 'user', 'paymentMethod'])
            ->when($categoryId, fn($q) => $q->where('expense_category_id', $categoryId))
            ->when($startDate, fn($q) => $q->whereDate('expense_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('expense_date', '<=', $endDate))
            ->orderBy('expense_date', 'desc')
            ->get();

        $filename = 'Biaya_Operasional_' . date('Ymd_His') . '.xls';

        return response()->view('expenses.excel', compact('expenses', 'startDate', 'endDate'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}

