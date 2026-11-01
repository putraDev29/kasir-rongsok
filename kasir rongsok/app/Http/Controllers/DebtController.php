<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\PaymentMethod;
use App\Models\Supplier;
use App\Services\DebtService;
use Exception;
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
        $search = $request->query('search');

        $query = Debt::with(['supplier', 'user', 'payments'])
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($type, function ($q) use ($type) {
                $q->where('borrower_type', $type);
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

        return view('debts.index', compact('debts', 'suppliers', 'paymentMethods', 'status', 'type', 'search', 'totalInitial', 'totalPaid', 'totalRemaining'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'borrower_name' => 'required|string|max:150',
            'borrower_phone' => 'nullable|string|max:30',
            'borrower_type' => 'required|in:supplier,karyawan,lainnya',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:date',
            'initial_amount' => 'required|numeric|gt:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $debt = $this->debtService->createDebt($validated, Auth::user());
            return back()->with('success', "Pinjaman modal {$debt->debt_number} untuk {$debt->borrower_name} berhasil dicatat.");
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
}

