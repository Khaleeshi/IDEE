@props(['project'])

<article class="mb-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
    <a href="{{ route('projects.show', $project) }}" class="block">
        <div class="flex flex-wrap items-center gap-2 text-xs text-stone-500">
            @if ($project->sector)
                <span>{{ $project->sector->name }}</span><span aria-hidden="true">·</span>
            @endif
            @if ($project->region)
                <span>{{ $project->region }}</span><span aria-hidden="true">·</span>
            @endif
            <span>{{ $project->stage->label() }}</span>
        </div>
        <h2 class="mt-1 text-lg font-semibold leading-snug text-stone-900">{{ $project->title }}</h2>
        <p class="mt-1 line-clamp-3 text-sm text-stone-600">{{ $project->summary }}</p>
    </a>

    @if ($project->openNeeds->isNotEmpty())
        <ul class="mt-3 flex flex-wrap gap-2" aria-label="Ce que le projet cherche">
            @foreach ($project->openNeeds->take(4) as $need)
                <li class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-900">Cherche : {{ mb_strtolower($need->type->label()) }}</li>
            @endforeach
        </ul>
    @endif

    @if ($project->relationLoaded('owner'))
        <p class="mt-3 text-xs text-stone-500">Porté par <a href="{{ route('members.show', $project->owner) }}" class="font-medium text-stone-700 underline">{{ $project->owner->name }}</a></p>
    @endif
</article>
