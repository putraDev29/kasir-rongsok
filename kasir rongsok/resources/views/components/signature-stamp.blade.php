@props([
    'isPdf' => false,
    'height' => 48,
    'stampSize' => null,
    'stampLeft' => '0px',
    'style' => '',
])

@php
    $h = (int) $height;
    $sSize = (int) ($stampSize ?: round($h * 1.15));
    $sLeft = $stampLeft ?: '0px';

    $hasSig = \App\Models\BusinessSetting::hasOwnerSignature();
    $hasStamp = \App\Models\BusinessSetting::hasCompanyStamp();

    $sigSrc = $isPdf ? \App\Models\BusinessSetting::ownerSignatureBase64() : \App\Models\BusinessSetting::ownerSignatureUrl();
    $stampSrc = $isPdf ? \App\Models\BusinessSetting::companyStampBase64() : \App\Models\BusinessSetting::companyStampUrl();
@endphp

@if($hasSig && $hasStamp && $sigSrc && $stampSrc)
    {{-- Keduanya tersedia: Cap Stempel menimpa bagian kiri tanda tangan secara proporsional resmi --}}
    <div style="position: relative; display: inline-block; min-width: 150px; max-width: 220px; height: {{ $h }}px; line-height: {{ $h }}px; vertical-align: middle; margin: 0 auto; text-align: center; {{ $style }}">
        <img src="{{ $stampSrc }}" alt="Stempel Depo" style="position: absolute; left: {{ $sLeft }}; top: {{ round(($h - $sSize) / 2) }}px; height: {{ $sSize }}px; max-height: {{ $sSize }}px; width: auto; max-width: {{ $sSize }}px; object-fit: contain; opacity: 0.88; z-index: 1;">
        <img src="{{ $sigSrc }}" alt="TTD Owner" style="position: relative; height: {{ $h }}px; max-height: {{ $h }}px; width: auto; max-width: 155px; object-fit: contain; margin: 0 auto; display: inline-block; z-index: 2;">
    </div>
@elseif($hasSig && $sigSrc)
    {{-- Hanya tanda tangan owner --}}
    <div style="height: {{ $h }}px; line-height: {{ $h }}px; text-align: center; vertical-align: middle; margin: 0 auto; {{ $style }}">
        <img src="{{ $sigSrc }}" alt="TTD Owner" style="height: {{ $h }}px; max-height: {{ $h }}px; width: auto; max-width: 160px; object-fit: contain; margin: 0 auto; display: inline-block;">
    </div>
@elseif($hasStamp && $stampSrc)
    {{-- Hanya cap stempel depo --}}
    <div style="height: {{ $h }}px; line-height: {{ $h }}px; text-align: center; vertical-align: middle; margin: 0 auto; {{ $style }}">
        <img src="{{ $stampSrc }}" alt="Stempel Depo" style="height: {{ $sSize }}px; max-height: {{ $sSize }}px; width: auto; max-width: {{ $sSize }}px; object-fit: contain; margin: 0 auto; display: inline-block; opacity: 0.9;">
    </div>
@else
    {{-- Belum ada unggahan: ruang kosong untuk tanda tangan basah fisik --}}
    <div style="height: {{ $h }}px; min-height: {{ $h }}px; {{ $style }}"></div>
@endif
