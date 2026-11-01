<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BusinessSetting;
use App\Models\PaymentMethod;
use App\Models\SalarySlip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SalarySlipController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->query('period_month');
        $search = $request->query('search');

        $query = SalarySlip::with(['employeeUser', 'creator', 'paymentMethod'])
            ->when($period, function ($q) use ($period) {
                $q->where('period_month', $period);
            })
            ->when($search, function ($q) use ($search) {
                $q->where('employee_name', 'LIKE', "%{$search}%")
                    ->orWhere('slip_number', 'LIKE', "%{$search}%");
            })
            ->latest('payment_date');

        $slips = $query->paginate(15)->withQueryString();
        $totalPayroll = (float) SalarySlip::when($period, fn($q) => $q->where('period_month', $period))->sum('net_salary');

        // Available periods for filter
        $periods = SalarySlip::select('period_month')->distinct()->orderBy('period_month', 'desc')->pluck('period_month');

        return view('salary_slips.index', compact('slips', 'totalPayroll', 'periods', 'period', 'search'));
    }

    public function create(): View
    {
        $users = User::where('is_active', true)->orderBy('name')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        $currentMonth = Carbon::now()->isoFormat('MMMM Y');

        return view('salary_slips.create', compact('users', 'paymentMethods', 'currentMonth'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_name' => 'required|string|max:150',
            'user_id' => 'nullable|exists:users,id',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'period_month' => 'required|string|max:50',
            'payment_date' => 'required|date',
            'basic_salary' => 'required|numeric|min:0',
            'allowance_amount' => 'nullable|numeric|min:0',
            'bonus_amount' => 'nullable|numeric|min:0',
            'debt_deduction' => 'nullable|numeric|min:0',
            'other_deduction' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $basic = (float) $validated['basic_salary'];
        $allowance = (float) ($validated['allowance_amount'] ?? 0);
        $bonus = (float) ($validated['bonus_amount'] ?? 0);
        $debt = (float) ($validated['debt_deduction'] ?? 0);
        $other = (float) ($validated['other_deduction'] ?? 0);

        $totalIncome = $basic + $allowance + $bonus;
        $totalDeduction = $debt + $other;
        $netSalary = max(0, $totalIncome - $totalDeduction);

        $date = Carbon::parse($validated['payment_date']);
        $prefix = 'SLIP-' . $date->format('ymd') . '-';
        $latest = SalarySlip::where('slip_number', 'LIKE', $prefix . '%')->orderBy('id', 'desc')->value('slip_number');
        $num = $latest ? ((int) substr($latest, strlen($prefix)) + 1) : 1;
        $slipNumber = $prefix . str_pad((string) $num, 3, '0', STR_PAD_LEFT);

        $slip = SalarySlip::create([
            'slip_number' => $slipNumber,
            'employee_name' => $validated['employee_name'],
            'user_id' => $validated['user_id'] ?? null,
            'created_by' => Auth::id(),
            'payment_method_id' => $validated['payment_method_id'] ?? null,
            'period_month' => $validated['period_month'],
            'payment_date' => $date,
            'basic_salary' => $basic,
            'allowance_amount' => $allowance,
            'bonus_amount' => $bonus,
            'total_income' => $totalIncome,
            'debt_deduction' => $debt,
            'other_deduction' => $other,
            'total_deduction' => $totalDeduction,
            'net_salary' => $netSalary,
            'status' => 'paid',
            'notes' => $validated['notes'] ?? null,
        ]);

        ActivityLog::record(
            action: 'salary_slip_created',
            subject: $slip,
            description: "Membuat slip gaji {$slip->slip_number} untuk {$slip->employee_name} senilai Rp " . number_format($netSalary, 0, ',', '.')
        );

        return redirect()->route('salary-slips.show', $slip)->with('success', "Slip gaji {$slip->slip_number} berhasil dibuat!");
    }

    public function show(SalarySlip $salarySlip): View
    {
        $salarySlip->load(['employeeUser', 'creator', 'paymentMethod']);
        $settings = BusinessSetting::pluck('value', 'key')->all();

        return view('salary_slips.show', compact('salarySlip', 'settings'));
    }

    public function print(SalarySlip $salarySlip): View
    {
        $salarySlip->load(['employeeUser', 'creator', 'paymentMethod']);
        $settings = BusinessSetting::pluck('value', 'key')->all();

        return view('salary_slips.print', compact('salarySlip', 'settings'));
    }

    public function destroy(SalarySlip $salarySlip): RedirectResponse
    {
        $num = $salarySlip->slip_number;
        $salarySlip->delete();

        ActivityLog::record(
            action: 'salary_slip_deleted',
            description: "Menghapus slip gaji {$num}"
        );

        return redirect()->route('salary-slips.index')->with('success', "Slip gaji {$num} berhasil dihapus.");
    }
}

