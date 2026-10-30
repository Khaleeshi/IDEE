@props(['status'])

@php
    $tone = match ($status->value) {
        'ouvert', 'actif', 'interesse', 'acceptee', 'comble' => 'bg-brand-50 text-brand-800 border-brand-100',
        'envoyee', 'lue', 'proposee', 'pas_maintenant' => 'bg-amber-50 text-amber-800 border-amber-200',
        'refusee', 'expiree', 'declinee', 'abandonne' => 'bg-stone-100 text-stone-600 border-stone-200',
        default => 'bg-stone-100 text-stone-700 border-stone-200',
    };
@endphp

<span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $tone }}">{{ $status->label() }}</span>
