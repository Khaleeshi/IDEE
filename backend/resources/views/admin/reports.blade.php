<x-layout title="Signalements">
    <h1 class="mb-3 text-2xl font-bold">Signalements</h1>
    <x-admin-nav />

    @foreach (['Recours à examiner' => $appeals, 'À traiter' => $open] as $heading => $reports)
        <h2 class="mb-2 mt-4 text-sm font-semibold uppercase tracking-wide text-stone-500">{{ $heading }} ({{ $reports->count() }})</h2>
        @forelse ($reports as $report)
            <a href="{{ route('admin.reports.show', $report) }}" class="mb-2 block rounded-2xl border bg-white p-3 text-sm {{ $report->priority >= 3 ? 'border-red-300' : 'border-stone-200' }}">
                <span class="font-semibold {{ $report->priority >= 3 ? 'text-red-700' : '' }}">{{ $report->reason->label() }}</span>
                <span class="block text-stone-600">{{ class_basename($report->reportable_type) === 'User' ? 'Profil' : (class_basename($report->reportable_type) === 'Project' ? 'Projet' : 'Message') }} de {{ $report->targetUser?->name ?? 'membre supprimé' }} · {{ $report->created_at->diffForHumans() }}</span>
            </a>
        @empty
            <p class="text-sm text-stone-500">Rien en attente.</p>
        @endforelse
    @endforeach
</x-layout>
