<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\PurchaseTransaction;
use App\Models\SalesTransaction;
use App\Models\ScrapItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Get real-time dashboard analytics with period filtering.
     */
    public function getDashboardMetrics(string $period = 'today', ?string $startDate = null, ?string $endDate = null): array
    {
        [$start, $end] = $this->resolveDateRange($period, $startDate, $endDate);

        // Purchases within period
        $purchasesQuery = PurchaseTransaction::where('status', PurchaseTransaction::STATUS_COMPLETED)
            ->whereBetween('transaction_date', [$start, $end]);

        $totalPurchasesAmount = (float) $purchasesQuery->sum('final_amount');
        $totalPurchasesWeight = (float) $purchasesQuery->sum('total_net_weight');
        $totalPurchasesCount = (int) $purchasesQuery->count();

        // Breakdown purchases by unit
        $purchasesItemsSummary = DB::table('purchase_transaction_items')
            ->join('purchase_transactions', 'purchase_transaction_items.purchase_transaction_id', '=', 'purchase_transactions.id')
            ->where('purchase_transactions.status', PurchaseTransaction::STATUS_COMPLETED)
            ->whereBetween('purchase_transactions.transaction_date', [$start, $end])
            ->where('purchase_transaction_items.is_cancelled', false)
            ->select(
                DB::raw("CASE WHEN LOWER(purchase_transaction_items.unit_type) IN ('pcs', 'satuan', 'unit', 'buah') THEN 'pcs' ELSE 'kg' END as unit_group"),
                DB::raw("SUM(CASE WHEN LOWER(purchase_transaction_items.unit_type) IN ('pcs', 'satuan', 'unit', 'buah') THEN (CASE WHEN purchase_transaction_items.quantity > 0 THEN purchase_transaction_items.quantity ELSE purchase_transaction_items.net_weight END) ELSE purchase_transaction_items.net_weight END) as total_qty")
            )
            ->groupBy('unit_group')
            ->pluck('total_qty', 'unit_group');

        $totalPurchasesKg = (float) ($purchasesItemsSummary['kg'] ?? 0);
        $totalPurchasesPcs = (float) ($purchasesItemsSummary['pcs'] ?? 0);

        $purchasesParts = [];
        if ($totalPurchasesKg > 0) $purchasesParts[] = FormatHelper::weight($totalPurchasesKg) . ' kg';
        if ($totalPurchasesPcs > 0) $purchasesParts[] = FormatHelper::number($totalPurchasesPcs) . ' pcs';
        $totalPurchasesDisplay = count($purchasesParts) > 0 ? implode(' + ', $purchasesParts) : (FormatHelper::weight($totalPurchasesWeight) . ' kg');

        // Sales within period
        $salesQuery = SalesTransaction::where('status', SalesTransaction::STATUS_COMPLETED)
            ->whereBetween('transaction_date', [$start, $end]);

        $totalSalesAmount = (float) $salesQuery->sum('final_amount');
        $totalSalesWeight = (float) $salesQuery->sum('total_weight');
        $totalSalesProfit = (float) $salesQuery->sum('estimated_profit');

        // Breakdown sales by unit
        $salesItemsSummary = DB::table('sales_transaction_items')
            ->join('sales_transactions', 'sales_transaction_items.sales_transaction_id', '=', 'sales_transactions.id')
            ->where('sales_transactions.status', SalesTransaction::STATUS_COMPLETED)
            ->whereBetween('sales_transactions.transaction_date', [$start, $end])
            ->select(
                DB::raw("CASE WHEN LOWER(sales_transaction_items.unit_type) IN ('pcs', 'satuan', 'unit', 'buah') THEN 'pcs' ELSE 'kg' END as unit_group"),
                DB::raw("SUM(CASE WHEN LOWER(sales_transaction_items.unit_type) IN ('pcs', 'satuan', 'unit', 'buah') THEN (CASE WHEN sales_transaction_items.quantity > 0 THEN sales_transaction_items.quantity ELSE sales_transaction_items.weight END) ELSE sales_transaction_items.weight END) as total_qty")
            )
            ->groupBy('unit_group')
            ->pluck('total_qty', 'unit_group');

        $totalSalesKg = (float) ($salesItemsSummary['kg'] ?? 0);
        $totalSalesPcs = (float) ($salesItemsSummary['pcs'] ?? 0);

        $salesParts = [];
        if ($totalSalesKg > 0) $salesParts[] = FormatHelper::weight($totalSalesKg) . ' kg';
        if ($totalSalesPcs > 0) $salesParts[] = FormatHelper::number($totalSalesPcs) . ' pcs';
        $totalSalesDisplay = count($salesParts) > 0 ? implode(' + ', $salesParts) : (FormatHelper::weight($totalSalesWeight) . ' kg');

        // Expenses within period
        $totalExpensesAmount = (float) Expense::whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])->sum('amount');

        // Current stock inventory metrics (snapshot right now)
        $totalStockKg = (float) StockBalance::sum('current_stock');
        $estimatedStockValue = (float) DB::table('stock_balances')
            ->selectRaw('SUM(current_stock * average_buy_price) as total_val')
            ->value('total_val');

        // Breakdown stock by unit
        $stockSummary = DB::table('stock_balances')
            ->join('scrap_items', 'stock_balances.scrap_item_id', '=', 'scrap_items.id')
            ->whereNull('scrap_items.deleted_at')
            ->select(
                DB::raw("CASE WHEN LOWER(scrap_items.unit) IN ('pcs', 'satuan', 'unit', 'buah') THEN 'pcs' ELSE 'kg' END as unit_group"),
                DB::raw("SUM(stock_balances.current_stock) as total_qty")
            )
            ->groupBy('unit_group')
            ->pluck('total_qty', 'unit_group');

        $totalStockKgVal = (float) ($stockSummary['kg'] ?? 0);
        $totalStockPcsVal = (float) ($stockSummary['pcs'] ?? 0);

        $stockParts = [];
        if ($totalStockKgVal > 0) $stockParts[] = FormatHelper::weight($totalStockKgVal) . ' kg';
        if ($totalStockPcsVal > 0) $stockParts[] = FormatHelper::number($totalStockPcsVal) . ' pcs';
        $totalStockDisplay = count($stockParts) > 0 ? implode(' + ', $stockParts) : (FormatHelper::weight($totalStockKg) . ' kg');

        // Top 5 items with largest available stock
        $topStockItems = ScrapItem::with('stockBalance', 'category')
            ->join('stock_balances', 'scrap_items.id', '=', 'stock_balances.scrap_item_id')
            ->where('stock_balances.current_stock', '>', 0)
            ->orderByDesc('stock_balances.current_stock')
            ->take(5)
            ->get(['scrap_items.*', 'stock_balances.current_stock as current_stock_qty', 'stock_balances.average_buy_price']);

        // Low stock items
        $lowStockItems = ScrapItem::with('stockBalance', 'category')
            ->join('stock_balances', 'scrap_items.id', '=', 'stock_balances.scrap_item_id')
            ->whereRaw('stock_balances.current_stock <= scrap_items.min_stock_alert')
            ->where('scrap_items.min_stock_alert', '>', 0)
            ->where('scrap_items.is_active', true)
            ->take(5)
            ->get();

        // 7-day purchase trend chart data
        $chartLabels = [];
        $chartPurchases = [];
        $chartWeights = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $dayLabel = $day->isoFormat('D MMM');
            $dayStart = $day->copy()->startOfDay();
            $dayEnd = $day->copy()->endOfDay();

            $daySum = (float) PurchaseTransaction::where('status', PurchaseTransaction::STATUS_COMPLETED)
                ->whereBetween('transaction_date', [$dayStart, $dayEnd])
                ->sum('final_amount');

            $dayWeight = (float) PurchaseTransaction::where('status', PurchaseTransaction::STATUS_COMPLETED)
                ->whereBetween('transaction_date', [$dayStart, $dayEnd])
                ->sum('total_net_weight');

            $chartLabels[] = $dayLabel;
            $chartPurchases[] = $daySum;
            $chartWeights[] = $dayWeight;
        }

        // Recent transactions
        $recentTransactions = PurchaseTransaction::with(['user', 'supplier'])
            ->latest('transaction_date')
            ->take(8)
            ->get();

        // Recent activity logs
        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(6)
            ->get();

        return [
            'period' => $period,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'total_purchases_amount' => $totalPurchasesAmount,
            'total_purchases_weight' => $totalPurchasesWeight,
            'total_purchases_count' => $totalPurchasesCount,
            'total_purchases_kg' => $totalPurchasesKg,
            'total_purchases_pcs' => $totalPurchasesPcs,
            'total_purchases_display' => $totalPurchasesDisplay,
            'total_sales_amount' => $totalSalesAmount,
            'total_sales_weight' => $totalSalesWeight,
            'total_sales_profit' => $totalSalesProfit,
            'total_sales_kg' => $totalSalesKg,
            'total_sales_pcs' => $totalSalesPcs,
            'total_sales_display' => $totalSalesDisplay,
            'total_expenses_amount' => $totalExpensesAmount,
            'total_stock_kg' => $totalStockKg,
            'total_stock_kg_val' => $totalStockKgVal,
            'total_stock_pcs' => $totalStockPcsVal,
            'total_stock_display' => $totalStockDisplay,
            'estimated_stock_value' => $estimatedStockValue,
            'net_profit_period' => ($totalSalesProfit - $totalExpensesAmount),
            'top_stock_items' => $topStockItems,
            'low_stock_items' => $lowStockItems,
            'chart' => [
                'labels' => $chartLabels,
                'purchases' => $chartPurchases,
                'weights' => $chartWeights,
            ],
            'recent_transactions' => $recentTransactions,
            'recent_activities' => $recentActivities,
        ];
    }

    /**
     * Resolve date range from string filter keywords.
     */
    public function resolveDateRange(string $period, ?string $startDate = null, ?string $endDate = null): array
    {
        $now = Carbon::now();

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'custom' => [
                $startDate ? Carbon::parse($startDate)->startOfDay() : $now->copy()->startOfMonth(),
                $endDate ? Carbon::parse($endDate)->endOfDay() : $now->copy()->endOfDay(),
            ],
            default => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
        };
    }
}

