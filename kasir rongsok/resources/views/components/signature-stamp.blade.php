@props([
    'isPdf' => false,
    'height' => 48,
    'stampSize' => null,
    'style' => '',
])

@php
    $h = (int) $height ?: 48;
    $sSize = (int) ($stampSize ?: round($h * 1.18));
    $boxWidth = round($sSize * 2.3);
    $boxHeight = max($h, $sSize);

    // Stempel di sisi kiri, TTD di sisi kanan menembus stempel (overlap ~40-45% diameter)
    $stampLeft = 12;
    $sigLeft = (int) round($stampLeft + ($sSize * 0.58));
    $sigTop = (int) round(($boxHeight - $h) / 2);
    $stampTop = (int) round(($boxHeight - $sSize) / 2);

    $hasSig = \App\Models\BusinessSetting::hasOwnerSignature();
    $hasStamp = \App\Models\BusinessSetting::hasCompanyStamp();

    $sigSrc = $isPdf ? \App\Models\BusinessSetting::ownerSignatureBase64() : \App\Models\BusinessSetting::ownerSignatureUrl();
    $stampSrc = $isPdf ? \App\Models\BusinessSetting::companyStampBase64() : \App\Models\BusinessSetting::companyStampUrl();
@endphp

@if($hasSig && $hasStamp && $sigSrc && $stampSrc)
    {{-- Keduanya tersedia: Cap Stempel DI ATAS tanda tangan & TERPUSAT (CENTER) PERSIS DI ATAS NAMA --}}
    <div style="position: relative; width: {{ $boxWidth }}px; height: {{ $boxHeight }}px; margin: 0 auto; text-align: center; display: inline-block; vertical-align: middle; {{ $style }}">
        <!-- 1. Tanda Tangan Owner (Layer bawah, posisi kanan menembus stempel) -->
        <img src="{{ $sigSrc }}" alt="TTD Owner" style="position: absolute; left: {{ $sigLeft }}px; top: {{ $sigTop }}px; height: {{ $h }}px; max-height: {{ $h }}px; width: auto; max-width: {{ round($boxWidth - $sigLeft) }}px; object-fit: contain; z-index: 1;">

        <!-- 2. Cap Stempel Depo / Usaha (Layer ATAS menimpa fisik tanda tangan di sisi kiri seperti dokumen resmi) -->
        <img src="{{ $stampSrc }}" alt="Stempel Depo" style="position: absolute; left: {{ $stampLeft }}px; top: {{ $stampTop }}px; height: {{ $sSize }}px; max-height: {{ $sSize }}px; width: {{ $sSize }}px; max-width: {{ $sSize }}px; object-fit: contain; opacity: 0.92; z-index: 2; pointer-events: none; mix-blend-mode: multiply;">
    </div>
@elseif($hasSig && $sigSrc)
    {{-- Hanya tanda tangan owner: Center tepat di atas nama --}}
    <div style="height: {{ $h }}px; line-height: {{ $h }}px; text-align: center; vertical-align: middle; margin: 0 auto; {{ $style }}">
        <img src="{{ $sigSrc }}" alt="TTD Owner" style="height: {{ $h }}px; max-height: {{ $h }}px; width: auto; max-width: 160px; object-fit: contain; margin: 0 auto; display: inline-block; vertical-align: middle;">
    </div>
@elseif($hasStamp && $stampSrc)
    {{-- Hanya cap stempel depo: Center tepat di atas nama --}}
    <div style="height: {{ $sSize }}px; line-height: {{ $sSize }}px; text-align: center; vertical-align: middle; margin: 0 auto; {{ $style }}">
        <img src="{{ $stampSrc }}" alt="Stempel Depo" style="height: {{ $sSize }}px; max-height: {{ $sSize }}px; width: {{ $sSize }}px; max-width: {{ $sSize }}px; object-fit: contain; margin: 0 auto; display: inline-block; vertical-align: middle; opacity: 0.92;">
    </div>
@else
    {{-- Belum ada unggahan: ruang kosong untuk tanda tangan basah fisik --}}
    <div style="height: {{ $h }}px; min-height: {{ $h }}px; {{ $style }}"></div>
@endif
