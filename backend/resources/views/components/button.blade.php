@props(['variant' => 'primary', 'href' => null])

@php
    $classes = 'inline-flex min-h-12 items-center justify-center rounded-xl px-5 py-3 text-base font-semibold transition '.match ($variant) {
        'secondary' => 'border border-stone-300 bg-white text-stone-800 hover:bg-stone-100',
        'danger' => 'border border-red-200 bg-white text-red-700 hover:bg-red-50',
        'ghost' => 'text-brand-700 hover:bg-brand-50',
        default => 'bg-brand-700 text-white hover:bg-brand-800',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>{{ $slot }}</button>
@endif
