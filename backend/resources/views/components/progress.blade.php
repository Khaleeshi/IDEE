@props(['done', 'total'])

@php($percent = $total > 0 ? (int) round($done * 100 / $total) : 0)

<div {{ $attributes->merge(['class' => 'h-2 overflow-hidden rounded-full bg-stone-100']) }}
     role="progressbar" aria-valuemin="0" aria-valuemax="{{ $total }}" aria-valuenow="{{ $done }}"
     aria-label="{{ $done }} étape{{ $done > 1 ? 's' : '' }} terminée{{ $done > 1 ? 's' : '' }} sur {{ $total }}">
    <div class="h-2 rounded-full bg-brand-600" style="width: {{ $percent }}%"></div>
</div>
