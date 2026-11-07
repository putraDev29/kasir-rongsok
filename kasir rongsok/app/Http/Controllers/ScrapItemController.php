<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScrapItemRequest;
use App\Models\ActivityLog;
use App\Models\ItemCategory;
use App\Models\ScrapItem;
use App\Models\StockBalance;
use App\Services\StockService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ScrapItemController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function index(Request $request): View
    {
        $search = $request->query('search');
        $categoryId = $request->query('category_id');
        $status = $request->query('status');

        $query = ScrapItem::with(['category', 'stockBalance'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%");
                });
            })
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->when($status !== null && $status !== '', function ($q) use ($status) {
                $q->where('is_active', $status == '1');
            })
            ->orderBy('name');

        $items = $query->paginate(15)->withQueryString();
        $categories = ItemCategory::where('is_active', true)->get();

        return view('scrap_items.index', compact('items', 'categories', 'search', 'categoryId', 'status'));
    }

    public function create(): View
    {
        $categories = ItemCategory::where('is_active', true)->get();
        return view('scrap_items.create', compact('categories'));
    }

    public function store(StoreScrapItemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $item = ScrapItem::create($data);

        // Initialize zero stock balance
        StockBalance::create([
            'scrap_item_id' => $item->id,
            'current_stock' => 0,
            'total_in' => 0,
            'total_out' => 0,
            'average_buy_price' => $item->buy_price_per_kg,
        ]);

        ActivityLog::record(
            action: 'scrap_item_created',
            subject: $item,
            description: "Menambahkan jenis barang rongsok baru: {$item->name} ({$item->code})"
        );

        return redirect()->route('scrap-items.index')->with('success', "Barang rongsok '{$item->name}' berhasil ditambahkan.");
    }

    public function edit(ScrapItem $scrapItem): View
    {
        $categories = ItemCategory::where('is_active', true)->get();
        $scrapItem->load(['category', 'stockBalance', 'priceHistories.user']);
        return view('scrap_items.edit', compact('scrapItem', 'categories'));
    }

    public function update(StoreScrapItemRequest $request, ScrapItem $scrapItem): RedirectResponse
    {
        $data = $request->validated();
        $newBuyPrice = (float) $data['buy_price_per_kg'];
        $newSellPrice = (float) ($data['sell_price_per_kg'] ?? 0);
        $reason = $request->input('price_change_reason', 'Pembaruan harga dari form edit');

        // Track price history if prices changed
        $this->stockService->updatePrice($scrapItem, $newBuyPrice, $newSellPrice, $reason, Auth::user());

        $scrapItem->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'category_id' => $data['category_id'],
            'unit' => $data['unit'],
            'min_stock_alert' => $data['min_stock_alert'] ?? 0,
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        ActivityLog::record(
            action: 'scrap_item_updated',
            subject: $scrapItem,
            description: "Memperbarui data barang rongsok: {$scrapItem->name}"
        );

        return redirect()->route('scrap-items.index')->with('success', "Data barang '{$scrapItem->name}' berhasil diperbarui.");
    }

    public function updatePrice(Request $request, ScrapItem $scrapItem): RedirectResponse
    {
        $request->validate([
            'buy_price_per_kg' => 'required|numeric|min:0',
            'sell_price_per_kg' => 'nullable|numeric|min:0',
            'reason' => 'required|string|max:255',
        ], [
            'reason.required' => 'Alasan perubahan harga wajib diisi untuk rekam audit.',
        ]);

        $this->stockService->updatePrice(
            $scrapItem,
            (float) $request->input('buy_price_per_kg'),
            (float) $request->input('sell_price_per_kg', 0),
            $request->input('reason'),
            Auth::user()
        );

        return back()->with('success', "Harga barang '{$scrapItem->name}' berhasil diperbarui.");
    }

    public function destroy(ScrapItem $scrapItem): RedirectResponse
    {
        // Check if item has transactions
        $hasPurchases = $scrapItem->purchaseItems()->exists();
        $hasSales = $scrapItem->salesItems()->exists();

        if ($hasPurchases || $hasSales) {
            return back()->with('error', "Barang '{$scrapItem->name}' sudah pernah memiliki riwayat transaksi dan tidak boleh dihapus. Anda dapat menonaktifkannya.");
        }

        $name = $scrapItem->name;
        $scrapItem->delete();

        ActivityLog::record(
            action: 'scrap_item_deleted',
            description: "Menghapus jenis barang: {$name}"
        );

        return redirect()->route('scrap-items.index')->with('success', "Barang '{$name}' berhasil dihapus.");
    }

    /**
     * Print / Export PDF of Buying Price List (Daftar Harga Beli Rongsok).
     */
    public function printPriceList(Request $request): View
    {
        $categories = ItemCategory::with(['scrapItems' => function ($q) {
            $q->where('is_active', true)->orderBy('name');
        }])->where('is_active', true)->orderBy('name')->get();

        $settings = \App\Models\BusinessSetting::pluck('value', 'key')->all();

        return view('scrap_items.print_price_list', compact('categories', 'settings'));
    }

    /**
     * Download / Stream PDF of Buying Price List.
     */
    public function pdfPriceList(Request $request)
    {
        $categories = ItemCategory::with(['scrapItems' => function ($q) {
            $q->where('is_active', true)->orderBy('name');
        }])->where('is_active', true)->orderBy('name')->get();

        $settings = \App\Models\BusinessSetting::pluck('value', 'key')->all();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('scrap_items.price_list_pdf', compact('categories', 'settings'))
            ->setPaper('a4', 'portrait');

        $filename = 'Daftar_Harga_Beli_Material_Scrap_' . date('Ymd') . '.pdf';

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Preview WhatsApp message for price list.
     */
    public function previewWaPriceList(Request $request)
    {
        $settings = \App\Models\BusinessSetting::pluck('value', 'key')->all();
        $companyName = $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur';
        $companyPhone = $settings['company_phone'] ?? '';
        $pickupText = $settings['pickup_service_text'] ?? 'Melayani armada jemput barang rongsok skala besar langsung ke lokasi gudang/pabrik Anda.';

        $categories = ItemCategory::with(['scrapItems' => function ($q) {
            $q->where('is_active', true)->orderBy('name');
        }])->where('is_active', true)->orderBy('name')->get();

        $msg = "*DAFTAR HARGA BELI RONGSOK TERBARU*\n";
        $msg .= "*{$companyName}*\n";
        $msg .= "🗓️ Update: " . date('d/m/Y') . "\n";
        $msg .= "----------------------------------------\n";

        foreach ($categories as $cat) {
            if ($cat->scrapItems->count() === 0) continue;
            $msg .= "\n📂 *" . strtoupper($cat->name) . "*\n";
            foreach ($cat->scrapItems as $item) {
                $priceStr = \App\Services\FormatHelper::rupiah($item->buy_price_per_kg);
                $msg .= "• {$item->name}: *{$priceStr}/kg*\n";
            }
        }

        $msg .= "\n----------------------------------------\n";
        $msg .= "🚛 {$pickupText}\n";
        if ($companyPhone) {
            $msg .= "📞 Hubungi: {$companyPhone}\n";
        }
        $msg .= "_*Harga dapat berubah sewaktu-waktu mengikuti tren pasar daur ulang._";

        $filename = 'Daftar_Harga_Beli_Rongsok_' . date('Ymd') . '.pdf';

        return response()->json([
            'message' => \App\Services\FormatHelper::cleanCaption($msg),
            'pdf_url' => route('scrap-items.pdf-price-list'),
            'pdf_filename' => $filename,
        ]);
    }

    /**
     * Send Buying Price List via WhatsApp (Task 10).
     */
    public function sendWaPriceList(Request $request)
    {
        $phone = $request->input('phone');
        $sendPdf = $request->boolean('send_pdf', true);
        $settings = \App\Models\BusinessSetting::pluck('value', 'key')->all();
        $companyName = $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur';
        $companyPhone = $settings['company_phone'] ?? '';
        $pickupText = $settings['pickup_service_text'] ?? 'Melayani armada jemput barang rongsok skala besar langsung ke lokasi gudang/pabrik Anda.';

        $categories = ItemCategory::with(['scrapItems' => function ($q) {
            $q->where('is_active', true)->orderBy('name');
        }])->where('is_active', true)->orderBy('name')->get();

        $msg = "*DAFTAR HARGA BELI RONGSOK TERBARU*\n";
        $msg .= "*{$companyName}*\n";
        $msg .= "🗓️ Update: " . date('d/m/Y') . "\n";
        $msg .= "----------------------------------------\n";

        foreach ($categories as $cat) {
            if ($cat->scrapItems->count() === 0) continue;
            $msg .= "\n📂 *" . strtoupper($cat->name) . "*\n";
            foreach ($cat->scrapItems as $item) {
                $priceStr = \App\Services\FormatHelper::rupiah($item->buy_price_per_kg);
                $msg .= "• {$item->name}: *{$priceStr}/kg*\n";
            }
        }

        $msg .= "\n----------------------------------------\n";
        $msg .= "🚛 {$pickupText}\n";
        if ($companyPhone) {
            $msg .= "📞 Hubungi: {$companyPhone}\n";
        }
        $msg .= "_*Harga dapat berubah sewaktu-waktu mengikuti tren pasar daur ulang._";

        $customCaption = \App\Services\FormatHelper::cleanCaption($request->input('message') ?: $request->input('caption') ?: $msg);

        if (!empty($phone)) {
            $waService = app(\App\Services\WhatsAppGatewayService::class);
            if ($sendPdf) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('scrap_items.price_list_pdf', compact('categories', 'settings'))
                    ->setPaper('a4', 'portrait');
                $fileBase64 = base64_encode($pdf->output());
                $filename = $request->input('filename') ?: ('Daftar_Harga_Beli_Rongsok_' . date('Ymd') . '.pdf');
                $res = $waService->sendFile($phone, $fileBase64, $filename, $customCaption);
            } else {
                $res = $waService->sendMessage($phone, $customCaption);
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json($res);
            }

            return back()->with($res['success'] ? 'success' : 'error', $res['message'] ?? 'Daftar harga terkirim ke WhatsApp.');
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp tujuan wajib diisi.'
            ], 422);
        }

        return redirect('https://wa.me/?text=' . urlencode($msg));
    }

    /**
     * Export price list & items to Excel (Task 18).
     */
    public function exportExcel()
    {
        $categories = ItemCategory::with(['scrapItems' => function ($q) {
            $q->orderBy('name');
        }])->where('is_active', true)->orderBy('name')->get();

        $filename = 'Daftar_Harga_Barang_' . date('Ymd_His') . '.xls';

        return response()->view('scrap_items.excel', compact('categories'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }
}

