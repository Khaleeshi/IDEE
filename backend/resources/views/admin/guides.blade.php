<x-layout title="Fiches pratiques">
    <h1 class="mb-3 text-2xl font-bold">Fiches pratiques</h1>
    <x-admin-nav />
    <p class="mb-3 text-sm text-stone-600">Une fiche dont la source n'a pas été revue depuis {{ config('idee.guide_review_after_months') }} mois est signalée (RG-10). Une fiche modifiée ici n'est plus écrasée à la mise en ligne.</p>

    <ul class="divide-y divide-stone-100 rounded-2xl border border-stone-200 bg-white text-sm">
        @foreach ($steps as $step)
            <li>
                <a href="{{ route('admin.guides.edit', $step) }}" class="flex items-center justify-between gap-2 p-3">
                    <span><span class="block font-medium">{{ $step->title }}</span><span class="text-xs text-stone-500">{{ $step->journey->title }} · {{ $step->checked_on ? 'vérifiée le '.$step->checked_on->format('d/m/Y') : 'sans source' }}</span></span>
                    @if ($step->isStale())<span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900">À revoir</span>@endif
                </a>
            </li>
        @endforeach
    </ul>
</x-layout>
