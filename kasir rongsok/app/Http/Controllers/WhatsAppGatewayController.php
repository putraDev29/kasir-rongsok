<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\DebtPayment;
use App\Models\PurchaseTransaction;
use App\Models\SalesTransaction;
use App\Services\FormatHelper;
use App\Services\WhatsAppGatewayService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppGatewayController extends Controller
{
    protected WhatsAppGatewayService $gateway;

    public function __construct(WhatsAppGatewayService $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Get WPPConnect gateway status
     */
    public function status(): JsonResponse
    {
        $status = $this->gateway->getStatus();
        return response()->json($status);
    }

    /**
     * Get QR code for scanning
     */
    public function qr(): JsonResponse
    {
        $qr = $this->gateway->getQrCode();
        return response()->json($qr);
    }

    /**
     * Reconnect / restart gateway client
     */
    public function reconnect(): JsonResponse
    {
        $res = $this->gateway->reconnect();
        return response()->json($res);
    }

    /**
     * Logout and destroy current WhatsApp session
     */
    public function logout(): JsonResponse
    {
        $res = $this->gateway->logout();
        return response()->json($res);
    }

    /**
     * Clear / reset chat queue for specific phone
     */
    public function clearChat(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $res = $this->gateway->clearChat($request->input('phone'));
        return response()->json($res);
    }

    /**
     * Send test or arbitrary single WhatsApp message
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        $res = $this->gateway->sendMessage($request->input('phone'), FormatHelper::cleanCaption($request->input('message')));
        return response()->json($res);
    }

    /**
     * Send WhatsApp file / PDF document
     */
    public function sendFile(Request $request): JsonResponse
    {
        $fileData = $request->input('file') ?? $request->input('fileBase64');
        if (!$fileData) {
            return response()->json([
                'success' => false,
                'message' => 'Berkas PDF (file / fileBase64) wajib disertakan.',
            ], 422);
        }

        $request->validate([
            'phone' => 'required|string',
            'filename' => 'nullable|string',
            'caption' => 'nullable|string',
        ]);

        $res = $this->gateway->sendFile(
            $request->input('phone'),
            $fileData,
            $request->input('filename', 'Nota.pdf'),
            FormatHelper::cleanCaption($request->input('caption', ''))
        );

        return response()->json($res);
    }

    /**
     * Preview formatted WhatsApp receipt text & structured data for PDF generation
     */
    public function previewPurchase(PurchaseTransaction $purchase): JsonResponse
    {
        $purchase->loadMissing(['items.scrapItem', 'user', 'paymentMethod', 'supplier']);

        $phone = $purchase->supplier_phone;
        if (empty($phone) && $purchase->supplier) {
            $phone = $purchase->supplier->phone;
        }

        $message = $this->gateway->buildPurchaseReceiptMessage($purchase);

        $companyName = BusinessSetting::get('company_name', 'UD. BERKAH RONGSOK MAKMUR');
        $companyAddress = BusinessSetting::get('company_address', 'Sentra Daur Ulang Logam & Rongsok');
        $companyPhone = BusinessSetting::get('company_phone', '0812-3456-7890');
        $disclaimer = BusinessSetting::get('receipt_disclaimer', 'TIDAK MENERIMA BARANG BUKAN MILIK PRIBADI');
        $footerText = BusinessSetting::get('receipt_footer', 'Barang yang telah ditimbang dan dibayar tidak dapat ditarik kembali.');

        $activeItems = $purchase->items->where('is_cancelled', false);
        $activeGross = $activeItems->sum(fn($i) => ($i->unit_type === 'pcs' || $i->unit_type === 'satuan') ? ($i->gross_weight > 0 ? $i->gross_weight : ($i->quantity + $i->tare_weight)) : $i->gross_weight);
        $activeTare = $activeItems->sum('tare_weight');
        $activeNet = $activeItems->sum(fn($i) => ($i->unit_type === 'pcs' || $i->unit_type === 'satuan') ? $i->quantity : $i->net_weight);
        $activeSubtotal = $activeItems->sum('subtotal');
        $discount = min((float) ($purchase->discount_amount ?? 0), $activeSubtotal);
        $rounding = (float) ($purchase->rounding_amount ?? 0);
        $activeFinal = max(0, $activeSubtotal - $discount + $rounding);
        $debtDeduction = min((float) ($purchase->debt_deduction_amount ?? 0), $activeFinal);
        $activeNetCash = max(0, $activeFinal - $debtDeduction);

        $cleanInvoice = str_replace(['/', '\\', ' '], '-', $purchase->invoice_number);
        $cleanSupplier = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $purchase->supplier_name ?: 'Pemasok_Umum');
        $cleanSupplier = trim(preg_replace('/_+/', '_', $cleanSupplier), '_');
        $filename = "Nota_{$cleanInvoice}_{$cleanSupplier}.pdf";

        $items = [];
        foreach ($activeItems as $it) {
            $isPcs = ($it->unit_type === 'pcs' || $it->unit_type === 'satuan');
            $grossQty = $isPcs ? ($it->gross_weight > 0 ? $it->gross_weight : ($it->quantity + $it->tare_weight)) : $it->gross_weight;
            $netQty = $isPcs ? $it->quantity : $it->net_weight;
            $unitStr = $it->unit_type ?: 'kg';

            $items[] = [
                'name' => $it->item_name_snapshot,
                'bruto' => FormatHelper::weight($grossQty),
                'tara' => FormatHelper::weight($it->tare_weight),
                'netto' => FormatHelper::weight($netQty),
                'unit' => $unitStr,
                'price' => number_format($it->price_per_kg, 0, ',', '.'),
                'priceFormatted' => 'Rp ' . number_format($it->price_per_kg, 0, ',', '.'),
                'subtotal' => number_format($it->subtotal, 0, ',', '.'),
                'subtotalFormatted' => 'Rp ' . number_format($it->subtotal, 0, ',', '.'),
            ];
        }

        $receiptData = [
            'type' => 'purchase',
            'invoiceNumber' => $purchase->invoice_number,
            'filename' => $filename,
            'transactionDate' => Carbon::parse($purchase->transaction_date)->translatedFormat('d F Y, H:i') . ' WIB',
            'cashierName' => $purchase->user?->name ?? 'Kasir Timbang',
            'supplierName' => $purchase->supplier_name ?: '-',
            'supplierPhone' => $purchase->supplier_phone ?? '-',
            'paymentMethod' => $purchase->paymentMethod?->name ?? 'Tunai',
            'isBankPayment' => $purchase->isBankPayment(),
            'bankAccountNumber' => $purchase->getBankAccountNumber(),
            'bankAccountName' => $purchase->getBankAccountName(),
            'bankName' => $purchase->getBankName(),
            'bankInfo' => $purchase->bank_info,
            'companyName' => $companyName,
            'companyAddress' => $companyAddress,
            'companyPhone' => $companyPhone,
            'disclaimer' => $disclaimer,
            'footerText' => $footerText,
            'totalNetWeight' => FormatHelper::weight($activeNet),
            'totalGrossWeight' => FormatHelper::weight($activeGross),
            'totalTareWeight' => FormatHelper::weight($activeTare),
            'subtotalAmount' => 'Rp ' . number_format($activeFinal, 0, ',', '.'),
            'debtDeductionAmount' => $debtDeduction > 0 ? number_format($debtDeduction, 0, ',', '.') : null,
            'netCashPaid' => 'Rp ' . number_format($activeNetCash, 0, ',', '.'),
            'remainingDebtAfter' => $purchase->remaining_debt_after > 0 ? number_format($purchase->remaining_debt_after, 0, ',', '.') : null,
            'items' => $items,
        ];

        return response()->json([
            'phone' => $phone ?? '',
            'formatted_phone' => $this->gateway->formatPhone($phone),
            'message' => FormatHelper::cleanCaption($message),
            'invoice_number' => $purchase->invoice_number,
            'filename' => $filename,
            'pdf_filename' => $filename,
            'pdf_url' => route('purchases.pdf', $purchase),
            'receipt_data' => $receiptData,
        ]);
    }

    /**
     * Send Purchase Receipt (PDF or Text) via WhatsApp Gateway
     */
    public function sendPurchase(Request $request, PurchaseTransaction $purchase): JsonResponse
    {
        $targetPhone = $request->input('phone') ?: $purchase->supplier_phone;
        if (empty($targetPhone) && $purchase->supplier) {
            $targetPhone = $purchase->supplier->phone;
        }

        if (empty($targetPhone)) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp tujuan wajib diisi.',
            ], 422);
        }

        $fileData = $request->input('file') ?? $request->input('fileBase64');
        $caption = FormatHelper::cleanCaption($request->input('caption') ?? $request->input('message') ?? "Terlampir Nota Pembelian PDF resmi {$purchase->invoice_number}.");
        $cleanInvoice = str_replace(['/', '\\', ' '], '-', $purchase->invoice_number);
        $cleanSupplier = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $purchase->supplier_name ?: 'Pemasok_Umum');
        $cleanSupplier = trim(preg_replace('/_+/', '_', $cleanSupplier), '_');
        $defaultFilename = "Nota_{$cleanInvoice}_{$cleanSupplier}.pdf";
        $filename = $request->input('filename') ?: $defaultFilename;

        if ($fileData) {
            // Send as PDF Document
            $res = $this->gateway->sendFile($targetPhone, $fileData, $filename, $caption);
        } else {
            // Send as Text Message
            $customMessage = $request->input('message');
            if (!empty($customMessage)) {
                $res = $this->gateway->sendMessage($targetPhone, $customMessage);
            } else {
                $res = $this->gateway->sendPurchaseReceipt($purchase, $targetPhone);
            }
        }

        return response()->json($res);
    }

    /**
     * Preview formatted WhatsApp invoice text for wholesale sales
     */
    public function previewSale(SalesTransaction $sale): JsonResponse
    {
        $sale->loadMissing(['items.scrapItem', 'user', 'paymentMethod']);

        $phone = $sale->buyer_phone ?: $sale->customer_phone;
        $message = $this->gateway->buildSalesInvoiceMessage($sale);

        $companyName = BusinessSetting::get('company_name', 'UD. BERKAH RONGSOK MAKMUR');
        $companyAddress = BusinessSetting::get('company_address', 'Sentra Daur Ulang Logam & Rongsok');
        $companyPhone = BusinessSetting::get('company_phone', '0812-3456-7890');

        $items = [];
        foreach ($sale->items as $it) {
            $items[] = [
                'name' => $it->item_name_snapshot,
                'weight' => FormatHelper::weight($it->weight),
                'sellPrice' => number_format($it->sell_price_per_kg, 0, ',', '.'),
                'subtotal' => number_format($it->subtotal, 0, ',', '.'),
                'subtotalFormatted' => 'Rp ' . number_format($it->subtotal, 0, ',', '.'),
            ];
        }

        $customerName = $sale->buyer_name ?: $sale->customer_name ?: 'Pabrik / Pembeli';
        $customerPhone = $sale->buyer_phone ?: $sale->customer_phone ?: '-';
        $filename = "Invoice_{$sale->invoice_number}.pdf";

        $receiptData = [
            'type' => 'sale',
            'invoiceNumber' => $sale->invoice_number,
            'transactionDate' => Carbon::parse($sale->transaction_date)->translatedFormat('d F Y, H:i') . ' WIB',
            'customerName' => $customerName,
            'customerPhone' => $customerPhone,
            'companyName' => $companyName,
            'companyAddress' => $companyAddress,
            'companyPhone' => $companyPhone,
            'totalWeight' => FormatHelper::weight($sale->total_weight),
            'finalAmount' => 'Rp ' . number_format($sale->final_amount, 0, ',', '.'),
            'items' => $items,
        ];

        return response()->json([
            'phone' => $phone ?? '',
            'formatted_phone' => $this->gateway->formatPhone($phone),
            'message' => FormatHelper::cleanCaption($message),
            'invoice_number' => $sale->invoice_number,
            'filename' => $filename,
            'pdf_filename' => $filename,
            'pdf_url' => route('sales.pdf', $sale),
            'receipt_data' => $receiptData,
        ]);
    }

    /**
     * Send Sales Invoice (PDF or Text) via WhatsApp Gateway
     */
    public function sendSale(Request $request, SalesTransaction $sale): JsonResponse
    {
        $targetPhone = $request->input('phone') ?: $sale->buyer_phone ?: $sale->customer_phone;
        if (empty($targetPhone)) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp pembeli wajib diisi.',
            ], 422);
        }

        $fileData = $request->input('file') ?? $request->input('fileBase64');
        $caption = FormatHelper::cleanCaption($request->input('caption') ?? $request->input('message') ?? "Terlampir Faktur Penjualan Partai PDF {$sale->invoice_number}.");
        $filename = $request->input('filename') ?: "Faktur_{$sale->invoice_number}.pdf";

        if ($fileData) {
            $res = $this->gateway->sendFile($targetPhone, $fileData, $filename, $caption);
        } else {
            $customMessage = $request->input('message');
            if (!empty($customMessage)) {
                $res = $this->gateway->sendMessage($targetPhone, FormatHelper::cleanCaption($customMessage));
            } else {
                $res = $this->gateway->sendSalesInvoice($sale, $targetPhone);
            }
        }

        return response()->json($res);
    }

    /**
     * Preview formatted WhatsApp receipt text for debt payment
     */
    public function previewDebtPayment(DebtPayment $payment): JsonResponse
    {
        $payment->loadMissing(['debt.supplier', 'paymentMethod', 'user']);

        $phone = $payment->debt?->supplier?->phone;
        $message = $this->gateway->buildDebtPaymentMessage($payment);

        $companyName = BusinessSetting::get('company_name', 'UD. BERKAH RONGSOK MAKMUR');
        $companyPhone = BusinessSetting::get('company_phone', '0812-3456-7890');

        $receiptData = [
            'type' => 'debt_payment',
            'paymentNumber' => $payment->payment_number,
            'paymentDate' => Carbon::parse($payment->payment_date)->translatedFormat('d F Y, H:i') . ' WIB',
            'supplierName' => $payment->debt?->supplier?->name ?? 'Pemasok',
            'supplierPhone' => $phone ?? '-',
            'paymentMethod' => $payment->paymentMethod?->name ?? 'Tunai',
            'amount' => 'Rp ' . number_format($payment->amount, 0, ',', '.'),
            'remainingBefore' => 'Rp ' . number_format($payment->remaining_before, 0, ',', '.'),
            'remainingAfter' => 'Rp ' . number_format($payment->remaining_after, 0, ',', '.'),
            'companyName' => $companyName,
            'companyPhone' => $companyPhone,
        ];

        return response()->json([
            'phone' => $phone ?? '',
            'formatted_phone' => $this->gateway->formatPhone($phone),
            'message' => FormatHelper::cleanCaption($message),
            'payment_number' => $payment->payment_number,
            'receipt_data' => $receiptData,
        ]);
    }

    /**
     * Send Debt Payment Receipt (PDF or Text) via WhatsApp Gateway
     */
    public function sendDebtPayment(Request $request, DebtPayment $payment): JsonResponse
    {
        $targetPhone = $request->input('phone') ?: $payment->debt?->supplier?->phone;
        if (empty($targetPhone)) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor WhatsApp pemasok wajib diisi.',
            ], 422);
        }

        $fileData = $request->input('file') ?? $request->input('fileBase64');
        $caption = FormatHelper::cleanCaption($request->input('caption') ?? $request->input('message') ?? "Terlampir Bukti Bayar Kasbon PDF {$payment->payment_number}.");
        $filename = $request->input('filename') ?: "BuktiBayar_{$payment->payment_number}.pdf";

        if ($fileData) {
            $res = $this->gateway->sendFile($targetPhone, $fileData, $filename, $caption);
        } else {
            $customMessage = $request->input('message');
            if (!empty($customMessage)) {
                $res = $this->gateway->sendMessage($targetPhone, $customMessage);
            } else {
                $res = $this->gateway->sendDebtPaymentReceipt($payment, $targetPhone);
            }
        }

        return response()->json($res);
    }
}
