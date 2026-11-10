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
use App\Services\FormatHelper;
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
        [$startDate, $endDate, $selectedMonth, $selectedYear] = FormatHelper::resolveDateFilter(
            $request,
            Carbon::today()->startOfMonth()->toDateString(),
            Carbon::today()->toDateString()
        );
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

        $unitTotals = (clone $query)->selectRaw("
            CASE 
                WHEN unit_type = 'pcs' THEN 'pcs'
                ELSE 'kg'
            END as unit_cat,
            SUM(CASE WHEN unit_type = 'pcs' THEN quantity ELSE net_weight END) as total_qty
        ")->groupBy('unit_cat')->pluck('total_qty', 'unit_cat');

        $totalKg = (float) ($unitTotals['kg'] ?? 0);
        $totalPcs = (float) ($unitTotals['pcs'] ?? 0);
        $totalCargoParts = [];
        if ($totalKg > 0) $totalCargoParts[] = FormatHelper::weight($totalKg) . ' kg';
        if ($totalPcs > 0) $totalCargoParts[] = number_format($totalPcs, 0, ',', '.') . ' pcs';
        $totalCargoDisplay = empty($totalCargoParts) ? '0 kg' : implode(' + ', $totalCargoParts);

        $totalWeight = $totalKg;
        $totalAmount = (float) $query->sum('subtotal');

        if ($format === 'print') {
            $settings = BusinessSetting::pluck('value', 'key')->all();
            return response()->view('reports.purchases_print', compact('items', 'startDate', 'endDate', 'totalWeight', 'totalKg', 'totalPcs', 'totalCargoDisplay', 'totalAmount', 'settings'));
        }

        return view('reports.purchases', compact('items', 'scrapItems', 'cashiers', 'startDate', 'endDate', 'scrapItemId', 'cashierId', 'totalWeight', 'totalKg', 'totalPcs', 'totalCargoDisplay', 'totalAmount', 'selectedMonth', 'selectedYear'));
    }

    /**
     * 2. Laporan Penjualan Barang
     */
    public function sales(Request $request): View|Response
    {
        [$startDate, $endDate, $selectedMonth, $selectedYear] = FormatHelper::resolveDateFilter(
            $request,
            Carbon::today()->startOfMonth()->toDateString(),
            Carbon::today()->toDateString()
        );
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

        $unitTotals = (clone $query)->selectRaw("
            CASE 
                WHEN unit_type = 'pcs' THEN 'pcs'
                ELSE 'kg'
            END as unit_cat,
            SUM(CASE WHEN unit_type = 'pcs' THEN quantity ELSE weight END) as total_qty
        ")->groupBy('unit_cat')->pluck('total_qty', 'unit_cat');

        $totalKg = (float) ($unitTotals['kg'] ?? 0);
        $totalPcs = (float) ($unitTotals['pcs'] ?? 0);
        $totalCargoParts = [];
        if ($totalKg > 0) $totalCargoParts[] = FormatHelper::weight($totalKg) . ' kg';
        if ($totalPcs > 0) $totalCargoParts[] = number_format($totalPcs, 0, ',', '.') . ' pcs';
        $totalCargoDisplay = empty($totalCargoParts) ? '0 kg' : implode(' + ', $totalCargoParts);

        $totalWeight = $totalKg;
        $totalSales = (float) $query->sum('subtotal');
        $totalCogs = (float) $query->sum('cogs_subtotal');
        $totalProfit = (float) $query->sum('profit_subtotal');

        if ($format === 'print') {
            $settings = BusinessSetting::pluck('value', 'key')->all();
            return response()->view('reports.sales_print', compact('items', 'startDate', 'endDate', 'totalWeight', 'totalKg', 'totalPcs', 'totalCargoDisplay', 'totalSales', 'totalProfit', 'settings'));
        }

        return view('reports.sales', compact('items', 'scrapItems', 'startDate', 'endDate', 'scrapItemId', 'totalWeight', 'totalKg', 'totalPcs', 'totalCargoDisplay', 'totalSales', 'totalCogs', 'totalProfit', 'selectedMonth', 'selectedYear'));
    }

    /**
     * 3. Laporan Stok & Estimasi Nilai
     */
    public function stock(Request $request): View|Response
    {
        $format = $request->query('format');

        $items = ScrapItem::with(['category', 'stockBalance', 'priceStocks'])
            ->orderBy('name')
            ->get();

        $stockUnitTotals = DB::table('stock_balances')
            ->join('scrap_items', 'stock_balances.scrap_item_id', '=', 'scrap_items.id')
            ->selectRaw("
                CASE 
                    WHEN scrap_items.unit_type = 'pcs' OR LOWER(scrap_items.unit) IN ('pcs', 'buah', 'unit', 'satuan', 'biji', 'set') THEN 'pcs'
                    ELSE 'kg'
                END as unit_cat,
                SUM(stock_balances.current_stock) as total_stock
            ")
            ->groupBy('unit_cat')
            ->pluck('total_stock', 'unit_cat');

        $totalStockKg = (float) ($stockUnitTotals['kg'] ?? 0);
        $totalStockPcs = (float) ($stockUnitTotals['pcs'] ?? 0);
        $stockCargoParts = [];
        if ($totalStockKg > 0) $stockCargoParts[] = FormatHelper::weight($totalStockKg) . ' kg';
        if ($totalStockPcs > 0) $stockCargoParts[] = number_format($totalStockPcs, 0, ',', '.') . ' pcs';
        $totalStockDisplay = empty($stockCargoParts) ? '0 kg' : implode(' + ', $stockCargoParts);

        $totalStock = $totalStockKg;
        $totalValue = (float) StockBalance::selectRaw('SUM(current_stock * average_buy_price) as val')->value('val');

        if ($format === 'csv') {
            return $this->exportStockCsv($items);
        }

        if ($format === 'print') {
            $settings = BusinessSetting::pluck('value', 'key')->all();
            return response()->view('reports.stock_print', compact('items', 'totalStock', 'totalStockKg', 'totalStockPcs', 'totalStockDisplay', 'totalValue', 'settings'));
        }

        return view('reports.stock', compact('items', 'totalStock', 'totalStockKg', 'totalStockPcs', 'totalStockDisplay', 'totalValue'));
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
            $selectedMonth = null;
            $selectedYear = null;
        } else {
            [$startDate, $endDate, $selectedMonth, $selectedYear] = FormatHelper::resolveDateFilter(
                $request,
                Carbon::today()->startOfMonth()->toDateString(),
                Carbon::today()->endOfMonth()->toDateString()
            );
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

        return view('reports.expenses', compact('expenses', 'startDate', 'endDate', 'totalExpense', 'allDates', 'selectedMonth', 'selectedYear'));
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

        if ($allDates) {
            $startDate = null;
            $endDate = null;
            $selectedMonth = null;
            $selectedYear = null;
        } else {
            [$startDate, $endDate, $selectedMonth, $selectedYear] = FormatHelper::resolveDateFilter(
                $request,
                Carbon::today()->startOfMonth()->toDateString(),
                Carbon::today()->endOfMonth()->toDateString()
            );
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
                DB::raw("COALESCE(MAX(sales_transaction_items.unit_type), 'kg') as unit"),
                DB::raw("SUM(CASE WHEN sales_transaction_items.unit_type = 'pcs' THEN sales_transaction_items.quantity ELSE sales_transaction_items.weight END) as total_weight"),
                DB::raw('SUM(sales_transaction_items.subtotal) as total_amount'),
                DB::raw('SUM(sales_transaction_items.cogs_subtotal) as total_cogs'),
                DB::raw('SUM(sales_transaction_items.profit_subtotal) as total_profit'),
                DB::raw("CASE WHEN SUM(CASE WHEN sales_transaction_items.unit_type = 'pcs' THEN sales_transaction_items.quantity ELSE sales_transaction_items.weight END) > 0 THEN (SUM(sales_transaction_items.cogs_subtotal) / SUM(CASE WHEN sales_transaction_items.unit_type = 'pcs' THEN sales_transaction_items.quantity ELSE sales_transaction_items.weight END)) ELSE AVG(sales_transaction_items.buy_price_snapshot) END as avg_buy_price"),
                DB::raw("CASE WHEN SUM(CASE WHEN sales_transaction_items.unit_type = 'pcs' THEN sales_transaction_items.quantity ELSE sales_transaction_items.weight END) > 0 THEN (SUM(sales_transaction_items.subtotal) / SUM(CASE WHEN sales_transaction_items.unit_type = 'pcs' THEN sales_transaction_items.quantity ELSE sales_transaction_items.weight END)) ELSE AVG(sales_transaction_items.sell_price_per_kg) END as avg_sell_price")
            )
            ->groupBy('sales_transaction_items.item_name_snapshot')
            ->orderByDesc('total_weight')
            ->get();

        $salesUnitBreakdown = DB::table('sales_transaction_items')
            ->join('sales_transactions', 'sales_transaction_items.sales_transaction_id', '=', 'sales_transactions.id')
            ->where('sales_transactions.status', 'completed')
            ->when($startDate, fn($q) => $q->whereDate('sales_transactions.transaction_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('sales_transactions.transaction_date', '<=', $endDate))
            ->selectRaw("
                CASE 
                    WHEN sales_transaction_items.unit_type = 'pcs' THEN 'pcs'
                    ELSE 'kg'
                END as unit_cat,
                SUM(CASE WHEN sales_transaction_items.unit_type = 'pcs' THEN sales_transaction_items.quantity ELSE sales_transaction_items.weight END) as total_qty
            ")
            ->groupBy('unit_cat')
            ->pluck('total_qty', 'unit_cat');
        $salKg = (float) ($salesUnitBreakdown['kg'] ?? 0);
        $salPcs = (float) ($salesUnitBreakdown['pcs'] ?? 0);
        $salParts = [];
        if ($salKg > 0) $salParts[] = FormatHelper::weight($salKg) . ' kg';
        if ($salPcs > 0) $salParts[] = number_format($salPcs, 0, ',', '.') . ' pcs';
        $totalSalesCargoDisplay = empty($salParts) ? '0 kg' : implode(' + ', $salParts);

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
                DB::raw("COALESCE(MAX(purchase_transaction_items.unit_type), MAX(scrap_items.unit), 'kg') as unit"),
                DB::raw("SUM(CASE WHEN purchase_transaction_items.unit_type = 'pcs' THEN purchase_transaction_items.quantity ELSE purchase_transaction_items.net_weight END) as total_weight"),
                DB::raw('SUM(purchase_transaction_items.subtotal) as total_amount'),
                DB::raw("CASE WHEN SUM(CASE WHEN purchase_transaction_items.unit_type = 'pcs' THEN purchase_transaction_items.quantity ELSE purchase_transaction_items.net_weight END) > 0 THEN (SUM(purchase_transaction_items.subtotal) / SUM(CASE WHEN purchase_transaction_items.unit_type = 'pcs' THEN purchase_transaction_items.quantity ELSE purchase_transaction_items.net_weight END)) ELSE AVG(purchase_transaction_items.price_per_kg) END as avg_buy_price"),
                DB::raw('COALESCE(MAX(scrap_items.sell_price_per_kg), 0) as current_sell_price'),
                DB::raw("SUM((CASE WHEN purchase_transaction_items.unit_type = 'pcs' THEN purchase_transaction_items.quantity ELSE purchase_transaction_items.net_weight END) * COALESCE(scrap_items.sell_price_per_kg, purchase_transaction_items.price_per_kg)) as estimated_sales_value")
            )
            ->groupBy('purchase_transaction_items.item_name_snapshot')
            ->orderByDesc('total_weight')
            ->get();

        $purUnitBreakdown = DB::table('purchase_transaction_items')
            ->join('purchase_transactions', 'purchase_transaction_items.purchase_transaction_id', '=', 'purchase_transactions.id')
            ->where('purchase_transactions.status', 'completed')
            ->when($startDate, fn($q) => $q->whereDate('purchase_transactions.transaction_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('purchase_transactions.transaction_date', '<=', $endDate))
            ->selectRaw("
                CASE 
                    WHEN purchase_transaction_items.unit_type = 'pcs' THEN 'pcs'
                    ELSE 'kg'
                END as unit_cat,
                SUM(CASE WHEN purchase_transaction_items.unit_type = 'pcs' THEN purchase_transaction_items.quantity ELSE purchase_transaction_items.net_weight END) as total_qty
            ")
            ->groupBy('unit_cat')
            ->pluck('total_qty', 'unit_cat');
        $purKg = (float) ($purUnitBreakdown['kg'] ?? 0);
        $purPcs = (float) ($purUnitBreakdown['pcs'] ?? 0);
        $purParts = [];
        if ($purKg > 0) $purParts[] = FormatHelper::weight($purKg) . ' kg';
        if ($purPcs > 0) $purParts[] = number_format($purPcs, 0, ',', '.') . ' pcs';
        $totalPurchasesCargoDisplay = empty($purParts) ? '0 kg' : implode(' + ', $purParts);

        $totalEstimatedInventorySales = (float) $incomingItems->sum('estimated_sales_value');
        $totalPotentialInventoryProfit = $totalEstimatedInventorySales - $totalPurchases;

        // 3. DATA SISA STOK BARANG & HARGA (STOK FISIK DI GUDANG DEPO SAAT INI)
        $stockBalanceItems = ScrapItem::with(['category', 'stockBalance'])
            ->where(function ($q) {
                $q->where('is_active', true)
                  ->orWhereHas('stockBalance', fn($sq) => $sq->where('current_stock', '>', 0));
            })
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                $stock = (float) ($item->stockBalance?->current_stock ?? 0);
                $buyPrice = (float) ($item->stockBalance?->average_buy_price > 0 
                    ? $item->stockBalance->average_buy_price 
                    : $item->buy_price_per_kg);
                $sellPrice = (float) $item->sell_price_per_kg;
                $stockValue = $stock * $buyPrice;

                return (object) [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category_name' => $item->category?->name ?? 'Umum',
                    'unit' => $item->unit ?? 'kg',
                    'current_stock' => $stock,
                    'buy_price' => $buyPrice,
                    'sell_price' => $sellPrice,
                    'stock_value' => $stockValue,
                ];
            });

        $stockUnitTotals = DB::table('stock_balances')
            ->join('scrap_items', 'stock_balances.scrap_item_id', '=', 'scrap_items.id')
            ->selectRaw("
                CASE 
                    WHEN scrap_items.unit_type = 'pcs' OR LOWER(scrap_items.unit) IN ('pcs', 'buah', 'unit', 'satuan', 'biji', 'set') THEN 'pcs'
                    ELSE 'kg'
                END as unit_cat,
                SUM(stock_balances.current_stock) as total_stock
            ")
            ->groupBy('unit_cat')
            ->pluck('total_stock', 'unit_cat');
        $sKg = (float) ($stockUnitTotals['kg'] ?? 0);
        $sPcs = (float) ($stockUnitTotals['pcs'] ?? 0);
        $sParts = [];
        if ($sKg > 0) $sParts[] = FormatHelper::weight($sKg) . ' kg';
        if ($sPcs > 0) $sParts[] = number_format($sPcs, 0, ',', '.') . ' pcs';
        $totalCurrentStockDisplay = empty($sParts) ? '0 kg' : implode(' + ', $sParts);

        $totalCurrentStock = (float) $stockBalanceItems->sum('current_stock');
        $totalCurrentStockValue = (float) $stockBalanceItems->sum('stock_value');

        // 4. BEBAN GAJI KARYAWAN (SLIP GAJI) PADA PERIODE TERPILIH
        $salarySlips = SalarySlip::with(['employeeUser', 'creator', 'paymentMethod'])
            ->when($startDate, fn($q) => $q->whereDate('payment_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('payment_date', '<=', $endDate))
            ->latest('payment_date')
            ->get();
        $totalSalaries = (float) $salarySlips->sum('net_salary');
        $totalSalariesGross = (float) $salarySlips->sum('total_income');

        // 5. BEBAN PENGELUARAN OPERASIONAL LAINNYA PADA PERIODE TERPILIH
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

        // 6. TOTAL BEBAN OPERASIONAL KESELURUHAN & LABA BERSIH
        // Laba Bersih = Laba Kotor Penjualan - Total Beban Operasional (Slip Gaji + Biaya Operasional Depo)
        $totalOperatingExpenses = $totalSalaries + $totalExpenses;
        $netProfit = $grossProfit - $totalOperatingExpenses;

        $viewData = compact(
            'startDate', 'endDate', 'allDates', 'selectedMonth', 'selectedYear',
            'totalSales', 'totalSalesWeight', 'totalSalesCargoDisplay', 'totalCogs', 'totalSalesTxCount', 'grossProfit', 'outgoingItems',
            'totalPurchases', 'totalPurchasesWeight', 'totalPurchasesCargoDisplay', 'totalPurchasesTxCount', 'incomingItems',
            'stockBalanceItems', 'totalCurrentStock', 'totalCurrentStockDisplay', 'totalCurrentStockValue',
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
        [$startDate, $endDate, $selectedMonth, $selectedYear] = FormatHelper::resolveDateFilter(
            $request,
            Carbon::today()->startOfMonth()->toDateString(),
            Carbon::today()->toDateString()
        );

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

        foreach ($cashiers as $c) {
            $breakdown = DB::table('purchase_transaction_items')
                ->join('purchase_transactions', 'purchase_transaction_items.purchase_transaction_id', '=', 'purchase_transactions.id')
                ->where('purchase_transactions.user_id', $c->id)
                ->where('purchase_transactions.status', 'completed')
                ->whereDate('purchase_transactions.transaction_date', '>=', $startDate)
                ->whereDate('purchase_transactions.transaction_date', '<=', $endDate)
                ->selectRaw("
                    CASE 
                        WHEN purchase_transaction_items.unit_type = 'pcs' THEN 'pcs'
                        ELSE 'kg'
                    END as unit_cat,
                    SUM(CASE WHEN purchase_transaction_items.unit_type = 'pcs' THEN purchase_transaction_items.quantity ELSE purchase_transaction_items.net_weight END) as total_qty
                ")
                ->groupBy('unit_cat')
                ->pluck('total_qty', 'unit_cat');

            $cKg = (float) ($breakdown['kg'] ?? 0);
            $cPcs = (float) ($breakdown['pcs'] ?? 0);
            $parts = [];
            if ($cKg > 0) $parts[] = FormatHelper::weight($cKg) . ' kg';
            if ($cPcs > 0) $parts[] = number_format($cPcs, 0, ',', '.') . ' pcs';
            $c->total_cargo_display = empty($parts) ? '0 kg' : implode(' + ', $parts);
        }

        return view('reports.cashier', compact('cashiers', 'startDate', 'endDate', 'selectedMonth', 'selectedYear'));
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
        $output .= "No,Tanggal,No Nota,Pemasok,Kasir,Kode,Nama Barang,Muatan / Berat,Satuan,Harga / Satuan,Subtotal\n";

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
                $item->quantity_or_weight,
                $item->effective_unit,
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
        $output .= "No,Tanggal,No Nota,Pembeli,Kode,Nama Barang,Muatan / Berat,Satuan,Harga Beli / Satuan,Harga Jual / Satuan,Subtotal,Estimasi Laba\n";

        foreach ($items as $idx => $item) {
            $tx = $item->transaction;
            $row = [
                $idx + 1,
                $tx->transaction_date->format('Y-m-d H:i'),
                $tx->invoice_number,
                str_replace(',', ' ', $tx->buyer_name),
                $item->item_code_snapshot,
                str_replace(',', ' ', $item->item_name_snapshot),
                $item->quantity_or_weight,
                $item->effective_unit,
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
        $output .= "Kode,Nama Barang,Kategori,Stok Fisik,Satuan,Rata-rata Harga Beli,Estimasi Nilai Stok,Harga Beli Terkini,Harga Jual Terkini\n";

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
                $item->unit_type ?: ($item->unit ?? 'kg'),
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

