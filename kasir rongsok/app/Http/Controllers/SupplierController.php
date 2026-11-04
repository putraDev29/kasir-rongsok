<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BusinessSetting;
use App\Models\Supplier;
use App\Models\SupplierReward;
use App\Services\FormatHelper;
use App\Services\WhatsAppGatewayService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                    ->orWhere('member_number', 'LIKE', "%{$search}%")
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
            'member_number' => 'nullable|string|max:50',
            'join_date' => 'nullable|date',
            'tier_override' => 'nullable|string|in:bronze,silver,gold,platinum',
        ]);

        $data['code'] = 'SUP-' . strtoupper(substr(uniqid(), -5));
        if (empty($data['member_number'])) {
            $data['member_number'] = 'MBR-' . $data['code'];
        }
        if (empty($data['join_date'])) {
            $data['join_date'] = now()->toDateString();
        }
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
            'member_number' => 'nullable|string|max:50',
            'join_date' => 'nullable|date',
            'tier_override' => 'nullable|string|in:bronze,silver,gold,platinum',
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

    /**
     * Tampilan Cetak / Visual Kartu Member Pemasok
     */
    public function memberCard(Supplier $supplier): View
    {
        $settings = BusinessSetting::pluck('value', 'key')->all();
        $rewards = collect();
        try {
            $rewards = $supplier->rewards()->take(5)->get();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal memuat rewards pemasok: ' . $e->getMessage());
        }

        return view('suppliers.member_card', compact('supplier', 'settings', 'rewards'));
    }

    /**
     * Tampilkan Preview / Download PDF Kartu Member Pemasok
     */
    public function memberCardPdf(Request $request, Supplier $supplier)
    {
        $settings = BusinessSetting::pluck('value', 'key')->all();
        $rewards = collect();
        try {
            $rewards = $supplier->rewards()->take(5)->get();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal memuat rewards pemasok: ' . $e->getMessage());
        }

        $pdf = Pdf::loadView('suppliers.member_card_pdf', compact('supplier', 'settings', 'rewards'))
            ->setPaper('a6', 'landscape');

        $filename = 'Kartu_Member_' . ($supplier->member_number ?: $supplier->code) . '.pdf';

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Preview Member Card WhatsApp message and PDF metadata.
     */
    public function previewMemberCardWa(Supplier $supplier): JsonResponse
    {
        $settings = BusinessSetting::pluck('value', 'key')->all();
        $companyName = $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur';
        $companyPhone = $settings['company_phone'] ?? '';

        $tierStars = str_repeat('⭐', $supplier->rating_stars);
        $tierName = strtoupper($supplier->tier);

        $msg = "*KARTU MEMBER RESMI PEMASOK RONGSOK*\n";
        $msg .= "*{$companyName}*\n";
        $msg .= "----------------------------------------\n";
        $msg .= "🔖 No. Member : *{$supplier->member_number}*\n";
        $msg .= "👤 Nama       : *{$supplier->name}*\n";
        if ($supplier->phone) {
            $msg .= "📞 HP         : {$supplier->phone}\n";
        }
        $msg .= "🏆 Tier Level : *{$tierName} MEMBER*\n";
        $msg .= "⭐ Rating     : {$tierStars} ({$supplier->rating_stars} Bintang)\n";
        $msg .= "----------------------------------------\n";
        $msg .= "📊 *REKAP PENJUALAN RONGSOK:*\n";
        $msg .= "⚖️ Total Setor : *" . FormatHelper::weight($supplier->total_weight) . " kg*\n";
        $msg .= "💰 Total Omzet : *" . FormatHelper::rupiah($supplier->total_amount) . "*\n";
        $msg .= "📦 Frekuensi   : {$supplier->total_transactions_count} kali setor\n";
        $msg .= "🎁 Poin Reward : *{$supplier->points} Poin*\n";
        $msg .= "----------------------------------------\n";
        $msg .= "🎁 *HAK REWARD DARI OWNER:*\n";
        $msg .= "👉 _{$supplier->eligible_reward}_\n\n";
        $msg .= "📄 *Terlampir berkas dokumen PDF resmi Kartu Member & Hak Reward*.\n";
        $msg .= "Terima kasih atas kerja sama dan loyalitas Anda! 🙏";

        $pdfFilename = "Kartu_Member_{$supplier->member_number}.pdf";
        $pdfUrl = route('suppliers.member-card-pdf', $supplier);

        return response()->json([
            'success' => true,
            'phone' => $supplier->phone ?? '',
            'message' => $msg,
            'pdf_filename' => $pdfFilename,
            'pdf_url' => $pdfUrl,
            'file_description' => "Dokumen PDF Resmi Kartu Member ({$supplier->name} - {$tierName})",
        ]);
    }

    /**
     * Kirim Ringkasan Kartu Member & Poin Reward ke WhatsApp Pemasok
     */
    public function sendMemberCardWa(Request $request, Supplier $supplier)
    {
        $phone = $request->input('phone', $supplier->phone);
        $sendPdf = $request->boolean('send_pdf', true);
        $caption = $request->input('caption') ?: $request->input('message');
        $settings = BusinessSetting::pluck('value', 'key')->all();
        $companyName = $settings['company_name'] ?? 'UD. Berkah Rongsok Makmur';
        $companyPhone = $settings['company_phone'] ?? '';

        if (empty($phone)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Nomor WhatsApp pemasok wajib diisi.'], 422);
            }
            return back()->with('error', 'Nomor WhatsApp pemasok wajib diisi.');
        }

        $tierStars = str_repeat('⭐', $supplier->rating_stars);
        $tierName = strtoupper($supplier->tier);

        if (empty($caption)) {
            $caption = "*KARTU MEMBER RESMI PEMASOK RONGSOK*\n";
            $caption .= "*{$companyName}*\n";
            $caption .= "----------------------------------------\n";
            $caption .= "🔖 No. Member : *{$supplier->member_number}*\n";
            $caption .= "👤 Nama       : *{$supplier->name}*\n";
            $caption .= "🏆 Tier Level : *{$tierName} MEMBER* {$tierStars}\n";
            $caption .= "🎁 Hak Reward : _{$supplier->eligible_reward}_\n";
            $caption .= "📄 Terlampir berkas PDF resmi Kartu Member.";
        }

        $waService = app(WhatsAppGatewayService::class);
        $filename = "Kartu_Member_{$supplier->member_number}.pdf";

        if ($sendPdf) {
            $recentRewards = collect();
            try {
                $recentRewards = $supplier->rewards()->latest('awarded_date')->take(5)->get();
            } catch (\Throwable $e) {}

            $pdf = Pdf::loadView('suppliers.member_card_pdf', compact('supplier', 'settings', 'recentRewards'))
                ->setPaper('a4', 'portrait');
            $fileBase64 = base64_encode($pdf->output());

            $res = $waService->sendFile($phone, $fileBase64, $filename, $caption);
        } else {
            $res = $waService->sendMessage($phone, $caption);
        }

        if ($request->wantsJson()) {
            return response()->json($res);
        }

        return back()->with($res['success'] ? 'success' : 'error', $res['message'] ?? 'Kartu member terkirim ke WhatsApp');
    }

    /**
     * Catat Pemberian Reward dari Owner ke Pemasok
     */
    public function storeReward(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validate([
            'reward_title' => 'required|string|max:150',
            'reward_type' => 'required|string|in:cash,souvenir,voucher,price_bonus,other',
            'reward_value' => 'nullable|numeric|min:0',
            'points_spent' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $data['supplier_id'] = $supplier->id;
        $data['user_id'] = Auth::id();
        $data['awarded_date'] = now()->toDateString();
        $data['status'] = 'given';
        $data['reward_value'] = (float) ($data['reward_value'] ?? 0);
        $data['points_spent'] = (int) ($data['points_spent'] ?? 0);

        try {
            SupplierReward::create($data);
        } catch (\Throwable $e) {
            // Graceful fallback if table not yet migrated
        }

        ActivityLog::record(
            action: 'supplier_reward_granted',
            subject: $supplier,
            description: "Memberikan reward '{$data['reward_title']}' kepada pemasok {$supplier->name}"
        );

        return back()->with('success', "Reward '{$data['reward_title']}' berhasil dicatat dan diberikan kepada {$supplier->name}.");
    }
}

