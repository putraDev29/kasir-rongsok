@extends('layouts.guest', ['title' => 'Masuk - ' . \App\Models\BusinessSetting::get('app_name', 'Kasir Rongsok')])

@section('content')
@php
    $loginLogo = \App\Models\BusinessSetting::logoUrl();
    $loginAppName = \App\Models\BusinessSetting::get('app_name', 'KASIR RONGSOK');
    $loginAppSubtitle = \App\Models\BusinessSetting::get('app_subtitle', 'Sistem Pembelian & Manajemen Persediaan Rongsok');
@endphp
<div class="bg-white rounded-2xl shadow-xl shadow-slate-200/80 border border-slate-200/80 overflow-hidden">
    <!-- Header -->
    <div class="bg-slate-900 p-6 text-center text-white">
        @if($loginLogo)
            <div class="w-16 h-16 bg-white rounded-2xl mx-auto flex items-center justify-center mb-3 shadow-md p-2 border border-slate-700">
                <img src="{{ $loginLogo }}" alt="Logo" class="max-h-full max-w-full object-contain">
            </div>
        @else
            <div class="w-12 h-12 bg-emerald-600 rounded-xl mx-auto flex items-center justify-center mb-3 shadow-md">
                <i data-lucide="recycle" class="w-7 h-7 text-white"></i>
            </div>
        @endif
        <h2 class="text-xl font-bold tracking-tight uppercase">{{ $loginAppName }}</h2>
        <p class="text-xs text-slate-400 mt-1">{{ $loginAppSubtitle }}</p>
    </div>

    <!-- Login Form -->
    <form action="{{ route('login.post') }}" method="POST" class="p-6 sm:p-8 space-y-5">
        @csrf

        @if($errors->any())
            <div class="p-3 rounded-lg bg-red-50 border border-red-200 text-xs text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Alamat Email</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                </span>
                <input type="email" id="email" name="email" value="{{ old('email', 'owner@kasirrongsok.com') }}" required autofocus
                       class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
            </div>
        </div>

        <div x-data="{ showPassword: false }">
            <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Kata Sandi</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                </span>
                <input :type="showPassword ? 'text' : 'password'" id="password" name="password" value="password123" required
                       class="w-full pl-9 pr-10 py-2 text-sm rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                <button type="button" 
                        @click="showPassword = !showPassword" 
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 focus:outline-none transition-colors cursor-pointer"
                        tabindex="-1"
                        :title="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                    <span x-show="!showPassword" class="flex items-center">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </span>
                    <span x-show="showPassword" x-cloak class="flex items-center">
                        <i data-lucide="eye-off" class="w-4 h-4"></i>
                    </span>
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between text-xs text-slate-600">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <span>Ingat saya di perangkat ini</span>
            </label>
        </div>

        <button type="submit" 
                class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm rounded-lg shadow-md transition-colors flex items-center justify-center gap-2">
            <i data-lucide="log-in" class="w-4 h-4"></i>
            <span>Masuk ke Aplikasi</span>
        </button>
    </form>
</div>
@endsection

