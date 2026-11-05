<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Models\BusinessSetting;
use App\Models\ItemCategory;
use App\Models\PaymentMethod;
use App\Models\PurchaseTransaction;
use App\Models\ScrapItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseTransactionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PurchaseTransactionController extends Controller
{
    public function __construct(
        protected PurchaseTransactionService $purchaseService
    ) {}

    /**
     * Cashier Screen - Fast, ergonomic POS for scrap intake & weighing.
     */
    public function create(): View
    {
        $scrapItems = ScrapItem::with(['category', 'stockBalance'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                $arr = $item->toArray();
                $arr['current_stock'] = (float) ($item->stockBalance?->current_stock ?? 0);
                return $arr;
            });

        $categories = ItemCategory::where('is_active', true)
            ->orderBy('id')
            ->get();

        $suppliers = Supplier::where('is_active', true)
            ->withSum(['debts as total_debt' => function ($q) {
                $q->where('remaining_amount', '>', 0);
            }], 'remaining_amount')
            ->withSum(['purchaseTransactions as total_weight' => function ($q) {
                $q->where('status', 'completed');
            }], 'total_net_weight')
            ->withSum(['purchaseTransactions as total_amount' => function ($q) {
                $q->where('status', 'completed');
            }], 'final_amount')
            ->withCount(['purchaseTransactions as total_transactions_count' => function ($q) {
                $q->where('status', 'completed');
            }])
            ->orderBy('name')
            ->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        $defaultPrinter = BusinessSetting::get('default_printer_type', 'thermal_58');

        return view('purchases.create', compact('scrapItems', 'categories', 'suppliers', 'paymentMethods', 'defaultPrinter'));
    }

    /**
     * Store new purchase transaction.
     */
    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        try {
            $transaction = $this->purchaseService->createPurchase(
                $request->validated(),
                Auth::user()
            );

            return redirect()->route('purchases.show', $transaction)
                ->with('success', "Transaksi {$transaction->invoice_number} berhasil disimpan!")
                ->with('auto_print', true);
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Transaction History List.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $status = $request->query('status');
        $cashierId = $request->query('cashier_id');

        $query = PurchaseTransaction::with(['user', 'supplier', 'paymentMethod', 'items'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_number', 'LIKE', "%{$search}%")
                        ->orWhere('supplier_name', 'LIKE', "%{$search}%");
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
            ->when($cashierId, function ($q) use ($cashierId) {
                $q->where('user_id', $cashierId);
            })
            ->latest('transaction_date');

        $transactions = $query->paginate(15)->withQueryString();
        $cashiers = User::where('is_active', true)->get();

        return view('purchases.index', compact('transactions', 'search', 'startDate', 'endDate', 'status', 'cashierId', 'cashiers'));
    }

    /**
     * Show single purchase detail.
     */
    public function show(PurchaseTransaction $purchase): View
    {
        $purchase->load(['items.scrapItem.category', 'user', 'supplier.debts', 'paymentMethod', 'cancelledByUser', 'debtPayments.debt']);
        $settings = BusinessSetting::pluck('value', 'key')->all();

        return view('purchases.show', compact('purchase', 'settings'));
    }

    /**
     * Print thermal / A4 receipt view.
     */
    public function printReceipt(Request $request, PurchaseTransaction $purchase): View
    {
        $purchase->load(['items.scrapItem', 'user', 'supplier.debts', 'paymentMethod', 'debtPayments.debt']);
        $format = $request->query('format', BusinessSetting::get('default_printer_type', 'thermal_58'));
        $settings = BusinessSetting::pluck('value', 'key')->all();

        return view('purchases.print', compact('purchase', 'format', 'settings'));
    }

    /**
     * Batch print all purchases matching current filter (Point A - Format 58mm Kertas HVS A4).
     */
    public function batchPrint(Request $request): View
    {
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $status = $request->query('status', 'completed');
        $cashierId = $request->query('cashier_id');

        $purchases = PurchaseTransaction::with(['user', 'supplier.debts', 'paymentMethod', 'items.scrapItem'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_number', 'LIKE', "%{$search}%")
                        ->orWhere('supplier_name', 'LIKE', "%{$search}%");
                });
            })
            ->when($startDate, fn($q) => $q->whereDate('transaction_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('transaction_date', '<=', $endDate))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($cashierId, fn($q) => $q->where('user_id', $cashierId))
            ->latest('transaction_date')
            ->take(150)
            ->get();

        $settings = BusinessSetting::pluck('value', 'key')->all();

        return view('purchases.batch_print', compact('purchases', 'settings', 'startDate', 'endDate'));
    }

    /**
     * Download Batch PDF of all purchases matching filter (Point A & D - Format 58mm Kertas HVS A4).
     */
    public function batchPdf(Request $request)
    {
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $status = $request->query('status', 'completed');
        $cashierId = $request->query('cashier_id');

        $purchases = PurchaseTransaction::with(['user', 'supplier.debts', 'paymentMethod', 'items.scrapItem'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_number', 'LIKE', "%{$search}%")
                        ->orWhere('supplier_name', 'LIKE', "%{$search}%");
                });
            })
            ->when($startDate, fn($q) => $q->whereDate('transaction_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('transaction_date', '<=', $endDate))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($cashierId, fn($q) => $q->where('user_id', $cashierId))
            ->latest('transaction_date')
            ->take(150)
            ->get();

        $totalWeight = (float) $purchases->sum('total_net_weight');
        $totalAmount = (float) $purchases->sum('final_amount');
        $settings = BusinessSetting::pluck('value', 'key')->all();

        $pdf = Pdf::loadView('purchases.batch_pdf', compact('purchases', 'settings', 'startDate', 'endDate', 'totalWeight', 'totalAmount'))
            ->setPaper('a4', 'portrait');

        $dateSuffix = ($startDate && $endDate) ? "{$startDate}_sd_{$endDate}" : date('Ymd_His');
        $filename = "Semua_Nota_Pembelian_{$dateSuffix}.pdf";

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Export Excel for Purchases (Task 18).
     */
    public function exportExcel(Request $request)
    {
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $status = $request->query('status');
        $cashierId = $request->query('cashier_id');

        $purchases = PurchaseTransaction::with(['user', 'supplier', 'paymentMethod', 'items'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_number', 'LIKE', "%{$search}%")
                        ->orWhere('supplier_name', 'LIKE', "%{$search}%");
                });
            })
            ->when($startDate, fn($q) => $q->whereDate('transaction_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('transaction_date', '<=', $endDate))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($cashierId, fn($q) => $q->where('user_id', $cashierId))
            ->latest('transaction_date')
            ->get();

        $filename = 'Rekap_Pembelian_' . date('Ymd_His') . '.xls';

        return response()->view('purchases.excel', compact('purchases', 'startDate', 'endDate'))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Preview Global WhatsApp message and batch PDF metadata (Point B & C).
     */
    public function previewWaGlobal(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $status = $request->query('status', 'completed');
        $cashierId = $request->query('cashier_id');

        $purchases = PurchaseTransaction::with(['user', 'supplier', 'paymentMethod', 'items.scrapItem'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_number', 'LIKE', "%{$search}%")
                        ->orWhere('supplier_name', 'LIKE', "%{$search}%");
                });
            })
            ->when($startDate, fn($q) => $q->whereDate('transaction_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('transaction_date', '<=', $endDate))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($cashierId, fn($q) => $q->where('user_id', $cashierId))
            ->latest('transaction_date')
            ->take(150)
            ->get();

        $count = $purchases->count();
        $totalWeight = (float) $purchases->sum('total_net_weight');
        $totalAmount = (float) $purchases->sum('final_amount');
        $companyName = BusinessSetting::get('company_name', 'UD. BERKAH RONGSOK MAKMUR');
        $periodStr = ($startDate && $endDate) ? "{$startDate} s/d {$endDate}" : date('d/m/Y');

        $msg = "*REKAPITULASI NOTA PEMBELIAN RONGSOK*\n";
        $msg .= "*{$companyName}*\n";
        $msg .= "Periode: {$periodStr}\n\n";
        $msg .= "🔖 Total Transaksi : *{$count} Nota*\n";
        $msg .= "⚖️ Total Berat Bersih: *" . \App\Services\FormatHelper::weight($totalWeight) . " kg*\n";
        $msg .= "💰 Total Pembelian   : *" . \App\Services\FormatHelper::rupiah($totalAmount) . "*\n\n";

        if ($count > 0) {
            $msg .= "📋 *Ringkasan Nota Terbaru:*\n";
            $previewList = $purchases->take(8);
            foreach ($previewList as $idx => $p) {
                $num = $idx + 1;
                $pWeight = \App\Services\FormatHelper::weight($p->total_net_weight);
                $pTotal = \App\Services\FormatHelper::rupiah($p->final_amount);
                $msg .= "{$num}. {$p->invoice_number} | {$p->supplier_name} ({$pWeight} kg) - {$pTotal}\n";
            }
            if ($count > 8) {
                $remaining = $count - 8;
                $msg .= "... dan {$remaining} nota lainnya terlampir di berkas PDF.\n";
            }
            $msg .= "\n";
        }
        $msg .= "📄 *Terlampir berkas PDF dokumen semua nota (Format 58mm Kertas HVS A4)*.\n";
        $msg .= "Laporan digenerate otomatis dari sistem Depo Kasir Rongsok.";

        $dateSuffix = ($startDate && $endDate) ? "{$startDate}_sd_{$endDate}" : date('Ymd_His');
        $pdfFilename = "Semua_Nota_Pembelian_{$dateSuffix}.pdf";
        $pdfUrl = route('purchases.batch-pdf', $request->query());

        return response()->json([
            'success' => true,
            'count' => $count,
            'total_weight' => $totalWeight,
            'total_amount' => $totalAmount,
            'message' => $msg,
            'pdf_filename' => $pdfFilename,
            'pdf_url' => $pdfUrl,
        ]);
    }

    /**
     * Send Global Summary and/or combined batch PDF via WhatsApp (Point B & C).
     */
    public function sendWaGlobal(Request $request)
    {
        $phone = $request->input('phone');
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $status = $request->input('status', 'completed');
        $cashierId = $request->input('cashier_id');
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

        $purchases = PurchaseTransaction::with(['user', 'supplier.debts', 'paymentMethod', 'items.scrapItem'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_number', 'LIKE', "%{$search}%")
                        ->orWhere('supplier_name', 'LIKE', "%{$search}%");
                });
            })
            ->when($startDate, fn($q) => $q->whereDate('transaction_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('transaction_date', '<=', $endDate))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($cashierId, fn($q) => $q->where('user_id', $cashierId))
            ->latest('transaction_date')
            ->take(150)
            ->get();

        $count = $purchases->count();
        $totalWeight = (float) $purchases->sum('total_net_weight');
        $totalAmount = (float) $purchases->sum('final_amount');
        $companyName = BusinessSetting::get('company_name', 'UD. BERKAH RONGSOK MAKMUR');
        $periodStr = ($startDate && $endDate) ? "{$startDate} s/d {$endDate}" : date('d/m/Y');

        if (empty($caption)) {
            $caption = "*REKAPITULASI NOTA PEMBELIAN RONGSOK*\n";
            $caption .= "*{$companyName}*\n";
            $caption .= "Periode: {$periodStr}\n\n";
            $caption .= "🔖 Total Transaksi : *{$count} Nota*\n";
            $caption .= "⚖️ Total Berat Bersih: *" . \App\Services\FormatHelper::weight($totalWeight) . " kg*\n";
            $caption .= "💰 Total Pembelian   : *" . \App\Services\FormatHelper::rupiah($totalAmount) . "*\n\n";
            $caption .= "📄 Terlampir berkas PDF dokumen semua nota (Format 58mm Kertas HVS A4).";
        }

        $waService = app(\App\Services\WhatsAppGatewayService::class);
        $dateSuffix = ($startDate && $endDate) ? "{$startDate}_sd_{$endDate}" : date('Ymd_His');
        $filename = "Semua_Nota_Pembelian_{$dateSuffix}.pdf";

        if ($sendPdf) {
            // Generate combined A4 HVS PDF (Point B)
            $settings = BusinessSetting::pluck('value', 'key')->all();
            $pdf = Pdf::loadView('purchases.batch_pdf', compact('purchases', 'settings', 'startDate', 'endDate', 'totalWeight', 'totalAmount'))
                ->setPaper('a4', 'portrait');
            $pdfRaw = $pdf->output();
            $fileBase64 = base64_encode($pdfRaw);

            $res = $waService->sendFile($phone, $fileBase64, $filename, $caption);
        } else {
            // Send pure text summary
            $res = $waService->sendMessage($phone, $caption);
        }

        if ($request->wantsJson()) {
            return response()->json($res);
        }

        return back()->with($res['success'] ? 'success' : 'error', $res['message'] ?? 'Status terkirim.');
    }

    /**
     * Cancel / void transaction with stock reversal.
     */
    public function cancel(Request $request, PurchaseTransaction $purchase): RedirectResponse
    {
        $request->validate([
            'cancel_reason' => 'required|string|min:3|max:255',
        ], [
            'cancel_reason.required' => 'Alasan pembatalan transaksi wajib diisi.',
        ]);

        try {
            $this->purchaseService->cancelPurchase($purchase, Auth::user(), $request->input('cancel_reason'));
            return back()->with('success', "Transaksi {$purchase->invoice_number} berhasil dibatalkan dan stok dikembalikan.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

