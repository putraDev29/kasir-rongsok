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

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('role:super_admin,admin');

    // Shortcuts for cashier POS
    Route::get('/pos', [PurchaseTransactionController::class, 'create'])->name('pos');
    Route::get('/kasir', [PurchaseTransactionController::class, 'create'])->name('kasir');

    // Core Cashier: Purchases Transactions (Accessible by Kasir, Admin, Super Admin)
    Route::resource('purchases', PurchaseTransactionController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/purchases/{purchase}/print', [PurchaseTransactionController::class, 'printReceipt'])->name('purchases.print');
    Route::post('/purchases/{purchase}/cancel', [PurchaseTransactionController::class, 'cancel'])
        ->name('purchases.cancel')
        ->middleware('role:super_admin,admin');

    // Scrap Items (Master Barang) & PDF Price List
    Route::get('/scrap-items/print-price-list', [ScrapItemController::class, 'printPriceList'])->name('scrap-items.print-price-list');
    Route::resource('scrap-items', ScrapItemController::class)->middleware('role:super_admin,admin');
    Route::post('/scrap-items/{scrap_item}/quick-price', [ScrapItemController::class, 'updatePrice'])
        ->name('scrap-items.quick-price')
        ->middleware('role:super_admin,admin');

    // Suppliers (Pemasok)
    Route::resource('suppliers', SupplierController::class)->except(['create', 'show', 'edit']);

    // Hutang Piutang / Pinjaman Modal (Debts & Loans)
    Route::resource('debts', DebtController::class)->only(['index', 'store', 'show']);
    Route::post('/debts/{debt}/pay', [DebtController::class, 'pay'])->name('debts.pay');
    Route::get('/debts/payments/{payment}/print', [DebtController::class, 'printPayment'])->name('debts.print-payment');

    // Stock Management & Movement
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/movements', [StockController::class, 'movements'])->name('stock.movements');
    Route::post('/stock/{scrap_item}/adjust', [StockController::class, 'adjust'])
        ->name('stock.adjust')
        ->middleware('role:super_admin,admin');

    // Bulk Sales of Scrap (Penjualan Partai Besar)
    Route::resource('sales', SalesTransactionController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/sales/{sale}/print', [SalesTransactionController::class, 'printInvoice'])->name('sales.print');
    Route::post('/sales/{sale}/cancel', [SalesTransactionController::class, 'cancel'])
        ->name('sales.cancel')
        ->middleware('role:super_admin,admin');

    // Surat Jalan / Delivery Orders (Pengiriman Armada Truk)
    Route::resource('delivery-orders', DeliveryOrderController::class)->except(['edit', 'update']);
    Route::get('/delivery-orders/{delivery_order}/print', [DeliveryOrderController::class, 'print'])->name('delivery-orders.print');

    // Expenses (Pengeluaran Operasional)
    Route::resource('expenses', ExpenseController::class)->only(['index', 'store', 'destroy'])
        ->middleware('role:super_admin,admin');

    // Slip Gaji Karyawan (Salary Slips)
    Route::resource('salary-slips', SalarySlipController::class)->except(['edit', 'update'])
        ->middleware('role:super_admin,admin');
    Route::get('/salary-slips/{salary_slip}/print', [SalarySlipController::class, 'print'])
        ->name('salary-slips.print')
        ->middleware('role:super_admin,admin');

    // Reports (Laporan)
    Route::prefix('reports')->name('reports.')->middleware('role:super_admin,admin')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/purchases', [ReportController::class, 'purchases'])->name('purchases');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
        Route::get('/expenses', [ReportController::class, 'expenses'])->name('expenses');
        Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
        Route::get('/cashier', [ReportController::class, 'cashier'])->name('cashier');
    });

    // User Management (Super Admin only)
    Route::resource('users', UserController::class)->except(['create', 'show', 'edit'])
        ->middleware('role:super_admin');

    // Business Settings (Super Admin only)
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index')
        ->middleware('role:super_admin');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update')
        ->middleware('role:super_admin');

    // WhatsApp Gateway (WPPConnect Integration)
    Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
        Route::get('/status', [WhatsAppGatewayController::class, 'status'])->name('status');
        Route::get('/qr', [WhatsAppGatewayController::class, 'qr'])->name('qr');
        Route::post('/reconnect', [WhatsAppGatewayController::class, 'reconnect'])->name('reconnect');
        Route::post('/logout', [WhatsAppGatewayController::class, 'logout'])->name('logout');
        Route::post('/clear-chat', [WhatsAppGatewayController::class, 'clearChat'])->name('clear-chat');
        Route::post('/send', [WhatsAppGatewayController::class, 'send'])->name('send');
        Route::post('/send-file', [WhatsAppGatewayController::class, 'sendFile'])->name('send-file');

        // Purchase Receipts (Kasir / Nota Timbang)
        Route::get('/purchases/{purchase}/preview', [WhatsAppGatewayController::class, 'previewPurchase'])->name('purchases.preview');
        Route::post('/purchases/{purchase}/send', [WhatsAppGatewayController::class, 'sendPurchase'])->name('purchases.send');

        // Sales Invoices (Faktur Penjualan Partai Pabrik)
        Route::get('/sales/{sale}/preview', [WhatsAppGatewayController::class, 'previewSale'])->name('sales.preview');
        Route::post('/sales/{sale}/send', [WhatsAppGatewayController::class, 'sendSale'])->name('sales.send');

        // Debt Payments (Bukti Bayar Kasbon)
        Route::get('/debt-payments/{payment}/preview', [WhatsAppGatewayController::class, 'previewDebtPayment'])->name('debt-payments.preview');
        Route::post('/debt-payments/{payment}/send', [WhatsAppGatewayController::class, 'sendDebtPayment'])->name('debt-payments.send');
    });
});
