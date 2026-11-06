<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Driver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $query = Driver::query()->latest('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('vehicle_number', 'LIKE', "%{$search}%")
                    ->orWhere('bank_name', 'LIKE', "%{$search}%")
                    ->orWhere('bank_account_number', 'LIKE', "%{$search}%")
                    ->orWhere('bank_account_name', 'LIKE', "%{$search}%");
            });
        }

        $drivers = $query->paginate(15)->withQueryString();

        return view('drivers.index', compact('drivers', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'vehicle_number' => 'nullable|string|max:30',
            'vehicle_type' => 'nullable|string|max:60',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:60',
            'bank_account_name' => 'nullable|string|max:150',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $latestId = Driver::max('id') ?? 0;
        $code = 'DRV-' . str_pad((string) ($latestId + 1), 3, '0', STR_PAD_LEFT);
        while (Driver::where('code', $code)->exists()) {
            $code = 'DRV-' . strtoupper(substr(uniqid(), -5));
        }
        $data['code'] = $code;
        $data['is_active'] = true;

        $driver = Driver::create($data);

        ActivityLog::record(
            action: 'driver_created',
            subject: $driver,
            description: "Menambahkan data sopir baru '{$driver->name}' ({$driver->code})" . ($driver->hasBankDetails() ? " dengan rekening {$driver->bankDisplay()}" : '')
        );

        return back()->with('success', "Data sopir/driver {$driver->name} berhasil ditambahkan.");
    }

    public function update(Request $request, Driver $driver): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'vehicle_number' => 'nullable|string|max:30',
            'vehicle_type' => 'nullable|string|max:60',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:60',
            'bank_account_name' => 'nullable|string|max:150',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $driver->update($data);

        ActivityLog::record(
            action: 'driver_updated',
            subject: $driver,
            description: "Memperbarui data sopir '{$driver->name}' ({$driver->code})" . ($driver->hasBankDetails() ? " Rekening: {$driver->bankDisplay()}" : '')
        );

        return back()->with('success', "Data sopir/driver {$driver->name} berhasil diperbarui.");
    }

    public function destroy(Driver $driver): RedirectResponse
    {
        $hasOrders = $driver->deliveryOrders()->exists();

        if ($hasOrders) {
            $driver->update(['is_active' => false]);
            return back()->with('success', "Sopir {$driver->name} memiliki riwayat surat jalan pengiriman, status dinonaktifkan.");
        }

        $driverName = $driver->name;
        $driver->delete();

        ActivityLog::record(
            action: 'driver_deleted',
            subject: $driver,
            description: "Menghapus data sopir '{$driverName}'"
        );

        return back()->with('success', "Data sopir {$driverName} berhasil dihapus.");
    }
}
