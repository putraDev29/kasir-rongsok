<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use App\Models\ScrapItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Services\FormatHelper;
use App\Services\StockService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $query = ScrapItem::with(['category', 'stockBalance', 'priceStocks'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%");
                });
            })
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });

        if ($lowStockOnly) {
            $query->whereHas('stockBalance', function ($q) {
                $q->whereRaw('stock_balances.current_stock <= scrap_items.min_stock_alert');
            })->where('min_stock_alert', '>', 0);
        }

        $items = $query->paginate(15)->withQueryString();
        $categories = ItemCategory::where('is_active', true)->get();

        // Overall stock totals
        $totalStockKg = (float) StockBalance::sum('current_stock');
        $totalValuation = (float) StockBalance::selectRaw('SUM(current_stock * average_buy_price) as val')->value('val');

        return view('stock.index', compact('items', 'categories', 'search', 'categoryId', 'lowStockOnly', 'totalStockKg', 'totalValuation'));
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

