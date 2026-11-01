<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesRequest;
use App\Models\BusinessSetting;
use App\Models\PaymentMethod;
use App\Models\SalesTransaction;
use App\Models\ScrapItem;
use App\Services\SalesTransactionService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SalesTransactionController extends Controller
{
    public function __construct(
        protected SalesTransactionService $salesService
    ) {}

    public function index(Request $request): View
    {
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $status = $request->query('status');

        $query = SalesTransaction::with(['user', 'paymentMethod', 'items'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_number', 'LIKE', "%{$search}%")
                        ->orWhere('buyer_name', 'LIKE', "%{$search}%");
                });
            })
            ->when($startDate, function ($q) use ($startDate) {
                $q->whereDate('transaction_date', '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                $q->whereDate('transaction_date', '<=', $endDate);
            })
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->latest('transaction_date');

        $transactions = $query->paginate(15)->withQueryString();

        $totalSales = (float) SalesTransaction::where('status', 'completed')->sum('final_amount');
        $totalProfit = (float) SalesTransaction::where('status', 'completed')->sum('estimated_profit');

        return view('sales.index', compact('transactions', 'search', 'startDate', 'endDate', 'status', 'totalSales', 'totalProfit'));
    }

    public function create(): View
    {
        $scrapItems = ScrapItem::with('stockBalance')
            ->where('is_active', true)
            ->whereHas('stockBalance', function ($q) {
                $q->where('current_stock', '>', 0);
            })
            ->orderBy('name')
            ->get();

        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        return view('sales.create', compact('scrapItems', 'paymentMethods'));
    }

    public function store(StoreSalesRequest $request): RedirectResponse
    {
        try {
            $transaction = $this->salesService->createSale(
                $request->validated(),
                Auth::user()
            );

            return redirect()->route('sales.show', $transaction)
                ->with('success', "Penjualan rongsok {$transaction->invoice_number} berhasil dicatat!");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(SalesTransaction $sale): View
    {
        $sale->load(['items.scrapItem', 'user', 'paymentMethod', 'cancelledByUser']);
        $settings = BusinessSetting::pluck('value', 'key')->all();

        return view('sales.show', compact('sale', 'settings'));
    }

    public function printInvoice(Request $request, SalesTransaction $sale): View
    {
        $sale->load(['items.scrapItem', 'user', 'paymentMethod']);
        $settings = BusinessSetting::pluck('value', 'key')->all();

        return view('sales.print', compact('sale', 'settings'));
    }

    public function cancel(Request $request, SalesTransaction $sale): RedirectResponse
    {
        $request->validate([
            'cancel_reason' => 'required|string|min:3|max:255',
        ], [
            'cancel_reason.required' => 'Alasan pembatalan penjualan wajib diisi.',
        ]);

        try {
            $this->salesService->cancelSale($sale, Auth::user(), $request->input('cancel_reason'));
            return back()->with('success', "Penjualan {$sale->invoice_number} berhasil dibatalkan dan stok dikembalikan.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

