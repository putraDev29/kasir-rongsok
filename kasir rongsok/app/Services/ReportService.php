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

        // Sales within period
        $salesQuery = SalesTransaction::where('status', SalesTransaction::STATUS_COMPLETED)
            ->whereBetween('transaction_date', [$start, $end]);

        $totalSalesAmount = (float) $salesQuery->sum('final_amount');
        $totalSalesWeight = (float) $salesQuery->sum('total_weight');
        $totalSalesProfit = (float) $salesQuery->sum('estimated_profit');

        // Expenses within period
        $totalExpensesAmount = (float) Expense::whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])->sum('amount');

        // Current stock inventory metrics (snapshot right now)
        $totalStockKg = (float) StockBalance::sum('current_stock');
        $estimatedStockValue = (float) DB::table('stock_balances')
            ->selectRaw('SUM(current_stock * average_buy_price) as total_val')
            ->value('total_val');

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
            'total_sales_amount' => $totalSalesAmount,
            'total_sales_weight' => $totalSalesWeight,
            'total_sales_profit' => $totalSalesProfit,
            'total_expenses_amount' => $totalExpensesAmount,
            'total_stock_kg' => $totalStockKg,
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

