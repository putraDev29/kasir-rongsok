<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\DeliveryOrderController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PurchaseTransactionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalarySlipController;
use App\Http\Controllers\SalesTransactionController;
use App\Http\Controllers\ScrapItemController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsAppGatewayController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Dynamic PWA Web Manifest (uses custom uploaded logo & business profile)
Route::get('/manifest.json', function () {
    $appName = \App\Models\BusinessSetting::get('app_name', 'Kasir Rongsok');
    $companyName = \App\Models\BusinessSetting::get('company_name', 'UD. Berkah Rongsok Makmur');
    $subtitle = \App\Models\BusinessSetting::get('app_subtitle', 'Sistem Depo & POS Timbang');
    $logo = \App\Models\BusinessSetting::get('app_logo');

    $icons = [];
    $hasPwa192 = file_exists(public_path('uploads/logo/pwa_icon_192.png'));
    $hasPwa512 = file_exists(public_path('uploads/logo/pwa_icon_512.png'));
    $hasPwaMaskable = file_exists(public_path('uploads/logo/pwa_icon_maskable.png'));

    if ($hasPwa192 && $hasPwa512) {
        $icons[] = [
            'src' => '/uploads/logo/pwa_icon_192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any',
        ];
        $icons[] = [
            'src' => '/uploads/logo/pwa_icon_512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any',
        ];
        if ($hasPwaMaskable) {
            $icons[] = [
                'src' => '/uploads/logo/pwa_icon_maskable.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ];
        }
    } else {
        $mainLogo = ($logo && file_exists(public_path($logo))) ? '/' . ltrim($logo, '/') : '/icon-default.svg';
        $isSvg = str_ends_with(strtolower($mainLogo), '.svg');
        $icons = [
            [
                'src' => $mainLogo,
                'sizes' => $isSvg ? 'any' : '192x192',
                'type' => $isSvg ? 'image/svg+xml' : 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => $mainLogo,
                'sizes' => $isSvg ? 'any' : '512x512',
                'type' => $isSvg ? 'image/svg+xml' : 'image/png',
                'purpose' => 'any maskable',
            ],
        ];
    }

    return response()->json([
        'id' => '/?source=pwa',
        'name' => $companyName ? "{$appName} - {$companyName}" : $appName,
        'short_name' => $appName,
        'description' => $subtitle,
        'start_url' => '/',
        'scope' => '/',
        'display' => 'standalone',
        'background_color' => '#0f172a',
        'theme_color' => '#059669',
        'orientation' => 'any',
        'icons' => $icons,
    ], 200, [
        'Content-Type' => 'application/manifest+json; charset=UTF-8',
        'Cache-Control' => 'no-cache, private',
    ]);
})->name('manifest.json');

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated Routes
Route::middleware('auth')->group(function () {

    // Root Redirect
    Route::get('/', function () {
        return Auth::user()->isKasir()
            ? redirect()->route('purchases.create')
            : redirect()->route('dashboard');
    });

    // Dashboard (Owner, Admin, Kasir)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('role:super_admin,admin,kasir');

    // Shortcuts for cashier POS
    Route::get('/pos', [PurchaseTransactionController::class, 'create'])->name('pos');
    Route::get('/kasir', [PurchaseTransactionController::class, 'create'])->name('kasir');

    // Core Cashier: Purchases Transactions (Accessible by Kasir, Admin, Super Admin / Owner)
    Route::resource('purchases', PurchaseTransactionController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/purchases/{purchase}/print', [PurchaseTransactionController::class, 'printReceipt'])->name('purchases.print');
    Route::post('/purchases/{purchase}/cancel', [PurchaseTransactionController::class, 'cancel'])
        ->name('purchases.cancel')
        ->middleware('role:super_admin');

    // Scrap Items (Master Barang) & PDF Price List (Hanya Owner)
    Route::get('/scrap-items/print-price-list', [ScrapItemController::class, 'printPriceList'])
        ->name('scrap-items.print-price-list')
        ->middleware('role:super_admin');
    Route::resource('scrap-items', ScrapItemController::class)
        ->middleware('role:super_admin');
    Route::post('/scrap-items/{scrap_item}/quick-price', [ScrapItemController::class, 'updatePrice'])
        ->name('scrap-items.quick-price')
        ->middleware('role:super_admin');

    // Suppliers (Pemasok) - Hanya Owner
    Route::resource('suppliers', SupplierController::class)
        ->except(['create', 'show', 'edit'])
        ->middleware('role:super_admin');

    // Hutang Piutang / Pinjaman Modal (Debts & Loans) - Hanya Owner
    Route::resource('debts', DebtController::class)
        ->only(['index', 'store', 'show'])
        ->middleware('role:super_admin');
    Route::post('/debts/{debt}/pay', [DebtController::class, 'pay'])
        ->name('debts.pay')
        ->middleware('role:super_admin');
    Route::get('/debts/payments/{payment}/print', [DebtController::class, 'printPayment'])
        ->name('debts.print-payment')
        ->middleware('role:super_admin');

    // Stock Management & Movement - Hanya Owner
    Route::get('/stock', [StockController::class, 'index'])
        ->name('stock.index')
        ->middleware('role:super_admin');
    Route::get('/stock/movements', [StockController::class, 'movements'])
        ->name('stock.movements')
        ->middleware('role:super_admin');
    Route::post('/stock/{scrap_item}/adjust', [StockController::class, 'adjust'])
        ->name('stock.adjust')
        ->middleware('role:super_admin');

    // Bulk Sales of Scrap (Penjualan Partai Besar) - Hanya Owner
    Route::resource('sales', SalesTransactionController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->middleware('role:super_admin');
    Route::get('/sales/{sale}/print', [SalesTransactionController::class, 'printInvoice'])
        ->name('sales.print')
        ->middleware('role:super_admin');
    Route::post('/sales/{sale}/cancel', [SalesTransactionController::class, 'cancel'])
        ->name('sales.cancel')
        ->middleware('role:super_admin');

    // Surat Jalan / Delivery Orders (Pengiriman Armada Truk) - Hanya Owner
    Route::resource('delivery-orders', DeliveryOrderController::class)
        ->except(['edit', 'update'])
        ->middleware('role:super_admin');
    Route::get('/delivery-orders/{delivery_order}/print', [DeliveryOrderController::class, 'print'])
        ->name('delivery-orders.print')
        ->middleware('role:super_admin');

    // Expenses (Pengeluaran Operasional) - Hanya Owner
    Route::resource('expenses', ExpenseController::class)
        ->only(['index', 'store', 'destroy'])
        ->middleware('role:super_admin');

    // Slip Gaji Karyawan (Salary Slips) - Hanya Owner
    Route::resource('salary-slips', SalarySlipController::class)
        ->except(['edit', 'update'])
        ->middleware('role:super_admin');
    Route::get('/salary-slips/{salary_slip}/print', [SalarySlipController::class, 'print'])
        ->name('salary-slips.print')
        ->middleware('role:super_admin');

    // Reports (Laporan) - Hanya Owner
    Route::prefix('reports')->name('reports.')->middleware('role:super_admin')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/purchases', [ReportController::class, 'purchases'])->name('purchases');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
        Route::get('/expenses', [ReportController::class, 'expenses'])->name('expenses');
        Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
        Route::get('/cashier', [ReportController::class, 'cashier'])->name('cashier');
    });

    // User Management (Super Admin / Owner only)
    Route::resource('users', UserController::class)->except(['create', 'show', 'edit'])
        ->middleware('role:super_admin');

    // Business Settings (Super Admin / Owner only)
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index')
        ->middleware('role:super_admin');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update')
        ->middleware('role:super_admin');

    // WhatsApp Gateway (WPPConnect Integration)
    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::get('/status', [WhatsAppGatewayController::class, 'status'])->name('status');
        Route::get('/qr', [WhatsAppGatewayController::class, 'qr'])->name('qr');
        Route::post('/reconnect', [WhatsAppGatewayController::class, 'reconnect'])->name('reconnect')->middleware('role:super_admin');
        Route::post('/logout', [WhatsAppGatewayController::class, 'logout'])->name('logout')->middleware('role:super_admin');
        Route::post('/clear-chat', [WhatsAppGatewayController::class, 'clearChat'])->name('clear-chat')->middleware('role:super_admin');
        Route::post('/send', [WhatsAppGatewayController::class, 'send'])->name('send');
        Route::post('/send-file', [WhatsAppGatewayController::class, 'sendFile'])->name('send-file');

        // Purchase Receipts (Kasir, Admin, Super Admin)
        Route::get('/purchases/{purchase}/preview', [WhatsAppGatewayController::class, 'previewPurchase'])->name('purchases.preview');
        Route::post('/purchases/{purchase}/send', [WhatsAppGatewayController::class, 'sendPurchase'])->name('purchases.send');

        // Sales Invoices (Hanya Owner)
        Route::get('/sales/{sale}/preview', [WhatsAppGatewayController::class, 'previewSale'])->name('sales.preview')->middleware('role:super_admin');
        Route::post('/sales/{sale}/send', [WhatsAppGatewayController::class, 'sendSale'])->name('sales.send')->middleware('role:super_admin');

        // Debt Payments (Hanya Owner)
        Route::get('/debt-payments/{payment}/preview', [WhatsAppGatewayController::class, 'previewDebtPayment'])->name('debt-payments.preview')->middleware('role:super_admin');
        Route::post('/debt-payments/{payment}/send', [WhatsAppGatewayController::class, 'sendDebtPayment'])->name('debt-payments.send')->middleware('role:super_admin');
    });
});
