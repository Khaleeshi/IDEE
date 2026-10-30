<x-layout :title="$organisation->name" :description="\Illuminate\Support\Str::limit($organisation->description, 180)">
    <a href="{{ route('organisations.index') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Organisations</a>

    <div class="flex flex-wrap items-center gap-2">
        <h1 class="text-2xl font-bold leading-tight">{{ $organisation->name }}</h1>
        <x-organisation-badge :organisation="$organisation" />
    </div>
    <p class="mt-1 text-sm text-stone-500">{{ $organisation->kind->label() }}@if ($organisation->region) · {{ $organisation->region }}@endif</p>
    <p class="mt-3 whitespace-pre-line text-stone-700">{{ $organisation->description }}</p>
    @if ($organisation->website)
        <a href="{{ $organisation->website }}" target="_blank" rel="noopener" class="mt-2 inline-block text-sm font-medium text-brand-700 underline">Site de l'organisation</a>
    @endif
    @if ($organisation->isVerified())
        <p class="mt-3 text-xs text-stone-500">RCCM {{ $organisation->rccm }} · NINEA {{ $organisation->ninea }} · vérifiés par IDEE le {{ $organisation->verified_at->translatedFormat('d F Y') }}</p>
    @endif

    @if ($canManage)
        <x-button :href="route('organisations.manage', $organisation)" variant="secondary" class="mt-4 w-full">Gérer la page</x-button>
    @endif

    @if ($programmes->isNotEmpty())
        <h2 class="mb-2 mt-6 text-lg font-bold">Programmes ouverts</h2>
        @foreach ($programmes as $programme)
            <a href="{{ route('opportunities.show', $programme) }}" class="mb-2 block rounded-xl border border-stone-200 bg-white p-3 text-sm">
                <span class="text-xs font-medium text-brand-700">{{ $programme->kind->label() }}</span>
                <span class="block font-semibold">{{ $programme->title }}</span>
                <span class="block text-xs text-stone-500">{{ $programme->deadline ? 'Date limite : '.$programme->deadline->translatedFormat('d F Y') : $programme->deadline_note }}</span>
            </a>
        @endforeach
    @endif

    <h2 class="mb-2 mt-6 text-lg font-bold">Membres</h2>
    <ul class="space-y-2 text-sm">
        @foreach ($organisation->members as $member)
            <li class="flex items-center gap-2 rounded-xl border border-stone-200 bg-white p-3">
                <x-avatar :user="$member->user" size="h-8 w-8 text-xs" />
                <a href="{{ route('members.show', $member->user) }}" class="font-medium underline">{{ $member->user->name }}</a>
            </li>
        @endforeach
    </ul>

    @auth
        <x-report-form type="organisation" :id="$organisation->id" label="Signaler cette page" class="mt-6 text-center" />
    @endauth
</x-layout>
