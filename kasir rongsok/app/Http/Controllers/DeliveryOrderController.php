<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BusinessSetting;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
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
            'items.*.unit_type' => 'nullable|string|in:kg,pcs,satuan',
            'items.*.item_type_name' => 'nullable|string|max:150',
            'items.*.quantity' => 'nullable|numeric|min:0.01',
            'items.*.weight' => 'nullable|numeric|min:0',
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
                $deliveryNumber = FormatHelper::generateDeliveryOrderNumber($date);

                $totalWeight = 0;
                $totalPackages = 0;

                foreach ($validated['items'] as $item) {
                    $totalWeight += (float) ($item['weight'] ?? 0);
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
                        'unit_type' => $item['unit_type'] ?? 'kg',
                        'item_type_name' => $item['item_type_name'] ?? null,
                        'quantity' => $item['quantity'] ?? 1,
                        'weight' => (float) ($item['weight'] ?? 0),
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

    public function pdf(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->load(['user', 'salesTransaction', 'items.scrapItem']);
        $settings = BusinessSetting::pluck('value', 'key')->all();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('delivery_orders.pdf', compact('deliveryOrder', 'settings'))
            ->setPaper('a5', 'landscape');

        $filename = "Surat_Jalan_{$deliveryOrder->delivery_number}.pdf";
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

        $msg = "*SURAT JALAN PENGIRIMAN RONGSOK*\n";
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
        if ($companyPhone) {
            $msg .= "📞 Hubungi Depo: {$companyPhone}\n";
        }
        $msg .= "Terima kasih atas kerja samanya! 🙏";

        $filename = "Surat_Jalan_{$deliveryOrder->delivery_number}.pdf";
        $targetPhone = $deliveryOrder->recipient_phone ?: $deliveryOrder->driver_phone ?: '';

        return response()->json([
            'phone' => $targetPhone,
            'message' => $msg,
            'delivery_number' => $deliveryOrder->delivery_number,
            'filename' => $filename,
            'pdf_filename' => $filename,
            'pdf_url' => route('delivery-orders.pdf', $deliveryOrder),
            'file_description' => 'Dokumen PDF Resmi Surat Jalan Pengiriman (Lengkap Supir, Armada & Muatan)',
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
        $caption = $request->input('message') ?: $request->input('caption') ?: "Terlampir Berkas Dokumen PDF Surat Jalan {$deliveryOrder->delivery_number}.";
        $filename = $request->input('filename') ?: ("Surat_Jalan_{$deliveryOrder->delivery_number}.pdf");

        $waService = app(\App\Services\WhatsAppGatewayService::class);

        if ($sendPdf) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('delivery_orders.pdf', compact('deliveryOrder', 'settings'))
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

