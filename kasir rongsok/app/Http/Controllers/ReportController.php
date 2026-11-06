<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\Expense;
use App\Models\PurchaseTransaction;
use App\Models\PurchaseTransactionItem;
use App\Models\SalarySlip;
use App\Models\SalesTransaction;
use App\Models\SalesTransactionItem;
use App\Models\ScrapItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function index(): View
    {
        return view('reports.index');
    }

    /**
     * 1. Laporan Pembelian Barang
     */
    public function purchases(Request $request): View|Response
    {
        $startDate = $request->query('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::today()->toDateString());
        $scrapItemId = $request->query('scrap_item_id');
        $cashierId = $request->query('cashier_id');
        $format = $request->query('format'); // print, csv

        $query = PurchaseTransactionItem::with(['transaction.user', 'transaction.supplier', 'scrapItem'])
            ->whereHas('transaction', function ($q) use ($startDate, $endDate, $cashierId) {
                $q->where('status', 'completed')
                    ->whereDate('transaction_date', '>=', $startDate)
                    ->whereDate('transaction_date', '<=', $endDate);
                if ($cashierId) {
                    $q->where('user_id', $cashierId);
                }
            })
            ->when($scrapItemId, fn($q) => $q->where('scrap_item_id', $scrapItemId))
            ->latest('id');

        if ($format === 'csv') {
            $items = $query->get();
            return $this->exportPurchasesCsv($items, $startDate, $endDate);
        }

        $items = $query->paginate(20)->withQueryString();
        $scrapItems = ScrapItem::orderBy('name')->get();
        $cashiers = User::where('is_active', true)->get();

        $totalWeight = (float) $query->sum('net_weight');
        $totalAmount = (float) $query->sum('subtotal');

        if ($format === 'print') {
            $settings = BusinessSetting::pluck('value', 'key')->all();
            return response()->view('reports.purchases_print', compact('items', 'startDate', 'endDate', 'totalWeight', 'totalAmount', 'settings'));
        }

        return view('reports.purchases', compact('items', 'scrapItems', 'cashiers', 'startDate', 'endDate', 'scrapItemId', 'cashierId', 'totalWeight', 'totalAmount'));
    }

    /**
     * 2. Laporan Penjualan Barang
     */
    public function sales(Request $request): View|Response
    {
        $startDate = $request->query('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::today()->toDateString());
        $scrapItemId = $request->query('scrap_item_id');
        $format = $request->query('format');

        $query = SalesTransactionItem::with(['transaction.user', 'scrapItem'])
            ->whereHas('transaction', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'completed')
                    ->whereDate('transaction_date', '>=', $startDate)
                    ->whereDate('transaction_date', '<=', $endDate);
            })
            ->when($scrapItemId, fn($q) => $q->where('scrap_item_id', $scrapItemId))
            ->latest('id');

        if ($format === 'csv') {
            return $this->exportSalesCsv($query->get(), $startDate, $endDate);
        }

        $items = $query->paginate(20)->withQueryString();
        $scrapItems = ScrapItem::orderBy('name')->get();

        $totalWeight = (float) $query->sum('weight');
        $totalSales = (float) $query->sum('subtotal');
        $totalCogs = (float) $query->sum('cogs_subtotal');
        $totalProfit = (float) $query->sum('profit_subtotal');

        if ($format === 'print') {
            $settings = BusinessSetting::pluck('value', 'key')->all();
            return response()->view('reports.sales_print', compact('items', 'startDate', 'endDate', 'totalWeight', 'totalSales', 'totalProfit', 'settings'));
        }

        return view('reports.sales', compact('items', 'scrapItems', 'startDate', 'endDate', 'scrapItemId', 'totalWeight', 'totalSales', 'totalCogs', 'totalProfit'));
    }

    /**
     * 3. Laporan Stok & Estimasi Nilai
     */
    public function stock(Request $request): View|Response
    {
        $format = $request->query('format');

        $items = ScrapItem::with(['category', 'stockBalance'])
            ->orderBy('name')
            ->get();

        $totalStock = (float) StockBalance::sum('current_stock');
        $totalValue = (float) StockBalance::selectRaw('SUM(current_stock * average_buy_price) as val')->value('val');

        if ($format === 'csv') {
            return $this->exportStockCsv($items);
        }

        if ($format === 'print') {
            $settings = BusinessSetting::pluck('value', 'key')->all();
            return response()->view('reports.stock_print', compact('items', 'totalStock', 'totalValue', 'settings'));
        }

        return view('reports.stock', compact('items', 'totalStock', 'totalValue'));
    }

    /**
     * 4. Laporan Pengeluaran Operasional
     */
    public function expenses(Request $request): View|Response
    {
        $allDates = $request->query('all_dates') == '1';

        if ($allDates) {
            $startDate = null;
            $endDate = null;
        } elseif ($request->has('start_date') || $request->has('end_date')) {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
        } else {
            $startDate = Carbon::today()->startOfMonth()->toDateString();
            $endDate = Carbon::today()->endOfMonth()->toDateString();
        }

        $format = $request->query('format');

        $query = Expense::with(['category', 'user', 'paymentMethod'])
            ->when($startDate, fn($q) => $q->whereDate('expense_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('expense_date', '<=', $endDate))
            ->latest('expense_date');

        if ($format === 'csv') {
            return $this->exportExpensesCsv($query->get(), $startDate, $endDate);
        }

        $expenses = $query->paginate(20)->withQueryString();
        $totalExpense = (float) (clone $query)->sum('amount');

        if ($format === 'print') {
            $settings = BusinessSetting::pluck('value', 'key')->all();
            return response()->view('reports.expenses_print', compact('expenses', 'startDate', 'endDate', 'totalExpense', 'settings', 'allDates'));
        }

        return view('reports.expenses', compact('expenses', 'startDate', 'endDate', 'totalExpense', 'allDates'));
    }

    /**
     * 5. Laporan Laba Rugi (Profit & Loss) Komprehensif
     * Mencakup Pendapatan, HPP, Laba Kotor, Rincian Barang Masuk & Keluar,
     * Rincian Beban Gaji Karyawan (Slip Gaji), Beban Operasional, dan Laba Bersih.
     * Default per tanggal saat itu (hari ini) dan pembelian barang dicatat sebagai
     * penambahan aset persediaan gudang agar laporan tidak terlihat minus.
     */
    public function profitLoss(Request $request): View|Response
    {
        $allDates = $request->query('all_dates') == '1';

        // Default sesuai tanggal saat itu (Hari Ini) agar tidak akumulatif dari awal waktu atau awal bulan
        if ($allDates) {
            $startDate = null;
            $endDate = null;
        } elseif ($request->has('start_date') || $request->has('end_date')) {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
        } else {
            $startDate = Carbon::today()->startOfMonth()->toDateString();
            $endDate = Carbon::today()->endOfMonth()->toDateString();
        }

        $format = $request->query('format');

        // 1. DATA PENJUALAN / BARANG KELUAR (REALISASI PENDAPATAN)
        $salesQuery = SalesTransaction::where('status', 'completed')
            ->when($startDate, fn($q) => $q->whereDate('transaction_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('transaction_date', '<=', $endDate));

        $totalSales = (float) (clone $salesQuery)->sum('final_amount');
        $totalSalesWeight = (float) (clone $salesQuery)->sum('total_weight');
        $totalCogs = (float) (clone $salesQuery)->sum('estimated_cogs');
        $totalSalesTxCount = (clone $salesQuery)->count();
        $grossProfit = $totalSales - $totalCogs;

        $outgoingItems = DB::table('sales_transaction_items')
            ->join('sales_transactions', 'sales_transaction_items.sales_transaction_id', '=', 'sales_transactions.id')
            ->where('sales_transactions.status', 'completed')
            ->when($startDate, fn($q) => $q->whereDate('sales_transactions.transaction_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('sales_transactions.transaction_date', '<=', $endDate))
            ->select(
                'sales_transaction_items.item_name_snapshot as item_name',
                DB::raw('SUM(sales_transaction_items.weight) as total_weight'),
                DB::raw('SUM(sales_transaction_items.subtotal) as total_amount'),
                DB::raw('SUM(sales_transaction_items.cogs_subtotal) as total_cogs'),
                DB::raw('SUM(sales_transaction_items.profit_subtotal) as total_profit')
            )
            ->groupBy('sales_transaction_items.item_name_snapshot')
            ->orderByDesc('total_weight')
            ->get();

        // 2. DATA PEMBELIAN / BARANG MASUK (PENAMBAHAN ASET PERSEDIAAN GUDANG)
        $purchasesQuery = PurchaseTransaction::where('status', 'completed')
            ->when($startDate, fn($q) => $q->whereDate('transaction_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('transaction_date', '<=', $endDate));

        $totalPurchases = (float) (clone $purchasesQuery)->sum('final_amount');
        $totalPurchasesWeight = (float) (clone $purchasesQuery)->sum('total_net_weight');
        $totalPurchasesTxCount = (clone $purchasesQuery)->count();

        $incomingItems = DB::table('purchase_transaction_items')
            ->join('purchase_transactions', 'purchase_transaction_items.purchase_transaction_id', '=', 'purchase_transactions.id')
            ->leftJoin('scrap_items', 'purchase_transaction_items.scrap_item_id', '=', 'scrap_items.id')
            ->where('purchase_transactions.status', 'completed')
            ->when($startDate, fn($q) => $q->whereDate('purchase_transactions.transaction_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('purchase_transactions.transaction_date', '<=', $endDate))
            ->select(
                'purchase_transaction_items.item_name_snapshot as item_name',
                DB::raw('SUM(purchase_transaction_items.net_weight) as total_weight'),
                DB::raw('SUM(purchase_transaction_items.subtotal) as total_amount'),
                DB::raw('AVG(purchase_transaction_items.price_per_kg) as avg_buy_price'),
                DB::raw('COALESCE(MAX(scrap_items.sell_price_per_kg), 0) as current_sell_price'),
                DB::raw('SUM(purchase_transaction_items.net_weight * COALESCE(scrap_items.sell_price_per_kg, purchase_transaction_items.price_per_kg)) as estimated_sales_value')
            )
            ->groupBy('purchase_transaction_items.item_name_snapshot')
            ->orderByDesc('total_weight')
            ->get();

        $totalEstimatedInventorySales = (float) $incomingItems->sum('estimated_sales_value');
        $totalPotentialInventoryProfit = $totalEstimatedInventorySales - $totalPurchases;

        // 3. BEBAN GAJI KARYAWAN (SLIP GAJI) PADA PERIODE TERPILIH
        $salarySlips = SalarySlip::with(['employeeUser', 'creator', 'paymentMethod'])
            ->when($startDate, fn($q) => $q->whereDate('payment_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('payment_date', '<=', $endDate))
            ->latest('payment_date')
            ->get();
        $totalSalaries = (float) $salarySlips->sum('net_salary');
        $totalSalariesGross = (float) $salarySlips->sum('total_income');

        // 4. BEBAN PENGELUARAN OPERASIONAL LAINNYA PADA PERIODE TERPILIH
        $expenses = Expense::with(['category', 'user', 'paymentMethod'])
            ->when($startDate, fn($q) => $q->whereDate('expense_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('expense_date', '<=', $endDate))
            ->latest('expense_date')
            ->get();
        $totalExpenses = (float) $expenses->sum('amount');

        $expensesByCategory = DB::table('expenses')
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->whereNull('expenses.deleted_at')
            ->when($startDate, fn($q) => $q->whereDate('expenses.expense_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('expenses.expense_date', '<=', $endDate))
            ->select('expense_categories.name', DB::raw('SUM(expenses.amount) as total'))
            ->groupBy('expense_categories.name')
            ->get();

        // 5. TOTAL BEBAN OPERASIONAL KESELURUHAN & LABA BERSIH
        // Laba Bersih = Laba Kotor Penjualan - Total Beban Operasional (Slip Gaji + Biaya Operasional Depo)
        $totalOperatingExpenses = $totalSalaries + $totalExpenses;
        $netProfit = $grossProfit - $totalOperatingExpenses;

        $viewData = compact(
            'startDate', 'endDate', 'allDates',
            'totalSales', 'totalSalesWeight', 'totalCogs', 'totalSalesTxCount', 'grossProfit', 'outgoingItems',
            'totalPurchases', 'totalPurchasesWeight', 'totalPurchasesTxCount', 'incomingItems',
            'totalEstimatedInventorySales', 'totalPotentialInventoryProfit',
            'salarySlips', 'totalSalaries', 'totalSalariesGross',
            'expenses', 'totalExpenses', 'expensesByCategory',
            'totalOperatingExpenses', 'netProfit'
        );

        if ($format === 'print') {
            $settings = BusinessSetting::pluck('value', 'key')->all();
            $viewData['settings'] = $settings;
            return response()->view('reports.profit_loss_print', $viewData);
        }

        return view('reports.profit_loss', $viewData);
    }

    /**
     * 6. Laporan Transaksi Per Kasir
     */
    public function cashier(Request $request): View
    {
        $startDate = $request->query('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::today()->toDateString());

        $cashiers = User::withCount(['purchaseTransactions as total_transactions' => function ($q) use ($startDate, $endDate) {
            $q->where('status', 'completed')
                ->whereDate('transaction_date', '>=', $startDate)
                ->whereDate('transaction_date', '<=', $endDate);
        }])
        ->withSum(['purchaseTransactions as total_payout' => function ($q) use ($startDate, $endDate) {
            $q->where('status', 'completed')
                ->whereDate('transaction_date', '>=', $startDate)
                ->whereDate('transaction_date', '<=', $endDate);
        }], 'final_amount')
        ->withSum(['purchaseTransactions as total_weight' => function ($q) use ($startDate, $endDate) {
            $q->where('status', 'completed')
                ->whereDate('transaction_date', '>=', $startDate)
                ->whereDate('transaction_date', '<=', $endDate);
        }], 'total_net_weight')
        ->get();

        return view('reports.cashier', compact('cashiers', 'startDate', 'endDate'));
    }

    /* --- CSV Export Helpers --- */

    protected function exportPurchasesCsv($items, $startDate, $endDate): Response
    {
        $filename = "laporan_pembelian_{$startDate}_{$endDate}.csv";
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $output = "\xEF\xBB\xBF"; // UTF-8 BOM
        $output .= "No,Tanggal,No Nota,Pemasok,Kasir,Kode,Nama Barang,Berat Bersih (kg),Harga/kg,Subtotal\n";

        foreach ($items as $idx => $item) {
            $tx = $item->transaction;
            $row = [
                $idx + 1,
                $tx->transaction_date->format('Y-m-d H:i'),
                $tx->invoice_number,
                str_replace(',', ' ', $tx->supplier_name),
                $tx->user?->name,
                $item->item_code_snapshot,
                str_replace(',', ' ', $item->item_name_snapshot),
                $item->net_weight,
                $item->price_per_kg,
                $item->subtotal,
            ];
            $output .= implode(',', $row) . "\n";
        }

        return response($output, 200, $headers);
    }

    protected function exportSalesCsv($items, $startDate, $endDate): Response
    {
        $filename = "laporan_penjualan_{$startDate}_{$endDate}.csv";
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $output = "\xEF\xBB\xBF";
        $output .= "No,Tanggal,No Nota,Pembeli,Kode,Nama Barang,Berat (kg),Harga Beli/kg,Harga Jual/kg,Subtotal,Estimasi Laba\n";

        foreach ($items as $idx => $item) {
            $tx = $item->transaction;
            $row = [
                $idx + 1,
                $tx->transaction_date->format('Y-m-d H:i'),
                $tx->invoice_number,
                str_replace(',', ' ', $tx->buyer_name),
                $item->item_code_snapshot,
                str_replace(',', ' ', $item->item_name_snapshot),
                $item->weight,
                $item->buy_price_snapshot,
                $item->sell_price_per_kg,
                $item->subtotal,
                $item->profit_subtotal,
            ];
            $output .= implode(',', $row) . "\n";
        }

        return response($output, 200, $headers);
    }

    protected function exportStockCsv($items): Response
    {
        $filename = "laporan_stok_" . date('Ymd') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $output = "\xEF\xBB\xBF";
        $output .= "Kode,Nama Barang,Kategori,Stok (kg),Rata-rata Harga Beli,Estimasi Nilai Stok,Harga Beli Terkini,Harga Jual Terkini\n";

        foreach ($items as $item) {
            $bal = $item->stockBalance;
            $avg = (float) ($bal?->average_buy_price ?? 0);
            $qty = (float) ($bal?->current_stock ?? 0);
            $val = $qty * $avg;

            $row = [
                $item->code,
                str_replace(',', ' ', $item->name),
                $item->category?->name,
                $qty,
                $avg,
                $val,
                $item->buy_price_per_kg,
                $item->sell_price_per_kg,
            ];
            $output .= implode(',', $row) . "\n";
        }

        return response($output, 200, $headers);
    }

    protected function exportExpensesCsv($expenses, $startDate, $endDate): Response
    {
        $filename = "laporan_pengeluaran_{$startDate}_{$endDate}.csv";
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $output = "\xEF\xBB\xBF";
        $output .= "No,Tanggal,No Pengeluaran,Kategori,Deskripsi,Penerima,Nominal,Dicatat Oleh\n";

        foreach ($expenses as $idx => $exp) {
            $row = [
                $idx + 1,
                $exp->expense_date->format('Y-m-d'),
                $exp->expense_number,
                $exp->category?->name,
                str_replace(',', ' ', $exp->description),
                str_replace(',', ' ', $exp->recipient ?? '-'),
                $exp->amount,
                $exp->user?->name,
            ];
            $output .= implode(',', $row) . "\n";
        }

        return response($output, 200, $headers);
    }
}

