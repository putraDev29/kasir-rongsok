<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use App\Models\ScrapItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Services\FormatHelper;
use App\Services\StockService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function index(Request $request): View
    {
        $search = $request->query('search');
        $categoryId = $request->query('category_id');
        $lowStockOnly = $request->boolean('low_stock');
        $hasDateFilter = $request->filled('month') || $request->filled('start_date') || $request->filled('end_date') || ($request->has('month') && $request->filled('year'));

        if ($hasDateFilter) {
            [$startDate, $endDate, $selectedMonth, $selectedYear] = FormatHelper::resolveDateFilter($request);
        } else {
            [$startDate, $endDate, $selectedMonth, $selectedYear] = [null, null, null, null];
        }

        $query = ScrapItem::with(['category', 'stockBalance', 'priceStocks'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%");
                });
            })
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->orderBy('name', 'asc');

        if ($lowStockOnly) {
            $query->whereHas('stockBalance', function ($q) {
                $q->whereRaw('stock_balances.current_stock <= scrap_items.min_stock_alert');
            })->where('min_stock_alert', '>', 0);
        }

        $items = $query->paginate(15)->withQueryString();
        $categories = ItemCategory::where('is_active', true)->orderBy('name')->get();

        $isFilteredByDate = !empty($startDate) && !empty($endDate);
        $periodIncomingStocks = [];
        $periodIncomingAvgPrices = [];

        if ($isFilteredByDate) {
            $incomingData = DB::table('purchase_transaction_items')
                ->join('purchase_transactions', 'purchase_transaction_items.purchase_transaction_id', '=', 'purchase_transactions.id')
                ->where('purchase_transactions.status', 'completed')
                ->whereDate('purchase_transactions.transaction_date', '>=', $startDate)
                ->whereDate('purchase_transactions.transaction_date', '<=', $endDate)
                ->select(
                    'purchase_transaction_items.scrap_item_id',
                    DB::raw('SUM(purchase_transaction_items.net_weight) as total_incoming'),
                    DB::raw('SUM(purchase_transaction_items.subtotal) as total_cost')
                )
                ->groupBy('purchase_transaction_items.scrap_item_id')
                ->get();

            foreach ($incomingData as $inc) {
                $periodIncomingStocks[$inc->scrap_item_id] = (float) $inc->total_incoming;
                $periodIncomingAvgPrices[$inc->scrap_item_id] = $inc->total_incoming > 0 
                    ? (float) ($inc->total_cost / $inc->total_incoming) 
                    : 0;
            }

            $totalStockKg = array_sum($periodIncomingStocks);
            $totalValuation = (float) DB::table('purchase_transaction_items')
                ->join('purchase_transactions', 'purchase_transaction_items.purchase_transaction_id', '=', 'purchase_transactions.id')
                ->where('purchase_transactions.status', 'completed')
                ->whereDate('purchase_transactions.transaction_date', '>=', $startDate)
                ->whereDate('purchase_transactions.transaction_date', '<=', $endDate)
                ->sum('purchase_transaction_items.subtotal');
        } else {
            // Overall stock totals
            $totalStockKg = (float) StockBalance::sum('current_stock');
            $totalValuation = (float) StockBalance::selectRaw('SUM(current_stock * average_buy_price) as val')->value('val');
        }

        $periodLabel = '';
        if ($isFilteredByDate) {
            if ($selectedMonth && $selectedYear) {
                $periodLabel = Carbon::create($selectedYear, $selectedMonth, 1)->translatedFormat('F Y');
            } else {
                $periodLabel = Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y');
            }
        }

        return view('stock.index', compact(
            'items', 'categories', 'search', 'categoryId', 'lowStockOnly', 
            'totalStockKg', 'totalValuation', 'startDate', 'endDate', 
            'selectedMonth', 'selectedYear', 'isFilteredByDate', 
            'periodIncomingStocks', 'periodIncomingAvgPrices', 'periodLabel'
        ));
    }

    public function movements(Request $request): View
    {
        $scrapItemId = $request->query('scrap_item_id');
        $type = $request->query('type');
        [$startDate, $endDate, $selectedMonth, $selectedYear] = FormatHelper::resolveDateFilter($request);

        $query = StockMovement::with(['scrapItem', 'user'])
            ->when($scrapItemId, function ($q) use ($scrapItemId) {
                $q->where('scrap_item_id', $scrapItemId);
            })
            ->when($type, function ($q) use ($type) {
                $q->where('type', $type);
            })
            ->when($startDate, function ($q) use ($startDate) {
                $q->whereDate('created_at', '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                $q->whereDate('created_at', '<=', $endDate);
            })
            ->latest();

        $movements = $query->paginate(20)->withQueryString();
        $scrapItems = ScrapItem::orderBy('name')->get();

        return view('stock.movements', compact('movements', 'scrapItems', 'scrapItemId', 'type', 'startDate', 'endDate', 'selectedMonth', 'selectedYear'));
    }

    public function adjust(Request $request, ScrapItem $scrapItem): RedirectResponse
    {
        $request->validate([
            'new_stock' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
        ], [
            'reason.required' => 'Alasan penyesuaian stok (opname) wajib dicatat.',
        ]);

        try {
            $this->stockService->adjustStock(
                $scrapItem,
                (float) $request->input('new_stock'),
                $request->input('reason'),
                Auth::user()
            );

            return back()->with('success', "Stok barang '{$scrapItem->name}' berhasil disesuaikan.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

