<x-layout title="Candidatures · {{ $programme->title }}">
    <a href="{{ route('organisations.manage', $organisation) }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← {{ $organisation->name }}</a>
    <h1 class="text-2xl font-bold">Candidatures</h1>
    <p class="mt-1 text-sm text-stone-600">{{ $programme->title }} · {{ $applications->count() }} candidature{{ $applications->count() > 1 ? 's' : '' }}</p>

    <div class="mt-4">
        @forelse ($applications as $application)
            <a href="{{ route('applications.show', $application) }}" class="mb-2 flex items-center justify-between gap-2 rounded-xl border border-stone-200 bg-white p-3 text-sm">
                <span><span class="font-semibold">{{ $application->project->title }}</span>
                    <span class="block text-xs text-stone-500">{{ $application->user->name }} · {{ $application->created_at->translatedFormat('d F') }} · score {{ $application->project->readiness_score }}/100</span></span>
                <x-status :status="$application->status" />
            </a>
        @empty
            <x-card class="text-center text-sm text-stone-600">Aucune candidature pour le moment.</x-card>
        @endforelse
    </div>
</x-layout>
