<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\DebtPayment;
use App\Models\PurchaseTransaction;
use App\Models\SalesTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGatewayService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.whatsapp_gateway.url', 'http://127.0.0.1:21465');
    }

    /**
     * Get connection status from WPPConnect Gateway
     */
    public function getStatus(): array
    {
        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/api/status");
            if ($response->successful()) {
                return $response->json();
            }
            return [
                'status' => 'ERROR',
                'connected' => false,
                'message' => 'Gateway server merespon dengan status: ' . $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'OFFLINE',
                'connected' => false,
                'message' => 'WhatsApp Gateway offline atau belum dijalankan di port 21465.',
            ];
        }
    }

    /**
     * Get QR code from WPPConnect Gateway
     */
    public function getQrCode(): array
    {
        try {
            $response = Http::timeout(4)->get("{$this->baseUrl}/api/qr");
            if ($response->successful()) {
                return $response->json();
            }
            return [
                'status' => 'ERROR',
                'qr' => null,
                'message' => 'Gagal memuat QR Code.',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'OFFLINE',
                'qr' => null,
                'message' => 'WhatsApp Gateway tidak aktif.',
            ];
        }
    }

    /**
     * Logout and destroy WhatsApp session
     */
    public function logout(): array
    {
        try {
            $response = Http::timeout(25)->post("{$this->baseUrl}/api/logout");
            if ($response->successful()) {
                return $response->json();
            }
            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Gagal logout dari WhatsApp.',
            ];
        } catch (\Exception $e) {
            Log::error('WhatsAppGateway logout error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Koneksi ke gateway gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Reconnect / restart client to get fresh QR code
     */
    public function reconnect(): array
    {
        try {
            $response = Http::timeout(25)->post("{$this->baseUrl}/api/reconnect");
            if ($response->successful()) {
                return $response->json();
            }
            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Gagal inisialisasi ulang gateway.',
            ];
        } catch (\Exception $e) {
            Log::error('WhatsAppGateway reconnect error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Koneksi ke gateway gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Clear / reset chat queue for specific phone
     */
    public function clearChat(string $phone): array
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/api/clear-chat", [
                'phone' => $this->formatPhone($phone),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Gagal mereset obrolan.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Koneksi ke gateway gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Format phone number to international WhatsApp format (628xxx)
     */
    public function formatPhone(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        $clean = preg_replace('/\D/', '', $phone);
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        } elseif (!str_starts_with($clean, '62') && !empty($clean)) {
            $clean = '62' . $clean;
        }

        return $clean;
    }

    /**
     * Send a single message
     */
    public function sendMessage(string $phone, string $message): array
    {
        $targetPhone = $this->formatPhone($phone);
        if (empty($targetPhone)) {
            return [
                'success' => false,
                'message' => 'Nomor WhatsApp tujuan tidak valid.',
            ];
        }

        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/api/send-message", [
                'phone' => $targetPhone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Gagal mengirim pesan via WhatsApp Gateway.',
            ];
        } catch (\Exception $e) {
            Log::error('WhatsAppGateway sendMessage error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Koneksi ke WhatsApp Gateway gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send file (document / PDF) to a WhatsApp recipient
     */
    public function sendFile(string $phone, string $fileBase64, string $filename = 'Dokumen.pdf', ?string $caption = null): array
    {
        $targetPhone = $this->formatPhone($phone);
        if (empty($targetPhone)) {
            return [
                'success' => false,
                'message' => 'Nomor WhatsApp tujuan tidak valid.',
            ];
        }

        try {
            $response = Http::timeout(45)->post("{$this->baseUrl}/api/send-file", [
                'phone' => $targetPhone,
                'fileBase64' => $fileBase64,
                'filename' => $filename,
                'caption' => $caption ?? '',
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Gagal mengirim berkas via WhatsApp Gateway.',
            ];
        } catch (\Exception $e) {
            Log::error('WhatsAppGateway sendFile error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Koneksi ke gateway gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send bulk messages
     */
    public function sendBulk(array $recipients): array
    {
        try {
            $response = Http::timeout(60)->post("{$this->baseUrl}/api/send-bulk", [
                'recipients' => $recipients,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Gagal mengirim pesan massal.',
            ];
        } catch (\Exception $e) {
            Log::error('WhatsAppGateway sendBulk error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Koneksi ke gateway gagal: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build formatted text for Purchase Transaction (Nota Timbangan Pembelian Rongsok)
     */
    public function buildPurchaseReceiptMessage(PurchaseTransaction $purchase): string
    {
        $companyName = BusinessSetting::get('company_name', 'UD. Berkah Rongsok Makmur');
        $companyAddress = BusinessSetting::get('company_address', 'Sentra Daur Ulang Logam & Rongsok');
        $companyPhone = BusinessSetting::get('company_phone', '');
        $disclaimer = BusinessSetting::get('receipt_disclaimer', 'TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI');
        $footerText = BusinessSetting::get('receipt_footer', 'Barang yang telah ditimbang dan dibayar tidak dapat ditarik kembali. Terima kasih atas kerja samanya.');

        $supplierName = $purchase->supplier_name ?: 'Pemasok Umum / Tunai';
        $kasirName = $purchase->user ? $purchase->user->name : 'Kasir Timbang';
        $dateStr = Carbon::parse($purchase->transaction_date)->translatedFormat('d F Y, H:i') . ' WIB';

        $items = $purchase->items ?? [];
        if ($items instanceof \Illuminate\Database\Eloquent\Collection && $items->isEmpty()) {
            $purchase->load('items');
            $items = $purchase->items;
        }

        $itemLines = [];
        $i = 1;
        foreach ($items as $item) {
            $itemName = $item->item_name_snapshot;
            $bruto = number_format($item->gross_weight, 2, ',', '.');
            $tara = number_format($item->tare_weight, 2, ',', '.');
            $netto = number_format($item->net_weight, 2, ',', '.');
            $price = number_format($item->price_per_kg, 0, ',', '.');
            $subtotal = number_format($item->subtotal, 0, ',', '.');

            $line = "{$i}. *{$itemName}*\n"
                  . "   ⚖️ {$netto} kg (Kotor: {$bruto} kg - Pot: {$tara} kg)\n"
                  . "   💵 @Rp {$price} = *Rp {$subtotal}*";
            $itemLines[] = $line;
            $i++;
        }

        $itemsFormatted = count($itemLines) > 0 ? implode("\n\n", $itemLines) : "- (Tidak ada rincian barang)";

        $totalNetto = number_format($purchase->total_net_weight, 2, ',', '.');
        $totalGross = number_format($purchase->total_gross_weight, 2, ',', '.');
        $totalTare = number_format($purchase->total_tare_weight, 2, ',', '.');

        $finalAmount = number_format($purchase->final_amount, 0, ',', '.');
        $debtDeduction = number_format($purchase->debt_deduction_amount, 0, ',', '.');
        $netCashPaid = number_format($purchase->net_cash_paid, 0, ',', '.');

        $msg = "*=============================*\n";
        $msg .= "♻️ *{$companyName}*\n";
        if (!empty($companyAddress)) {
            $msg .= "📍 {$companyAddress}\n";
        }
        if (!empty($companyPhone)) {
            $msg .= "📞 WA/Telp: {$companyPhone}\n";
        }
        $msg .= "*=============================*\n";
        $msg .= "🧾 *BUKTI PENERIMAAN / NOTA TIMBANG*\n\n";
        $msg .= "🔖 No. Nota : *{$purchase->invoice_number}*\n";
        $msg .= "🗓️ Waktu    : {$dateStr}\n";
        $msg .= "👤 Pemasok  : *{$supplierName}*\n";
        $msg .= "👨‍💼 Kasir    : {$kasirName}\n";
        $msg .= "----------------------------------------\n\n";
        $msg .= "📦 *RINCIAN PENIMBANGAN BARANG:*\n\n";
        $msg .= "{$itemsFormatted}\n\n";
        $msg .= "----------------------------------------\n";
        $msg .= "⚖️ *Total Berat Kotor :* {$totalGross} kg\n";
        $msg .= "✂️ *Total Potongan Tara:* {$totalTare} kg\n";
        $msg .= "✅ *Total Berat Bersih :* *{$totalNetto} kg*\n";
        $msg .= "----------------------------------------\n";
        $msg .= "💰 *TOTAL NILAI NOTA :* *Rp {$finalAmount}*\n";

        if ($purchase->debt_deduction_amount > 0) {
            $msg .= "✂️ *Potongan Kasbon/Hutang:* -Rp {$debtDeduction}\n";
            $msg .= "💵 *TUNAI DIBAYARKAN :* *Rp {$netCashPaid}*\n";
            
            if ($purchase->remaining_debt_after > 0) {
                $remDebt = number_format($purchase->remaining_debt_after, 0, ',', '.');
                $msg .= "⚠️ *Sisa Pinjaman Modal:* Rp {$remDebt}\n";
            }
        } else {
            $msg .= "💵 *TUNAI DIBAYARKAN :* *Rp {$finalAmount}*\n";
        }

        $msg .= "\n----------------------------------------\n";
        $msg .= "⚠️ *PERINGATAN :*\n*{$disclaimer}*\n";
        if (!empty($footerText)) {
            $msg .= "\n_{$footerText}_\n";
        }
        $msg .= "\n🙏 *Terima kasih atas kerja samanya!*";

        return $msg;
    }

    /**
     * Send Purchase Receipt via Gateway
     */
    public function sendPurchaseReceipt(PurchaseTransaction $purchase, ?string $targetPhone = null): array
    {
        $phone = $targetPhone ?: $purchase->supplier_phone;
        if (empty($phone) && $purchase->supplier) {
            $phone = $purchase->supplier->phone;
        }

        if (empty($phone)) {
            return [
                'success' => false,
                'message' => 'Pemasok tidak memiliki nomor WhatsApp tersimpan.',
            ];
        }

        $message = $this->buildPurchaseReceiptMessage($purchase);
        return $this->sendMessage($phone, $message);
    }

    /**
     * Build formatted text for Wholesale Sales Invoice (Penjualan Partai ke Pabrik)
     */
    public function buildSalesInvoiceMessage(SalesTransaction $sale): string
    {
        $companyName = BusinessSetting::get('company_name', 'UD. Berkah Rongsok Makmur');
        $companyPhone = BusinessSetting::get('company_phone', '');
        $dateStr = Carbon::parse($sale->transaction_date)->translatedFormat('d F Y, H:i') . ' WIB';

        $customerName = $sale->customer_name ?: 'Pabrik / Pembeli';
        $sale->loadMissing('items');

        $lines = [];
        $i = 1;
        foreach ($sale->items as $item) {
            $itemName = $item->item_name_snapshot;
            $kg = number_format($item->weight, 2, ',', '.');
            $price = number_format($item->sell_price_per_kg, 0, ',', '.');
            $subtotal = number_format($item->subtotal, 0, ',', '.');

            $lines[] = "{$i}. *{$itemName}* ({$kg} kg @ Rp {$price}) = *Rp {$subtotal}*";
            $i++;
        }
        $itemsStr = count($lines) > 0 ? implode("\n", $lines) : "-";

        $totalWeight = number_format($sale->total_weight, 2, ',', '.');
        $grandTotal = number_format($sale->final_amount, 0, ',', '.');

        $msg = "*=============================*\n";
        $msg .= "🚛 *{$companyName}*\n";
        $msg .= "🧾 *FAKTUR PENJUALAN PARTAI (GROSIR)*\n";
        $msg .= "*=============================*\n\n";
        $msg .= "🔖 No. Faktur : *{$sale->invoice_number}*\n";
        $msg .= "🗓️ Tanggal    : {$dateStr}\n";
        $msg .= "🏭 Pembeli    : *{$customerName}*\n\n";
        $msg .= "📦 *RINCIAN PENGIRIMAN:*\n{$itemsStr}\n\n";
        $msg .= "----------------------------------------\n";
        $msg .= "⚖️ *Total Muatan :* *{$totalWeight} kg*\n";
        $msg .= "💰 *TOTAL TAGIHAN:* *Rp {$grandTotal}*\n";
        $msg .= "----------------------------------------\n";
        $msg .= "Terima kasih atas kerja sama dan kepercayaan Anda! 🙏";

        return $msg;
    }

    /**
     * Send Sales Invoice via Gateway
     */
    public function sendSalesInvoice(SalesTransaction $sale, ?string $targetPhone = null): array
    {
        $phone = $targetPhone ?: $sale->customer_phone;
        if (empty($phone)) {
            return [
                'success' => false,
                'message' => 'Nomor WhatsApp pembeli tidak diisi.',
            ];
        }

        $message = $this->buildSalesInvoiceMessage($sale);
        return $this->sendMessage($phone, $message);
    }

    /**
     * Build formatted text for Debt / Cash Advance Payment Receipt
     */
    public function buildDebtPaymentMessage(DebtPayment $payment): string
    {
        $companyName = BusinessSetting::get('company_name', 'UD. Berkah Rongsok Makmur');
        $payment->loadMissing(['debt.supplier', 'paymentMethod']);

        $supplierName = $payment->debt?->supplier?->name ?? 'Pemasok';
        $dateStr = Carbon::parse($payment->payment_date)->translatedFormat('d F Y, H:i') . ' WIB';
        $amount = number_format($payment->amount, 0, ',', '.');
        $remBefore = number_format($payment->remaining_before, 0, ',', '.');
        $remAfter = number_format($payment->remaining_after, 0, ',', '.');
        $method = $payment->paymentMethod?->name ?? 'Tunai';

        $msg = "*=============================*\n";
        $msg .= "🤝 *{$companyName}*\n";
        $msg .= "🧾 *BUKTI PEMBAYARAN KASBON / MODAL*\n";
        $msg .= "*=============================*\n\n";
        $msg .= "🔖 No. Bukti : *{$payment->payment_number}*\n";
        $msg .= "🗓️ Waktu     : {$dateStr}\n";
        $msg .= "👤 Pemasok   : *{$supplierName}*\n\n";
        $msg .= "💵 *Jumlah Dibayar  :* *Rp {$amount}* ({$method})\n";
        $msg .= "📉 *Saldo Awal      :* Rp {$remBefore}\n";
        $msg .= "✅ *Sisa Kasbon Kini:* *Rp {$remAfter}*\n\n";
        $msg .= "Bukti ini sah diterbitkan otomatis oleh sistem {$companyName}.";

        return $msg;
    }

    /**
     * Send Debt Payment Receipt via Gateway
     */
    public function sendDebtPaymentReceipt(DebtPayment $payment, ?string $targetPhone = null): array
    {
        $phone = $targetPhone ?: $payment->debt?->supplier?->phone;
        if (empty($phone)) {
            return [
                'success' => false,
                'message' => 'Nomor WhatsApp pemasok tidak ditemukan.',
            ];
        }

        $message = $this->buildDebtPaymentMessage($payment);
        return $this->sendMessage($phone, $message);
    }
}

