<x-layout :title="'Groupe '.$name" :description="'Entrepreneurs et projets : '.$name.' sur IDEE.'">
    <a href="{{ route('groups.index') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Groupes</a>
    <p class="text-sm font-medium text-brand-700">{{ $type->label() }}</p>
    <h1 class="text-2xl font-bold">{{ $name }}</h1>

    @if ($sector)
        @auth
            <form method="POST" action="{{ route('follows.sector', $sector) }}" class="mt-3">
                @csrf
                <x-button :variant="$following ? 'secondary' : 'primary'" class="w-full">{{ $following ? '✓ Secteur suivi' : 'Suivre le secteur' }}</x-button>
            </form>
        @endauth
    @endif

    <section class="mt-5" aria-labelledby="titre-projets-groupe">
        <h2 id="titre-projets-groupe" class="mb-2 text-lg font-bold">Projets <span class="font-normal text-stone-400">({{ $projectsCount }})</span></h2>
        @forelse ($projects as $project)
            <x-project-card :project="$project" />
        @empty
            <p class="text-sm text-stone-500">Aucun projet actif pour le moment.</p>
        @endforelse
        @if ($projectsCount > $projects->count())
            <a href="{{ route('home', ['vue' => 'projets', $type->value === 'secteur' ? 'sector' : 'region' => $type->value === 'secteur' ? $key : $name]) }}" class="text-sm font-medium text-brand-700 underline">Voir les {{ $projectsCount }} projets</a>
        @endif
    </section>

    <section id="discussion" class="mt-6" aria-labelledby="titre-discussion">
        <h2 id="titre-discussion" class="mb-2 text-lg font-bold">Discussion</h2>

        @auth
            <form method="POST" action="{{ route('groups.posts.store', [$type, $key]) }}" class="mb-4">
                @csrf
                <x-field name="body" type="textarea" rows="2" label="Votre message au groupe" maxlength="2000"
                         placeholder="Ex. : qui connaît un fournisseur d'emballages alimentaires à Thiès ?" />
                <x-button variant="secondary" class="w-full">Publier</x-button>
            </form>
        @else
            <p class="mb-3 text-sm"><a href="{{ route('login') }}" class="font-medium text-brand-700 underline">Connectez-vous</a> pour écrire au groupe.</p>
        @endauth

        @forelse ($posts as $post)
            <article class="mb-2 rounded-xl border border-stone-200 bg-white p-3 text-sm">
                <p class="flex items-center gap-2 text-xs text-stone-500">
                    <a href="{{ route('members.show', $post->user) }}" class="font-medium text-stone-800 underline">{{ $post->user->name }}</a>
                    <time datetime="{{ $post->created_at->toIso8601String() }}">{{ $post->created_at->diffForHumans() }}</time>
                </p>
                <p class="mt-1 whitespace-pre-line text-stone-800">{{ $post->body }}</p>
                @auth
                    @if ($post->user_id === auth()->id())
                        <form method="POST" action="{{ route('groups.posts.destroy', $post) }}" data-confirm="Supprimer ce message ?" class="mt-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="py-1 text-xs text-stone-500 underline">Supprimer</button>
                        </form>
                    @else
                        <x-report-form type="publication" :id="$post->id" label="Signaler" class="mt-1" />
                    @endif
                @endauth
            </article>
        @empty
            <p class="text-sm text-stone-500">Pas encore de message. Lancez la discussion !</p>
        @endforelse
        {{ $posts->links() }}
    </section>
</x-layout>
