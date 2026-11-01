<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Models\BusinessSetting;
use App\Models\PaymentMethod;
use App\Models\PurchaseTransaction;
use App\Models\ScrapItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseTransactionService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PurchaseTransactionController extends Controller
{
    public function __construct(
        protected PurchaseTransactionService $purchaseService
    ) {}

    /**
     * Cashier Screen - Fast, ergonomic POS for scrap intake & weighing.
     */
    public function create(): View
    {
        $scrapItems = ScrapItem::with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $suppliers = Supplier::where('is_active', true)
            ->withSum(['debts as total_debt' => function ($q) {
                $q->where('remaining_amount', '>', 0);
            }], 'remaining_amount')
            ->orderBy('name')
            ->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        $defaultPrinter = BusinessSetting::get('default_printer_type', 'thermal_58');

        return view('purchases.create', compact('scrapItems', 'suppliers', 'paymentMethods', 'defaultPrinter'));
    }

    /**
     * Store new purchase transaction.
     */
    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        try {
            $transaction = $this->purchaseService->createPurchase(
                $request->validated(),
                Auth::user()
            );

            return redirect()->route('purchases.show', $transaction)
                ->with('success', "Transaksi {$transaction->invoice_number} berhasil disimpan!")
                ->with('auto_print', true);
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Transaction History List.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $status = $request->query('status');
        $cashierId = $request->query('cashier_id');

        $query = PurchaseTransaction::with(['user', 'supplier', 'paymentMethod', 'items'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_number', 'LIKE', "%{$search}%")
                        ->orWhere('supplier_name', 'LIKE', "%{$search}%");
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
            ->when($cashierId, function ($q) use ($cashierId) {
                $q->where('user_id', $cashierId);
            })
            ->latest('transaction_date');

        $transactions = $query->paginate(15)->withQueryString();
        $cashiers = User::where('is_active', true)->get();

        return view('purchases.index', compact('transactions', 'search', 'startDate', 'endDate', 'status', 'cashierId', 'cashiers'));
    }

    /**
     * Show single purchase detail.
     */
    public function show(PurchaseTransaction $purchase): View
    {
        $purchase->load(['items.scrapItem.category', 'user', 'supplier.debts', 'paymentMethod', 'cancelledByUser', 'debtPayments.debt']);
        $settings = BusinessSetting::pluck('value', 'key')->all();

        return view('purchases.show', compact('purchase', 'settings'));
    }

    /**
     * Print thermal / A4 receipt view.
     */
    public function printReceipt(Request $request, PurchaseTransaction $purchase): View
    {
        $purchase->load(['items.scrapItem', 'user', 'supplier.debts', 'paymentMethod', 'debtPayments.debt']);
        $format = $request->query('format', BusinessSetting::get('default_printer_type', 'thermal_58'));
        $settings = BusinessSetting::pluck('value', 'key')->all();

        return view('purchases.print', compact('purchase', 'format', 'settings'));
    }

    /**
     * Cancel / void transaction with stock reversal.
     */
    public function cancel(Request $request, PurchaseTransaction $purchase): RedirectResponse
    {
        $request->validate([
            'cancel_reason' => 'required|string|min:3|max:255',
        ], [
            'cancel_reason.required' => 'Alasan pembatalan transaksi wajib diisi.',
        ]);

        try {
            $this->purchaseService->cancelPurchase($purchase, Auth::user(), $request->input('cancel_reason'));
            return back()->with('success', "Transaksi {$purchase->invoice_number} berhasil dibatalkan dan stok dikembalikan.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

