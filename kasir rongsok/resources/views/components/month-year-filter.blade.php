@props([
    'selectedMonth' => null,
    'selectedYear' => null,
    'startDateName' => 'start_date',
    'endDateName' => 'end_date',
    'inline' => false,
])

@php
    $months = \App\Services\FormatHelper::getMonths();
    $years = \App\Services\FormatHelper::getYears();

    // Default to current month and year when not explicitly provided
    if ($selectedMonth === null) {
        $selectedMonth = request()->has('month') ? request('month') : date('n');
    }
    if ($selectedYear === null) {
        $selectedYear = request()->has('year') ? request('year') : date('Y');
    }
@endphp

@if($inline)
    <div class="flex items-center gap-1.5 text-xs">
        <select name="month" 
                onchange="syncDateInputs(this, '{{ $startDateName }}', '{{ $endDateName }}')"
                class="py-1.5 px-2 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 bg-white">
            <option value="">-- Bulan --</option>
            @foreach($months as $num => $name)
                <option value="{{ $num }}" {{ (string)$selectedMonth === (string)$num ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </select>

        <select name="year" 
                onchange="syncDateInputs(this, '{{ $startDateName }}', '{{ $endDateName }}')"
                class="py-1.5 px-2 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 bg-white">
            <option value="">-- Tahun --</option>
            @foreach($years as $y)
                <option value="{{ $y }}" {{ (string)$selectedYear === (string)$y ? 'selected' : '' }}>
                    {{ $y }}
                </option>
            @endforeach
        </select>
    </div>
@else
    <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Bulan</label>
        <select name="month" 
                onchange="syncDateInputs(this, '{{ $startDateName }}', '{{ $endDateName }}')"
                class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 bg-white">
            <option value="">-- Semua Bulan --</option>
            @foreach($months as $num => $name)
                <option value="{{ $num }}" {{ (string)$selectedMonth === (string)$num ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun</label>
        <select name="year" 
                onchange="syncDateInputs(this, '{{ $startDateName }}', '{{ $endDateName }}')"
                class="w-full py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-emerald-500 bg-white">
            <option value="">-- Semua Tahun --</option>
            @foreach($years as $y)
                <option value="{{ $y }}" {{ (string)$selectedYear === (string)$y ? 'selected' : '' }}>
                    {{ $y }}
                </option>
            @endforeach
        </select>
    </div>
@endif
