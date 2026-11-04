<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalesRequest;
use App\Models\BusinessSetting;
use App\Models\PaymentMethod;
use App\Models\SalesTransaction;
use App\Models\ScrapItem;
use App\Services\SalesTransactionService;
use App\Services\FormatHelper;
use App\Services\WhatsAppGatewayService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\JsonResponse;
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

    public function create(Request $request): View
    {
        $scrapItems = ScrapItem::with('stockBalance')
            ->where('is_active', true)
            ->whereHas('stockBalance', function ($q) {
                $q->where('current_stock', '>', 0);
            })
            ->orderBy('name')
            ->get();

        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        $deliveryOrderId = $request->query('delivery_order_id');
        $selectedDeliveryOrder = null;
        if ($deliveryOrderId) {
            $selectedDeliveryOrder = \App\Models\DeliveryOrder::with('items.scrapItem')->find($deliveryOrderId);
        }

        $unbilledDeliveryOrders = \App\Models\DeliveryOrder::whereNull('sales_transaction_id')
            ->latest('delivery_date')
            ->take(20)
            ->get();

        return view('sales.create', compact('scrapItems', 'paymentMethods', 'selectedDeliveryOrder', 'unbilledDeliveryOrders'));
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

    public function pdfInvoice(Request $request, SalesTransaction $sale)
    {
        $sale->load(['items.scrapItem', 'user', 'paymentMethod']);
        $settings = BusinessSetting::pluck('value', 'key')->all();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.invoice_pdf', compact('sale', 'settings'))
            ->setPaper('a5', 'portrait');

        $filename = "Faktur_{$sale->invoice_number}.pdf";

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
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

    /**
     * Export Sales to Excel (Task 18).
     */
    public function exportExcel(Request $request)
    {
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $status = $request->query('status');

        $sales = SalesTransaction::with(['user', 'paymentMethod', 'items'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_number', 'LIKE', "%{$search}%")
                        ->orWhere('buyer_name', 'LIKE', "%{$search}%");
                });
            })
            ->when($startDate, fn($q) => $q->whereDate('transaction_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('transaction_date', '<=', $endDate))
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest('transaction_date')
            ->get();

        $filename = 'Rekap_Penjualan_' . date('Ymd_His') . '.xls';

        return response()->view('sales.excel', compact('sales', 'startDate', 'endDate'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Send Sales Invoice to WhatsApp (Task 18 & 20).
     */
    public function sendWa(Request $request, SalesTransaction $sale)
    {
        $sale->load(['items', 'user', 'paymentMethod']);
        $settings = BusinessSetting::pluck('value', 'key')->all();
        $companyName = $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur';
        $companyPhone = $settings['company_phone'] ?? '';

        $phone = $request->input('phone', $sale->buyer_phone);

        $msg = "*FAKTUR PENJUALAN RONGSOK (GROSIR/PABRIK)*\n";
        $msg .= "*{$companyName}*\n";
        $msg .= "----------------------------------------\n";
        $msg .= "🔖 No. Faktur : *{$sale->invoice_number}*\n";
        $msg .= "🗓️ Tanggal    : " . $sale->transaction_date->format('d/m/Y H:i') . "\n";
        $msg .= "🏢 Pembeli    : *{$sale->buyer_name}*\n";
        if ($sale->buyer_phone) {
            $msg .= "📞 HP Pembeli : {$sale->buyer_phone}\n";
        }
        $msg .= "----------------------------------------\n";
        $msg .= "*RINCIAN MUATAN BARANG:*\n";

        foreach ($sale->items as $it) {
            $typeName = $it->item_type_name ? " ({$it->item_type_name})" : "";
            $qtyStr = ($it->unit_type === 'pcs' || $it->unit_type === 'satuan')
                ? \App\Services\FormatHelper::number($it->quantity ?? 1) . ' pcs'
                : \App\Services\FormatHelper::weight($it->weight) . ' kg';

            $priceStr = \App\Services\FormatHelper::rupiah($it->sell_price_per_kg);
            $subtotalStr = \App\Services\FormatHelper::rupiah($it->subtotal);

            $msg .= "• *{$it->item_name_snapshot}{$typeName}*\n";
            $msg .= "  {$qtyStr} @ {$priceStr} = *{$subtotalStr}*\n";
        }

        $msg .= "----------------------------------------\n";
        $msg .= "⚖️ *Total Berat :* " . \App\Services\FormatHelper::weight($sale->total_weight) . " kg\n";
        $msg .= "💰 *TOTAL BAYAR : " . \App\Services\FormatHelper::rupiah($sale->final_amount) . "*\n";
        $msg .= "💳 *Metode      :* " . ($sale->paymentMethod?->name ?? 'Kas Tunai');
        if ($sale->bank_info) {
            $msg .= " ({$sale->bank_info})";
        }
        $msg .= "\n----------------------------------------\n";
        if ($companyPhone) {
            $msg .= "📞 Hubungi Kantor: {$companyPhone}\n";
        }
        $msg .= "Terima kasih atas kerja samanya! 🙏";

        if (!empty($phone)) {
            $waService = app(\App\Services\WhatsAppGatewayService::class);
            $res = $waService->sendMessage($phone, $msg);
            return back()->with($res['success'] ? 'success' : 'error', $res['message'] ?? 'Faktur terkirim via WhatsApp');
        }

        return redirect('https://wa.me/?text=' . urlencode($msg));
    }

    /**
     * Export Batch Sales Recap as PDF (A4 Portrait).
     */
    public function batchPdf(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $search = $request->query('search');
        $status = $request->query('status', 'completed');

        $transactions = SalesTransaction::with(['items.scrapItem'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_number', 'LIKE', "%{$search}%")
                        ->orWhere('buyer_name', 'LIKE', "%{$search}%");
                });
            })
            ->when($startDate, fn($q) => $q->whereDate('transaction_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('transaction_date', '<=', $endDate))
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest('transaction_date')
            ->get();

        $totalWeight = (float) $transactions->sum('total_weight');
        $totalSales = (float) $transactions->sum('final_amount');
        $totalProfit = (float) $transactions->sum('estimated_profit');
        $settings = BusinessSetting::pluck('value', 'key')->all();

        $pdf = Pdf::loadView('sales.batch_pdf', compact('transactions', 'settings', 'startDate', 'endDate', 'totalWeight', 'totalSales', 'totalProfit'))
            ->setPaper('a4', 'portrait');

        $dateSuffix = ($startDate && $endDate) ? "{$startDate}_sd_{$endDate}" : date('Ymd_His');
        $filename = "Rekap_Penjualan_Partai_{$dateSuffix}.pdf";

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Preview Global WhatsApp message and batch PDF metadata.
     */
    public function previewWaGlobal(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $search = $request->query('search');
        $status = $request->query('status', 'completed');

        $sales = SalesTransaction::when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_number', 'LIKE', "%{$search}%")
                        ->orWhere('buyer_name', 'LIKE', "%{$search}%");
                });
            })
            ->when($startDate, fn($q) => $q->whereDate('transaction_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('transaction_date', '<=', $endDate))
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest('transaction_date')
            ->get();

        $count = $sales->count();
        $totalWeight = (float) $sales->sum('total_weight');
        $totalSales = (float) $sales->sum('final_amount');
        $totalProfit = (float) $sales->sum('estimated_profit');
        $companyName = BusinessSetting::get('company_name', 'UD. BERKAH RONGSOK MAKMUR');
        $periodStr = ($startDate && $endDate) ? "{$startDate} s/d {$endDate}" : date('d/m/Y');

        $msg = "*REKAPITULASI PENJUALAN RONGSOK PARTAI*\n";
        $msg .= "*{$companyName}*\n";
        $msg .= "Periode: {$periodStr}\n\n";
        $msg .= "📦 Total Transaksi : *{$count} Faktur Penjualan*\n";
        $msg .= "⚖️ Total Muatan   : *" . FormatHelper::weight($totalWeight) . " kg*\n";
        $msg .= "💰 Total Omzet    : *" . FormatHelper::rupiah($totalSales) . "*\n";
        $msg .= "📈 Estimasi Laba  : *" . FormatHelper::rupiah($totalProfit) . "*\n\n";

        if ($count > 0) {
            $msg .= "📋 *Ringkasan Faktur Terbaru:*\n";
            $previewList = $sales->take(6);
            foreach ($previewList as $idx => $s) {
                $num = $idx + 1;
                $sWeight = FormatHelper::weight($s->total_weight);
                $sTotal = FormatHelper::rupiah($s->final_amount);
                $msg .= "{$num}. {$s->invoice_number} | {$s->buyer_name} ({$sWeight} kg) - {$sTotal}\n";
            }
            if ($count > 6) {
                $rem = $count - 6;
                $msg .= "... dan {$rem} faktur lainnya tercantum lengkap dalam berkas PDF.\n";
            }
            $msg .= "\n";
        }

        $msg .= "📄 *Terlampir berkas PDF dokumen resmi rekapitulasi penjualan grosir*.\n";
        $msg .= "Diterbitkan resmi oleh Sistem Kasir Rongsok.";

        $dateSuffix = ($startDate && $endDate) ? "{$startDate}_sd_{$endDate}" : date('Ymd_His');
        $pdfFilename = "Rekap_Penjualan_Partai_{$dateSuffix}.pdf";
        $pdfUrl = route('sales.batch-pdf', $request->query());

        return response()->json([
            'success' => true,
            'count' => $count,
            'total_weight' => $totalWeight,
            'total_amount' => $totalSales,
            'message' => $msg,
            'pdf_filename' => $pdfFilename,
            'pdf_url' => $pdfUrl,
            'file_description' => "Dokumen PDF Rekap Penjualan Partai ({$count} Faktur)",
        ]);
    }

    /**
     * Send Global Sales Recap via WhatsApp (with PDF attachment).
     */
    public function sendWaGlobal(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');
        $status = $request->input('status', 'completed');
        $phone = $request->input('phone');
        $sendPdf = $request->boolean('send_pdf', true);
        $caption = $request->input('caption') ?: $request->input('message');

        if (empty($phone)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor WhatsApp tujuan wajib diisi.',
                ], 422);
            }
            return back()->with('error', 'Nomor WhatsApp tujuan wajib diisi.');
        }

        $settings = BusinessSetting::pluck('value', 'key')->all();
        $companyName = $settings['company_name'] ?? 'UD. BERKAH RONGSOK MAKMUR';

        $transactions = SalesTransaction::with(['items.scrapItem'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_number', 'LIKE', "%{$search}%")
                        ->orWhere('buyer_name', 'LIKE', "%{$search}%");
                });
            })
            ->when($startDate, fn($q) => $q->whereDate('transaction_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('transaction_date', '<=', $endDate))
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest('transaction_date')
            ->get();

        $totalWeight = (float) $transactions->sum('total_weight');
        $totalSales = (float) $transactions->sum('final_amount');
        $totalProfit = (float) $transactions->sum('estimated_profit');

        if (empty($caption)) {
            $periodStr = ($startDate && $endDate) ? "{$startDate} s/d {$endDate}" : date('d/m/Y');
            $caption = "*REKAPITULASI PENJUALAN RONGSOK PARTAI*\n";
            $caption .= "*{$companyName}*\n";
            $caption .= "Periode: {$periodStr}\n\n";
            $caption .= "📦 Total Transaksi : *{$transactions->count()} Faktur*\n";
            $caption .= "⚖️ Total Muatan   : *" . FormatHelper::weight($totalWeight) . " kg*\n";
            $caption .= "💰 Total Omzet    : *" . FormatHelper::rupiah($totalSales) . "*\n";
            $caption .= "📈 Estimasi Laba  : *" . FormatHelper::rupiah($totalProfit) . "*\n\n";
            $caption .= "📄 Terlampir berkas dokumen PDF Rekap Penjualan Partai.";
        }

        $waService = app(WhatsAppGatewayService::class);
        $dateSuffix = ($startDate && $endDate) ? "{$startDate}_sd_{$endDate}" : date('Ymd_His');
        $filename = "Rekap_Penjualan_Partai_{$dateSuffix}.pdf";

        if ($sendPdf) {
            $pdf = Pdf::loadView('sales.batch_pdf', compact('transactions', 'settings', 'startDate', 'endDate', 'totalWeight', 'totalSales', 'totalProfit'))
                ->setPaper('a4', 'portrait');
            $pdfRaw = $pdf->output();
            $fileBase64 = base64_encode($pdfRaw);

            $res = $waService->sendFile($phone, $fileBase64, $filename, $caption);
        } else {
            $res = $waService->sendMessage($phone, $caption);
        }

        if ($request->wantsJson()) {
            return response()->json($res);
        }

        return back()->with($res['success'] ? 'success' : 'error', $res['message'] ?? 'Status terkirim.');
    }
}

