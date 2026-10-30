@props(['activity'])

@php($project = $activity->project)

<article class="mb-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
    <p class="flex flex-wrap items-center gap-x-2 text-xs text-stone-500">
        <span class="font-semibold {{ $activity->type->value === 'reussite' ? 'text-brand-700' : 'text-stone-700' }}">{{ $activity->type->label() }}</span>
        <span aria-hidden="true">·</span>
        <time datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->diffForHumans() }}</time>
        @if ($project->region)<span aria-hidden="true">·</span><span>{{ $project->region }}</span>@endif
    </p>

    <a href="{{ route('projects.show', $project) }}" class="mt-1 block">
        <h2 class="text-base font-semibold leading-snug text-stone-900">{{ $project->title }}</h2>

        @switch ($activity->type->value)
            @case('projet_publie')
                <p class="mt-1 line-clamp-3 text-sm text-stone-600">{{ $project->summary }}</p>
                @break
            @case('besoin_publie')
                <p class="mt-2 text-sm"><span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-900">Cherche : {{ mb_strtolower($activity->need?->type->label() ?? '') }}</span> {{ $activity->need?->title }}</p>
                @break
            @case('mise_a_jour')
                <p class="mt-1 line-clamp-4 whitespace-pre-line text-sm text-stone-700">{{ $activity->projectUpdate?->body }}</p>
                @break
            @case('reussite')
                @if ($activity->collaboration)
                    <p class="mt-1 text-sm text-stone-700">{{ $activity->collaboration->summary }}</p>
                    <p class="mt-1 text-xs font-medium text-brand-700">Confirmée par {{ $activity->collaboration->partner?->name }}</p>
                @else
                    <p class="mt-1 text-sm text-stone-700">Besoin comblé : {{ $activity->need?->title }}@if ($activity->need?->outcome) — {{ $activity->need->outcome }}@endif</p>
                @endif
                @break
        @endswitch
    </a>

    <p class="mt-2 text-xs text-stone-500">par <a href="{{ route('members.show', $activity->actor) }}" class="font-medium text-stone-700 underline">{{ $activity->actor->name }}</a></p>
</article>
