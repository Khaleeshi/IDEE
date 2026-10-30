<x-layout title="Administration">
    <h1 class="mb-3 text-2xl font-bold">Administration</h1>
    <x-admin-nav />

    <div class="mb-4 grid grid-cols-3 gap-2 text-center">
        <a href="{{ route('admin.reports') }}" class="rounded-2xl border border-stone-200 bg-white p-3"><span class="block text-2xl font-bold {{ $openReports ? 'text-amber-700' : '' }}">{{ $openReports }}</span><span class="text-xs text-stone-500">signalements à traiter</span></a>
        <a href="{{ route('admin.verifications') }}" class="rounded-2xl border border-stone-200 bg-white p-3"><span class="block text-2xl font-bold {{ $pendingVerifications ? 'text-amber-700' : '' }}">{{ $pendingVerifications }}</span><span class="text-xs text-stone-500">vérifications en attente</span></a>
        <a href="{{ route('admin.guides') }}" class="rounded-2xl border border-stone-200 bg-white p-3"><span class="block text-2xl font-bold {{ $staleSteps ? 'text-amber-700' : '' }}">{{ $staleSteps }}</span><span class="text-xs text-stone-500">fiches à revoir</span></a>
    </div>

    <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-stone-500">Indicateurs</h2>
    <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-stone-50 text-left text-xs text-stone-500">
                <tr><th class="p-3 font-medium">Indicateur</th><th class="p-3 text-right font-medium">Valeur</th><th class="p-3 text-right font-medium">Cible mois 12</th></tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($indicators as $indicator)
                    <tr>
                        <td class="p-3"><span class="font-medium">{{ $indicator['label'] }}</span><span class="block text-xs text-stone-500">{{ $indicator['definition'] }}</span></td>
                        <td class="p-3 text-right font-semibold">{{ $indicator['value'] }}</td>
                        <td class="p-3 text-right text-stone-500">{{ $indicator['target'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layout>
