<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $categoryId = $request->query('category_id');
        $search = $request->query('search');
        $allDates = $request->query('all_dates') == '1';

        // Default to today (tanggal saat itu) so historical expenses are not accumulated unless requested
        if ($allDates) {
            $startDate = null;
            $endDate = null;
        } elseif ($request->has('start_date') || $request->has('end_date')) {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
        } else {
            $startDate = Carbon::today()->toDateString();
            $endDate = Carbon::today()->toDateString();
        }

        $query = Expense::with(['category', 'user', 'paymentMethod'])
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->where('expense_category_id', $categoryId);
            })
            ->when($startDate, function ($q) use ($startDate) {
                $q->whereDate('expense_date', '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                $q->whereDate('expense_date', '<=', $endDate);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('description', 'LIKE', "%{$search}%")
                        ->orWhere('recipient', 'LIKE', "%{$search}%")
                        ->orWhere('expense_number', 'LIKE', "%{$search}%");
                });
            })
            ->latest('expense_date');

        $expenses = $query->paginate(15)->withQueryString();
        $categories = ExpenseCategory::orderBy('name')->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        $totalAmount = (float) Expense::when($startDate, fn($q) => $q->whereDate('expense_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('expense_date', '<=', $endDate))
            ->when($categoryId, fn($q) => $q->where('expense_category_id', $categoryId))
            ->sum('amount');

        return view('expenses.index', compact('expenses', 'categories', 'paymentMethods', 'categoryId', 'startDate', 'endDate', 'search', 'totalAmount', 'allDates'));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (!empty($request->input('new_category_name'))) {
            $cat = ExpenseCategory::firstOrCreate([
                'name' => trim($request->input('new_category_name')),
            ]);
            $data['expense_category_id'] = $cat->id;
        }

        unset($data['new_category_name']);

        $date = Carbon::parse($data['expense_date']);

        $prefix = 'EXP-' . $date->format('ymd') . '-';
        $latest = Expense::where('expense_number', 'LIKE', $prefix . '%')
            ->orderBy('id', 'desc')
            ->value('expense_number');

        $num = $latest ? ((int) substr($latest, strlen($prefix)) + 1) : 1;
        $data['expense_number'] = $prefix . str_pad((string) $num, 4, '0', STR_PAD_LEFT);
        $data['user_id'] = Auth::id();

        $expense = Expense::create($data);

        ActivityLog::record(
            action: 'expense_created',
            subject: $expense,
            description: "Mencatat pengeluaran operasional: {$expense->description} senilai Rp " . number_format($expense->amount, 0, ',', '.')
        );

        return back()->with('success', "Pengeluaran {$expense->expense_number} berhasil dicatat.");
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $desc = $expense->description;
        $expense->delete();

        ActivityLog::record(
            action: 'expense_deleted',
            description: "Menghapus pengeluaran: {$desc}"
        );

        return back()->with('success', 'Pengeluaran berhasil dihapus.');
    }
}

