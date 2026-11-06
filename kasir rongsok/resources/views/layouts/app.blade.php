<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Kasir Rongsok' }} - {{ config('app.name', 'Kasir Rongsok') }}</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @php
        $pwaLogo = \App\Models\BusinessSetting::logoUrl() ?: asset('icon-default.svg');
        $pwaAppName = \App\Models\BusinessSetting::get('app_name', 'Kasir Rongsok');
    @endphp

    <!-- Favicon & PWA Icons (Dynamic from Settings) -->
    <link rel="icon" type="image/png" href="{{ $pwaLogo }}">
    <link rel="apple-touch-icon" href="{{ $pwaLogo }}">
    <link rel="manifest" href="{{ route('manifest.json') }}">
    <meta name="theme-color" content="#059669">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $pwaAppName }}">
    <meta name="wa-gateway-enabled" content="{{ config('services.whatsapp_gateway.enabled') ? '1' : '0' }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full flex flex-col font-sans text-slate-800 antialiased" x-data="{ sidebarOpen: false }">

    <!-- Mobile Sidebar Drawer Backdrop -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-slate-900/60 lg:hidden" 
         style="display: none;"></div>

    <div class="flex h-full min-h-screen overflow-hidden">
        
        <!-- SIDEBAR -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-200 flex flex-col transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 shrink-0 no-print">
            
            @php
                $appLogo = \App\Models\BusinessSetting::logoUrl();
                $appName = \App\Models\BusinessSetting::get('app_name', 'KASIR RONGSOK');
                $appSubtitle = \App\Models\BusinessSetting::get('app_subtitle', 'Sistem Depo & POS');
                $companyName = \App\Models\BusinessSetting::get('company_name', 'UD. Berkah Rongsok Makmur');
            @endphp

            <!-- Brand Header -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-slate-800 bg-slate-950/40">
                <a href="{{ Auth::user()->isKasir() ? route('purchases.create') : route('dashboard') }}" class="flex items-center gap-2.5 min-w-0">
                    @if($appLogo)
                        <img src="{{ $appLogo }}" alt="Logo" class="w-9 h-9 object-contain rounded-lg bg-white p-1 shadow-sm shrink-0 border border-slate-700">
                    @else
                        <div class="w-9 h-9 rounded-lg bg-emerald-600 flex items-center justify-center text-white shadow-sm font-bold text-lg shrink-0">
                            <i data-lucide="recycle" class="w-5 h-5"></i>
                        </div>
                    @endif
                    <div class="truncate">
                        <div class="text-sm font-bold tracking-tight text-white leading-none truncate uppercase">{{ $appName }}</div>
                        <div class="text-[10px] text-emerald-400 font-medium tracking-wider uppercase mt-1 truncate">{{ $appSubtitle }}</div>
                    </div>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white p-1">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Quick Kasir Action Button -->
            <div class="px-4 py-3.5 border-b border-slate-800/80">
                <a href="{{ route('purchases.create') }}" 
                   class="flex items-center justify-center gap-2 w-full py-2.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-sm shadow-sm shadow-emerald-900/40 transition-colors">
                    <i data-lucide="scale" class="w-4 h-4"></i>
                    <span>Timbang & Kasir</span>
                </a>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-3 py-3 overflow-y-auto space-y-1 text-sm font-medium">
                {{-- Dashboard: Dapat diakses oleh Owner, Admin, dan Kasir --}}
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-950/40 border-l-4 border-emerald-300' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-slate-400' }}"></i>
                    <span>Dashboard</span>
                </a>

                <div class="pt-2 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">1. Transaksi Kasir & Timbang</div>

                {{-- Timbang Kasir (Penerimaan Barang) --}}
                <a href="{{ route('purchases.create') }}" 
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('purchases.create') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-950/40 border-l-4 border-emerald-300' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                    <i data-lucide="scale" class="w-4 h-4 {{ request()->routeIs('purchases.create') ? 'text-white' : 'text-emerald-500' }}"></i>
                    <span>Penerimaan Barang</span>
                </a>

                {{-- Riwayat Pembelian --}}
                <a href="{{ route('purchases.index') }}" 
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('purchases.index') || request()->routeIs('purchases.show') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-950/40 border-l-4 border-emerald-300' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                    <i data-lucide="receipt" class="w-4 h-4 {{ request()->routeIs('purchases.index') || request()->routeIs('purchases.show') ? 'text-white' : 'text-slate-400' }}"></i>
                    <span>Riwayat Pembelian</span>
                </a>

                {{-- FITUR OWNER / SUPER ADMIN --}}
                @if(Auth::user()->isSuperAdmin())
                    <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">2. Penjualan & Ekspedisi</div>

                    <a href="{{ route('sales.index') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('sales.*') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-950/40 border-l-4 border-emerald-300' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="truck" class="w-4 h-4 {{ request()->routeIs('sales.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Penjualan Partai (Grosir)</span>
                    </a>

                    <a href="{{ route('delivery-orders.index') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('delivery-orders.*') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-950/40 border-l-4 border-emerald-300' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="clipboard-list" class="w-4 h-4 {{ request()->routeIs('delivery-orders.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Surat Jalan Pengiriman</span>
                    </a>

                    <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">3. Keuangan & Kasbon</div>

                    <a href="{{ route('debts.index') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('debts.*') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-950/40 border-l-4 border-emerald-300' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="handshake" class="w-4 h-4 {{ request()->routeIs('debts.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Hutang Piutang (Kasbon Modal)</span>
                    </a>

                    <a href="{{ route('expenses.index') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('expenses.*') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-950/40 border-l-4 border-emerald-300' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="wallet" class="w-4 h-4 {{ request()->routeIs('expenses.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Biaya Operasional</span>
                    </a>

                    <a href="{{ route('salary-slips.index') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('salary-slips.*') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-950/40 border-l-4 border-emerald-300' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="banknote" class="w-4 h-4 {{ request()->routeIs('salary-slips.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Slip Gaji Karyawan</span>
                    </a>

                    <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">4. Gudang & Inventori</div>

                    <a href="{{ route('stock.index') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('stock.index') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-950/40 border-l-4 border-emerald-300' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="boxes" class="w-4 h-4 {{ request()->routeIs('stock.index') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Stok & Nilai Barang</span>
                    </a>

                    <a href="{{ route('stock.movements') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('stock.movements') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-950/40 border-l-4 border-emerald-300' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="history" class="w-4 h-4 {{ request()->routeIs('stock.movements') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Mutasi Stok (Ledger)</span>
                    </a>

                    <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">5. Master Data Depo</div>

                    <a href="{{ route('scrap-items.index') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('scrap-items.*') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-950/40 border-l-4 border-emerald-300' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="tag" class="w-4 h-4 {{ request()->routeIs('scrap-items.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Jenis Barang & Harga</span>
                    </a>

                    <a href="{{ route('suppliers.index') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('suppliers.*') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-950/40 border-l-4 border-emerald-300' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="users" class="w-4 h-4 {{ request()->routeIs('suppliers.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Data Pemasok / Pengepul</span>
                    </a>

                    <a href="{{ route('drivers.index') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('drivers.*') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-950/40 border-l-4 border-emerald-300' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="truck" class="w-4 h-4 {{ request()->routeIs('drivers.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Data Sopir / Driver</span>
                    </a>

                    <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">6. Laporan & Rekapitulasi</div>

                    <a href="{{ route('reports.purchases') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('reports.purchases') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-950/40 border-l-4 border-emerald-300' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="file-spreadsheet" class="w-4 h-4 {{ request()->routeIs('reports.purchases') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Laporan Pembelian</span>
                    </a>

                    <a href="{{ route('reports.sales') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('reports.sales') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-950/40 border-l-4 border-emerald-300' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="trending-up" class="w-4 h-4 {{ request()->routeIs('reports.sales') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Laporan Penjualan</span>
                    </a>

                    <a href="{{ route('reports.profit-loss') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('reports.profit-loss') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-950/40 border-l-4 border-emerald-300' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="pie-chart" class="w-4 h-4 {{ request()->routeIs('reports.profit-loss') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Laba / Rugi Usaha</span>
                    </a>

                    <div class="pt-3 pb-1 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">7. Pengaturan Sistem</div>

                    <a href="{{ route('users.index') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('users.*') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-950/40 border-l-4 border-emerald-300' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="user-cog" class="w-4 h-4 {{ request()->routeIs('users.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Kelola Pengguna</span>
                    </a>

                    <a href="{{ route('settings.index') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('settings.*') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-950/40 border-l-4 border-emerald-300' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                        <i data-lucide="sliders" class="w-4 h-4 {{ request()->routeIs('settings.*') ? 'text-white' : 'text-slate-400' }}"></i>
                        <span>Pengaturan Usaha</span>
                    </a>
                @endif
            </nav>

            <!-- User Footer Profile -->
            <div class="p-3 border-t border-slate-800 bg-slate-950/60 flex items-center justify-between">
                <div class="flex items-center gap-2.5 overflow-hidden">
                    <div class="w-8 h-8 rounded-full bg-slate-800 text-emerald-400 font-bold flex items-center justify-center shrink-0 text-xs border border-slate-700">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>
                    <div class="truncate">
                        <div class="text-xs font-semibold text-white truncate">{{ Auth::user()->name }}</div>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-medium leading-none 
                                {{ Auth::user()->isSuperAdmin() ? 'bg-purple-900/60 text-purple-300 border border-purple-700/50' : (Auth::user()->isAdmin() ? 'bg-blue-900/60 text-blue-300 border border-blue-700/50' : 'bg-emerald-900/60 text-emerald-300 border border-emerald-700/50') }}">
                                {{ strtoupper(str_replace('_', ' ', Auth::user()->role)) }}
                            </span>
                            @if(Auth::user()->hasBankDetails())
                                <span class="text-[9px] text-slate-400 font-mono truncate" title="{{ Auth::user()->bankDisplay() }}">
                                    💳 {{ Auth::user()->bank_name }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" title="Keluar / Logout" class="p-1.5 text-slate-400 hover:text-red-400 hover:bg-slate-800 rounded transition-colors">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            
            <!-- Topbar -->
            <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-6 flex items-center justify-between sticky top-0 z-30 shrink-0 no-print">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 text-slate-600 hover:bg-slate-100 rounded-lg">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                    <div>
                        <h1 class="text-base sm:text-lg font-bold text-slate-900 leading-tight">
                            {{ $header ?? $appName }}
                        </h1>
                        <p class="text-xs text-slate-500 hidden sm:block">
                            {{ $companyName }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @if(config('services.whatsapp_gateway.enabled', false))
                        <!-- WhatsApp Gateway Quick Trigger (Like Dental Care) -->
                        <button type="button"
                                @click="$store.waGateway.isOpen = true"
                                class="flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition-all shadow-xs cursor-pointer"
                                :class="{
                                    'border-emerald-300 bg-emerald-50 text-emerald-800 hover:bg-emerald-100': $store.waGateway.connected,
                                    'border-amber-400 bg-amber-50 text-amber-900 hover:bg-amber-100 ring-2 ring-amber-300/60 animate-pulse': !$store.waGateway.connected && $store.waGateway.status === 'QR_READY',
                                    'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100': !$store.waGateway.connected && $store.waGateway.status !== 'QR_READY'
                                }"
                                :title="$store.waGateway.connected ? 'WhatsApp Gateway Online (' + ($store.waGateway.phone || 'Terhubung') + ')' : ($store.waGateway.status === 'QR_READY' ? 'Scan QR WhatsApp Gateway' : 'WhatsApp Gateway Offline')">
                            <span x-bind:class="$store.waGateway.connected ? 'text-emerald-600' : (!$store.waGateway.connected && $store.waGateway.status === 'QR_READY' ? 'text-amber-600' : 'text-slate-500')">
                                <x-whatsapp-icon class="w-3.5 h-3.5 shrink-0" />
                            </span>
                            <span class="hidden sm:inline" x-text="$store.waGateway.connected ? 'WA Terhubung' : ($store.waGateway.status === 'QR_READY' ? 'Scan QR WA' : 'WA Gateway')"></span>
                            <span class="h-2 w-2 rounded-full"
                                  :class="{
                                      'bg-emerald-500': $store.waGateway.connected,
                                      'bg-amber-500 animate-ping': !$store.waGateway.connected && $store.waGateway.status === 'QR_READY',
                                      'bg-rose-400': !$store.waGateway.connected && $store.waGateway.status !== 'QR_READY'
                                  }"></span>
                        </button>
                    @endif

                    <div class="hidden md:flex items-center gap-1.5 px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-medium">
                        <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</span>
                    </div>

                    @if(!request()->routeIs('purchases.create'))
                        <a href="{{ route('purchases.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm transition-colors">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                            <span>Kasir Baru</span>
                        </a>
                    @endif
                </div>
            </header>

            <!-- Notification Messages -->
            <div class="px-4 sm:px-6 pt-4 no-print">
                @if(session('success'))
                    <div class="mb-4 flex items-center gap-3 p-3.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm shadow-sm" role="alert">
                        <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                        <div class="flex-1 font-medium">{{ session('success') }}</div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 flex items-center gap-3 p-3.5 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm shadow-sm" role="alert">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-red-600 shrink-0"></i>
                        <div class="flex-1 font-medium">{{ session('error') }}</div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 p-3.5 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm shadow-sm" role="alert">
                        <div class="font-semibold mb-1 flex items-center gap-2">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-red-600"></i>
                            <span>Terdapat beberapa kesalahan input:</span>
                        </div>
                        <ul class="list-disc list-inside text-xs space-y-0.5 ml-1">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <!-- Page Body Content -->
            <main class="flex-1 px-4 sm:px-6 py-4">
                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </div>

    <!-- WhatsApp Gateway & Receipt Modals -->
    @if(config('services.whatsapp_gateway.enabled', false))
        @include('components.whatsapp-gateway-modal')
    @endif
    @include('components.whatsapp-receipt-modal')
    @include('components.pdf-preview-modal')

    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.createIcons && window.lucideIcons) {
                window.createIcons({ icons: window.lucideIcons });
            }

            // Register PWA Service Worker
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            }
        });
    </script>
</body>
</html>

