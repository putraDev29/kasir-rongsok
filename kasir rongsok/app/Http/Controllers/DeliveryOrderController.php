<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BusinessSetting;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\Driver;
use App\Models\SalesTransaction;
use App\Models\ScrapItem;
use App\Services\FormatHelper;
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

        $rawItems = ScrapItem::with(['stockBalance', 'priceStocks', 'category'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $scrapItems = [];
        foreach ($rawItems as $item) {
            foreach ($item->getPriceTierOptions(onlyWithStock: false) as $opt) {
                $scrapItems[] = $opt;
            }
        }
        $recentSales = SalesTransaction::where('delivery_type', SalesTransaction::DELIVERY_TYPE_DELIVERY)
            ->whereNull('delivery_order_id')
            ->where('status', '!=', SalesTransaction::STATUS_CANCELLED)
            ->latest()
            ->take(15)
            ->get();

        $drivers = Driver::where('is_active', true)->orderBy('name')->get();

        return view('delivery_orders.create', compact('scrapItems', 'recentSales', 'salesTx', 'drivers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sales_transaction_id' => 'nullable|exists:sales_transactions,id',
            'delivery_date' => 'required|date',
            'recipient_name' => 'required|string|max:150',
            'recipient_address' => 'nullable|string|max:255',
            'recipient_phone' => 'nullable|string|max:30',
            'driver_id' => 'nullable|exists:drivers,id',
            'driver_name' => 'required|string|max:100',
            'driver_phone' => 'nullable|string|max:30',
            'driver_bank_name' => 'nullable|string|max:100',
            'driver_bank_account_number' => 'nullable|string|max:60',
            'driver_bank_account_name' => 'nullable|string|max:150',
            'save_to_driver_bank' => 'nullable|boolean',
            'vehicle_number' => 'required|string|max:20',
            'vehicle_type' => 'nullable|string|max:50',
            'shipping_cost' => 'nullable|numeric|min:0',
            'loading_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.scrap_item_id' => 'nullable|exists:scrap_items,id',
            'items.*.item_name' => 'required|string|max:150',
            'items.*.unit_type' => 'nullable|string|in:kg,pcs,satuan',
            'items.*.item_type_name' => 'nullable|string|max:150',
            'items.*.quantity' => 'nullable|numeric|min:0.01',
            'items.*.weight' => 'nullable|numeric|min:0',
            'items.*.package_qty' => 'nullable|integer|min:0',
            'items.*.price_per_kg' => 'nullable|numeric|min:0',
            'items.*.subtotal' => 'nullable|numeric|min:0',
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
                $deliveryNumber = FormatHelper::generateDeliveryOrderNumber($date);

                $totalWeight = 0;
                $totalPackages = 0;

                foreach ($validated['items'] as $item) {
                    $totalWeight += (float) ($item['weight'] ?? 0);
                    $totalPackages += (int) ($item['package_qty'] ?? 0);
                }

                $driverId = $validated['driver_id'] ?? null;
                $driverName = trim($validated['driver_name']);
                $driverPhone = $validated['driver_phone'] ?? null;
                $vehicleNumber = strtoupper(trim($validated['vehicle_number']));
                $vehicleType = $validated['vehicle_type'] ?? 'Truk';
                $driverBankName = trim($validated['driver_bank_name'] ?? '');
                $driverBankAccNo = trim($validated['driver_bank_account_number'] ?? '');
                $driverBankAccName = trim($validated['driver_bank_account_name'] ?? '');

                $driver = null;
                if ($driverId) {
                    $driver = Driver::find($driverId);
                } elseif (!empty($driverName)) {
                    $driver = Driver::where('name', $driverName)->first();
                    if (!$driver) {
                        $latestId = Driver::max('id') ?? 0;
                        $code = 'DRV-' . str_pad((string) ($latestId + 1), 3, '0', STR_PAD_LEFT);
                        while (Driver::where('code', $code)->exists()) {
                            $code = 'DRV-' . strtoupper(substr(uniqid(), -5));
                        }
                        $driver = Driver::create([
                            'code' => $code,
                            'name' => $driverName,
                            'phone' => $driverPhone,
                            'vehicle_number' => $vehicleNumber,
                            'vehicle_type' => $vehicleType,
                            'bank_name' => $driverBankName ?: null,
                            'bank_account_number' => $driverBankAccNo ?: null,
                            'bank_account_name' => $driverBankAccName ?: null,
                            'is_active' => true,
                        ]);
                    }
                    $driverId = $driver->id;
                }

                if ($driver) {
                    $driverId = $driver->id;
                    $driverName = $driver->name;
                    $hasBankInfo = !empty($driverBankName) || !empty($driverBankAccNo) || !empty($driverBankAccName);
                    $shouldSaveToDriver = !empty($validated['save_to_driver_bank']) || empty($driver->bank_account_number);
                    if ($hasBankInfo && $shouldSaveToDriver) {
                        $driver->update(array_filter([
                            'bank_name' => $driverBankName ?: $driver->bank_name,
                            'bank_account_number' => $driverBankAccNo ?: $driver->bank_account_number,
                            'bank_account_name' => $driverBankAccName ?: $driver->bank_account_name,
                            'phone' => $driverPhone ?: $driver->phone,
                            'vehicle_number' => $vehicleNumber ?: $driver->vehicle_number,
                            'vehicle_type' => $vehicleType ?: $driver->vehicle_type,
                        ], fn($val) => !is_null($val) && $val !== ''));
                    }
                }

                $order = DeliveryOrder::create([
                    'delivery_number' => $deliveryNumber,
                    'sales_transaction_id' => $validated['sales_transaction_id'] ?? null,
                    'driver_id' => $driverId,
                    'delivery_date' => $date,
                    'recipient_name' => $validated['recipient_name'],
                    'recipient_address' => $validated['recipient_address'] ?? null,
                    'recipient_phone' => $validated['recipient_phone'] ?? null,
                    'driver_name' => $driverName,
                    'driver_phone' => $driverPhone,
                    'driver_bank_name' => $driverBankName ?: ($driver?->bank_name ?? null),
                    'driver_bank_account_number' => $driverBankAccNo ?: ($driver?->bank_account_number ?? null),
                    'driver_bank_account_name' => $driverBankAccName ?: ($driver?->bank_account_name ?? null),
                    'vehicle_number' => $vehicleNumber,
                    'vehicle_type' => $vehicleType,
                    'total_weight' => $totalWeight,
                    'shipping_cost' => (float) ($validated['shipping_cost'] ?? 0),
                    'loading_cost' => (float) ($validated['loading_cost'] ?? 0),
                    'total_packages' => $totalPackages,
                    'status' => 'sent',
                    'notes' => $validated['notes'] ?? null,
                    'user_id' => Auth::id(),
                ]);

                foreach ($validated['items'] as $item) {
                    $weight = (float) ($item['weight'] ?? 0);
                    $pricePerKg = (float) ($item['price_per_kg'] ?? 0);
                    $subtotal = (float) ($item['subtotal'] ?? ($pricePerKg * $weight));

                    DeliveryOrderItem::create([
                        'delivery_order_id' => $order->id,
                        'scrap_item_id' => $item['scrap_item_id'] ?? null,
                        'item_name' => $item['item_name'],
                        'unit_type' => $item['unit_type'] ?? 'kg',
                        'item_type_name' => $item['item_type_name'] ?? null,
                        'quantity' => $item['quantity'] ?? 1,
                        'weight' => $weight,
                        'package_qty' => (int) ($item['package_qty'] ?? 0),
                        'price_per_kg' => $pricePerKg,
                        'subtotal' => $subtotal,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }

                // Auto-record operational expenses (ongkos & bongkar muat)
                $order->recordOperationalExpenses();

                ActivityLog::record(
                    action: 'delivery_order_created',
                    subject: $order,
                    description: "Membuat Surat Jalan {$order->delivery_number} tujuan {$order->recipient_name} dengan armada {$order->vehicle_number}"
                );

                if (!empty($validated['sales_transaction_id'])) {
                    $saleTx = SalesTransaction::find($validated['sales_transaction_id']);
                    if ($saleTx) {
                        $saleTx->update([
                            'delivery_order_id' => $order->id,
                            'delivery_type' => SalesTransaction::DELIVERY_TYPE_DELIVERY,
                        ]);
                    }
                }

                return $order;
            });

            return redirect()->route('delivery-orders.show', $deliveryOrder)->with('success', "Surat Jalan {$deliveryOrder->delivery_number} berhasil dibuat!");
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('DeliveryOrder store failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(DeliveryOrder $deliveryOrder): View
    {
        $deliveryOrder->load(['user', 'salesTransaction', 'items']);
        $settings = BusinessSetting::pluck('value', 'key')->all();

        return view('delivery_orders.show', compact('deliveryOrder', 'settings'));
    }

    public function print(Request $request, DeliveryOrder $deliveryOrder): View
    {
        $deliveryOrder->load(['user', 'salesTransaction', 'items']);
        $settings = BusinessSetting::pluck('value', 'key')->all();
        $copy = $request->query('copy', 'all');
        $copies = match($copy) {
            '1' => [1],
            '2' => [2],
            default => [1, 2],
        };

        return view('delivery_orders.print', compact('deliveryOrder', 'settings', 'copies', 'copy'));
    }

    public function pdf(Request $request, DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load(['user', 'salesTransaction', 'items.scrapItem']);
        $settings = BusinessSetting::pluck('value', 'key')->all();
        $copy = $request->query('copy', 'all');
        $copies = match($copy) {
            '1' => [1],
            '2' => [2],
            default => [1, 2],
        };

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('delivery_orders.pdf', compact('deliveryOrder', 'settings', 'copies', 'copy'))
            ->setPaper('a5', 'landscape');

        $suffix = match($copy) {
            '1' => '_Lembar1_Gudang',
            '2' => '_Lembar2_Supir',
            default => '_2Rangkap',
        };
        $filename = "Surat_Jalan_{$deliveryOrder->delivery_number}{$suffix}.pdf";

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    public function previewWa(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load(['user', 'salesTransaction', 'items.scrapItem']);
        $settings = BusinessSetting::pluck('value', 'key')->all();
        $companyName = $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur';
        $companyPhone = $settings['company_phone'] ?? '';

        $lines = [];
        $i = 1;
        foreach ($deliveryOrder->items as $it) {
            $kg = number_format($it->weight, 2, ',', '.');
            $koli = $it->package_qty > 0 ? " ({$it->package_qty} koli)" : "";
            $lines[] = "{$i}. *{$it->item_name}* : *{$kg} kg*{$koli}";
            $i++;
        }
        $itemsStr = count($lines) > 0 ? implode("\n", $lines) : "-";

        $totalKg = number_format($deliveryOrder->total_weight, 2, ',', '.');
        $dateStr = $deliveryOrder->delivery_date->format('d/m/Y');

        $msg = "*SURAT JALAN PENGIRIMAN *\n";
        $msg .= "🏢 *{$companyName}*\n";
        $msg .= "----------------------------------------\n";
        $msg .= "🔖 No. SJ     : *{$deliveryOrder->delivery_number}*\n";
        $msg .= "🗓️ Tanggal    : {$dateStr}\n";
        $msg .= "🏭 Penerima   : *{$deliveryOrder->recipient_name}*\n";
        $msg .= "📍 Tujuan     : {$deliveryOrder->recipient_address}\n";
        $msg .= "----------------------------------------\n";
        $msg .= "🚛 *ARMADA & PENGEMUDI:*\n";
        $msg .= "• Plat Nomor  : *{$deliveryOrder->vehicle_number}* ({$deliveryOrder->vehicle_type})\n";
        $msg .= "• Sopir       : *{$deliveryOrder->driver_name}*\n";
        if ($deliveryOrder->driver_phone) {
            $msg .= "• HP Sopir    : {$deliveryOrder->driver_phone}\n";
        }
        $msg .= "----------------------------------------\n";
        $msg .= "📦 *DAFTAR MUATAN BARANG:*\n{$itemsStr}\n\n";
        $msg .= "⚖️ *TOTAL MUATAN :* *{$totalKg} kg* ({$deliveryOrder->total_packages} Koli)\n";
        $msg .= "----------------------------------------\n";
        $msg .= "📄 *KETERANGAN RANGKAP:*\n";
        $msg .= "• Surat jalan ini dibuat *2 rangkap*:\n";
        $msg .= "  - *Lembar 1* dipegang oleh Gudang\n";
        $msg .= "  - *Lembar 2* dipegang oleh Supir\n";
        $msg .= "----------------------------------------\n";
        if ($companyPhone) {
            $msg .= "📞 Hubungi Depo: {$companyPhone}\n";
        }
        $msg .= "Terima kasih atas kerja samanya! 🙏";

        $filename = "Surat_Jalan_{$deliveryOrder->delivery_number}_2Rangkap.pdf";
        $targetPhone = $deliveryOrder->recipient_phone ?: $deliveryOrder->driver_phone ?: '';

        return response()->json([
            'phone' => $targetPhone,
            'message' => $msg,
            'delivery_number' => $deliveryOrder->delivery_number,
            'filename' => $filename,
            'pdf_filename' => $filename,
            'pdf_url' => route('delivery-orders.pdf', $deliveryOrder),
            'file_description' => 'Dokumen PDF Resmi Surat Jalan Pengiriman (2 Rangkap: Lembar 1 Gudang & Lembar 2 Supir)',
        ]);
    }

    public function sendWa(Request $request, DeliveryOrder $deliveryOrder)
    {
        $phone = $request->input('phone');
        if (empty($phone)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Nomor WhatsApp tujuan wajib diisi.'], 422);
            }
            return back()->with('error', 'Nomor WhatsApp tujuan wajib diisi.');
        }

        $deliveryOrder->load(['user', 'salesTransaction', 'items.scrapItem']);
        $settings = BusinessSetting::pluck('value', 'key')->all();
        $sendPdf = $request->boolean('send_pdf', true);
        $caption = $request->input('message') ?: $request->input('caption') ?: "Terlampir Berkas Dokumen PDF Surat Jalan {$deliveryOrder->delivery_number} (2 Rangkap: Gudang & Supir).";
        $filename = $request->input('filename') ?: ("Surat_Jalan_{$deliveryOrder->delivery_number}_2Rangkap.pdf");

        $waService = app(\App\Services\WhatsAppGatewayService::class);

        if ($sendPdf) {
            $copies = [1, 2];
            $copy = 'all';
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('delivery_orders.pdf', compact('deliveryOrder', 'settings', 'copies', 'copy'))
                ->setPaper('a5', 'landscape');
            $fileBase64 = base64_encode($pdf->output());
            $res = $waService->sendFile($phone, $fileBase64, $filename, $caption);
        } else {
            $res = $waService->sendMessage($phone, $caption);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($res);
        }

        return back()->with($res['success'] ? 'success' : 'error', $res['message'] ?? 'Surat Jalan berhasil dikirim via WhatsApp.');
    }

    public function markDelivered(Request $request, DeliveryOrder $deliveryOrder): RedirectResponse
    {
        $deliveryOrder->update(['status' => 'delivered']);

        if ($deliveryOrder->salesTransaction) {
            $deliveryOrder->salesTransaction->update([
                'status' => SalesTransaction::STATUS_COMPLETED,
            ]);
        }

        ActivityLog::record(
            action: 'delivery_order_delivered',
            subject: $deliveryOrder,
            description: "Surat Jalan {$deliveryOrder->delivery_number} tujuan {$deliveryOrder->recipient_name} telah sampai dan selesai dibongkar."
        );

        return back()->with('success', "Surat Jalan {$deliveryOrder->delivery_number} berhasil dikonfirmasi sampai di tujuan / selesai! Status transaksi penjualan otomatis resmi SELESAI.");
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

