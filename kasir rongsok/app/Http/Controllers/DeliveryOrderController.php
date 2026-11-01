<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BusinessSetting;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\SalesTransaction;
use App\Models\ScrapItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeliveryOrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $status = $request->query('status');

        $query = DeliveryOrder::with(['user', 'salesTransaction', 'items'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('delivery_number', 'LIKE', "%{$search}%")
                        ->orWhere('recipient_name', 'LIKE', "%{$search}%")
                        ->orWhere('vehicle_number', 'LIKE', "%{$search}%")
                        ->orWhere('driver_name', 'LIKE', "%{$search}%");
                });
            })
            ->when($startDate, function ($q) use ($startDate) {
                $q->whereDate('delivery_date', '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                $q->whereDate('delivery_date', '<=', $endDate);
            })
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->latest('delivery_date');

        $orders = $query->paginate(15)->withQueryString();

        return view('delivery_orders.index', compact('orders', 'search', 'startDate', 'endDate', 'status'));
    }

    public function create(Request $request): View
    {
        $salesTxId = $request->query('sales_transaction_id');
        $salesTx = null;
        if ($salesTxId) {
            $salesTx = SalesTransaction::with('items.scrapItem')->find($salesTxId);
        }

        $scrapItems = ScrapItem::where('is_active', true)->orderBy('name')->get();
        $recentSales = SalesTransaction::where('status', 'completed')->latest()->take(10)->get();

        return view('delivery_orders.create', compact('scrapItems', 'recentSales', 'salesTx'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sales_transaction_id' => 'nullable|exists:sales_transactions,id',
            'delivery_date' => 'required|date',
            'recipient_name' => 'required|string|max:150',
            'recipient_address' => 'nullable|string|max:255',
            'recipient_phone' => 'nullable|string|max:30',
            'driver_name' => 'required|string|max:100',
            'driver_phone' => 'nullable|string|max:30',
            'vehicle_number' => 'required|string|max:20',
            'vehicle_type' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.scrap_item_id' => 'nullable|exists:scrap_items,id',
            'items.*.item_name' => 'required|string|max:150',
            'items.*.weight' => 'required|numeric|gt:0',
            'items.*.package_qty' => 'nullable|integer|min:0',
            'items.*.notes' => 'nullable|string|max:255',
        ], [
            'recipient_name.required' => 'Nama penerima / pabrik tujuan pengiriman wajib diisi.',
            'driver_name.required' => 'Nama sopir armada wajib diisi.',
            'vehicle_number.required' => 'Nomor plat polisi kendaraan armada wajib diisi.',
            'items.required' => 'Daftar muatan barang pada surat jalan wajib diisi.',
        ]);

        try {
            $deliveryOrder = DB::transaction(function () use ($validated) {
                $date = Carbon::parse($validated['delivery_date']);
                $prefix = 'SJ-' . $date->format('ymd') . '-';
                $latest = DeliveryOrder::where('delivery_number', 'LIKE', $prefix . '%')->orderBy('id', 'desc')->value('delivery_number');
                $num = $latest ? ((int) substr($latest, strlen($prefix)) + 1) : 1;
                $deliveryNumber = $prefix . str_pad((string) $num, 3, '0', STR_PAD_LEFT);

                $totalWeight = 0;
                $totalPackages = 0;

                foreach ($validated['items'] as $item) {
                    $totalWeight += (float) $item['weight'];
                    $totalPackages += (int) ($item['package_qty'] ?? 0);
                }

                $order = DeliveryOrder::create([
                    'delivery_number' => $deliveryNumber,
                    'sales_transaction_id' => $validated['sales_transaction_id'] ?? null,
                    'delivery_date' => $date,
                    'recipient_name' => $validated['recipient_name'],
                    'recipient_address' => $validated['recipient_address'] ?? null,
                    'recipient_phone' => $validated['recipient_phone'] ?? null,
                    'driver_name' => $validated['driver_name'],
                    'driver_phone' => $validated['driver_phone'] ?? null,
                    'vehicle_number' => strtoupper($validated['vehicle_number']),
                    'vehicle_type' => $validated['vehicle_type'] ?? 'Truk',
                    'total_weight' => $totalWeight,
                    'total_packages' => $totalPackages,
                    'status' => 'sent',
                    'notes' => $validated['notes'] ?? null,
                    'user_id' => Auth::id(),
                ]);

                foreach ($validated['items'] as $item) {
                    DeliveryOrderItem::create([
                        'delivery_order_id' => $order->id,
                        'scrap_item_id' => $item['scrap_item_id'] ?? null,
                        'item_name' => $item['item_name'],
                        'weight' => (float) $item['weight'],
                        'package_qty' => (int) ($item['package_qty'] ?? 0),
                        'notes' => $item['notes'] ?? null,
                    ]);
                }

                ActivityLog::record(
                    action: 'delivery_order_created',
                    subject: $order,
                    description: "Membuat Surat Jalan {$order->delivery_number} tujuan {$order->recipient_name} dengan armada {$order->vehicle_number}"
                );

                return $order;
            });

            return redirect()->route('delivery-orders.show', $deliveryOrder)->with('success', "Surat Jalan {$deliveryOrder->delivery_number} berhasil dibuat!");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(DeliveryOrder $deliveryOrder): View
    {
        $deliveryOrder->load(['user', 'salesTransaction', 'items']);
        $settings = BusinessSetting::pluck('value', 'key')->all();

        return view('delivery_orders.show', compact('deliveryOrder', 'settings'));
    }

    public function print(DeliveryOrder $deliveryOrder): View
    {
        $deliveryOrder->load(['user', 'salesTransaction', 'items']);
        $settings = BusinessSetting::pluck('value', 'key')->all();

        return view('delivery_orders.print', compact('deliveryOrder', 'settings'));
    }

    public function destroy(DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $num = $deliveryOrder->delivery_number;
        $deliveryOrder->delete();

        ActivityLog::record(
            action: 'delivery_order_deleted',
            description: "Menghapus surat jalan {$num}"
        );

        return redirect()->route('delivery-orders.index')->with('success', "Surat jalan {$num} berhasil dihapus.");
    }
}

