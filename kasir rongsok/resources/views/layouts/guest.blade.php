<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Masuk' }} - {{ config('app.name', 'Kasir Rongsok') }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center font-sans antialiased text-slate-800 p-4">
    <div class="w-full max-w-md">
        {{ $slot ?? '' }}
        @yield('content')
    </div>
    
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

