<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $suppliers = Supplier::withCount('purchaseTransactions')
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $data['code'] = 'SUP-' . strtoupper(substr(uniqid(), -5));
        $data['is_active'] = true;

        $supplier = Supplier::create($data);

        ActivityLog::record(
            action: 'supplier_created',
            subject: $supplier,
            description: "Menambahkan data pemasok baru: {$supplier->name}"
        );

        return back()->with('success', "Pemasok '{$supplier->name}' berhasil ditambahkan.");
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $supplier->update($data);

        ActivityLog::record(
            action: 'supplier_updated',
            subject: $supplier,
            description: "Memperbarui data pemasok: {$supplier->name}"
        );

        return back()->with('success', "Data pemasok '{$supplier->name}' berhasil diperbarui.");
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchaseTransactions()->exists()) {
            return back()->with('error', "Pemasok '{$supplier->name}' tidak dapat dihapus karena sudah memiliki riwayat transaksi.");
        }

        $name = $supplier->name;
        $supplier->delete();

        ActivityLog::record(
            action: 'supplier_deleted',
            description: "Menghapus pemasok: {$name}"
        );

        return back()->with('success', "Pemasok '{$name}' berhasil dihapus.");
    }
}

