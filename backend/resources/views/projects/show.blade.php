<x-layout :title="$project->title" :description="\Illuminate\Support\Str::limit($project->summary, 180)">
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="mb-3 inline-block py-1 text-sm text-stone-500">← Retour</a>

    <div class="flex flex-wrap items-center gap-2 text-xs text-stone-500">
        @if ($project->sector)<span>{{ $project->sector->name }}</span><span aria-hidden="true">·</span>@endif
        @if ($project->region)<span>{{ $project->region }}</span><span aria-hidden="true">·</span>@endif
        <span>{{ $project->stage->label() }}</span>
        @if ($project->status->value !== 'actif')<x-status :status="$project->status" />@endif
    </div>
    <h1 class="mt-1 text-2xl font-bold leading-tight">{{ $project->title }}</h1>
    <p class="mt-3 whitespace-pre-line text-stone-700">{{ $project->summary }}</p>

    <div class="mt-3 flex flex-wrap gap-2">
        <a href="https://wa.me/?text={{ rawurlencode($project->title.' · découvrez ce projet sur IDEE : '.route('projects.show', $project)) }}"
           target="_blank" rel="noopener" class="inline-flex min-h-11 items-center rounded-xl border border-stone-300 bg-white px-4 text-sm font-semibold text-stone-800">
            Partager sur WhatsApp
        </a>
        @auth
            @unless ($isOwner)
                <form method="POST" action="{{ route('follows.project', $project) }}">
                    @csrf
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl border px-4 text-sm font-semibold {{ $following ? 'border-brand-100 bg-brand-50 text-brand-800' : 'border-stone-300 bg-white text-stone-800' }}">
                        {{ $following ? '✓ Suivi' : 'Suivre ce projet' }}
                    </button>
                </form>
            @endunless
        @endauth
    </div>

    @if ($isOwner)
        <div class="mt-4 flex gap-2">
            <x-button variant="secondary" :href="route('projects.edit', $project)">Modifier</x-button>
        </div>

        @if ($project->details)
            <x-card class="mt-4">
                <h2 class="text-sm font-semibold text-stone-500">Détails privés · visibles par vous seul</h2>
                <p class="mt-2 whitespace-pre-line text-stone-700">{{ $project->details }}</p>
            </x-card>
        @endif

        <x-card class="mt-4">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold">Score de préparation</h2>
                <span class="text-2xl font-bold text-brand-700">{{ $readiness['score'] }}<span class="text-sm text-stone-500">/100</span></span>
            </div>
            <div class="mt-2 h-2 rounded-full bg-stone-100" role="progressbar" aria-valuenow="{{ $readiness['score'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Score de préparation">
                <div class="h-2 rounded-full bg-brand-600" style="width: {{ $readiness['score'] }}%"></div>
            </div>
            @if ($readiness['missing'])
                <p class="mt-3 text-sm font-medium">Pour progresser :</p>
                <ul class="mt-1 space-y-1 text-sm text-stone-700">
                    @foreach ($readiness['missing'] as $advice)
                        <li>· {{ $advice['advice'] }} <span class="text-stone-400">(+{{ $advice['points'] }})</span></li>
                    @endforeach
                </ul>
            @endif
            <p class="mt-3 text-xs text-stone-500">Ce score mesure la préparation du dossier, jamais la rentabilité du projet.</p>
        </x-card>
    @endif

    <x-card class="mt-4">
        <p class="text-sm text-stone-500">Porté par</p>
        <div class="mt-1 flex items-center gap-2">
            <x-avatar :user="$project->owner" size="h-10 w-10 text-sm" />
            <a href="{{ route('members.show', $project->owner) }}" class="text-lg font-semibold text-brand-800 underline">{{ $project->owner->name }}</a>
        </div>
        @if ($project->owner->region)<span class="text-sm text-stone-500"> · {{ $project->owner->region }}</span>@endif
        @include('members.partials.reactivity', ['reactivity' => $reactivity])

        @auth
            @unless ($isOwner)
                <x-button :href="route('contact-requests.create', $project)" class="mt-3 w-full">Contacter le porteur</x-button>
            @endunless
        @else
            <x-button :href="route('login')" variant="secondary" class="mt-3 w-full">Se connecter pour contacter</x-button>
        @endauth
    </x-card>

    @include('projects.partials.documents')

    <section id="journal" class="mt-6">
        <h2 class="mb-3 text-lg font-bold">Journal de progression</h2>
        @if ($isOwner)
            <form method="POST" action="{{ route('projects.updates.store', $project) }}" class="mb-3">
                @csrf
                <x-field name="body" type="textarea" rows="2" label="Quoi de neuf ?" placeholder="Ex. : premier lot de 200 pots vendu en deux semaines" />
                <x-button variant="secondary" class="w-full">Publier la mise à jour</x-button>
            </form>
        @endif
        @forelse ($updates as $update)
            <article class="mb-2 border-l-2 border-brand-100 pl-3">
                <time datetime="{{ $update->created_at->toIso8601String() }}" class="text-xs text-stone-500">{{ $update->created_at->translatedFormat('d F Y') }}</time>
                <p class="whitespace-pre-line text-sm text-stone-800">{{ $update->body }}</p>
            </article>
        @empty
            <p class="text-sm text-stone-500">{{ $isOwner ? 'Racontez où en est le projet : chaque mise à jour apparaît dans le fil.' : 'Pas encore de mise à jour.' }}</p>
        @endforelse
    </section>

    <h2 class="mb-3 mt-6 text-lg font-bold">Ce que le projet cherche</h2>

    @forelse ($needs as $need)
        <x-card>
            <div class="flex items-start justify-between gap-2">
                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-900">{{ $need->type->label() }}</span>
                <x-status :status="$need->status" />
            </div>
            <h3 class="mt-2 font-semibold">{{ $need->title }}</h3>
            <p class="mt-1 whitespace-pre-line text-sm text-stone-700">{{ $need->description }}</p>

            <dl class="mt-2 space-y-1 text-sm">
                @if ($need->amount_fcfa)
                    <div><dt class="inline text-stone-500">Montant recherché :</dt> <dd class="inline font-medium">{{ number_format($need->amount_fcfa, 0, ',', "\u{00A0}") }}&nbsp;FCFA</dd> <span class="text-xs text-stone-400">(indicatif)</span></div>
                @endif
                @if ($need->profile_sought)
                    <div><dt class="inline text-stone-500">Profil recherché :</dt> <dd class="inline">{{ $need->profile_sought }}</dd></div>
                @endif
                @if ($need->deadline)
                    <div><dt class="inline text-stone-500">Échéance :</dt> <dd class="inline">{{ $need->deadline->translatedFormat('d F Y') }}</dd></div>
                @endif
                @if ($need->counterpart)
                    <div><dt class="inline text-stone-500">Contrepartie proposée :</dt> <dd class="inline">{{ $need->counterpart }}</dd></div>
                @endif
                @if ($need->outcome)
                    <div><dt class="inline text-stone-500">Résultat :</dt> <dd class="inline">{{ $need->outcome }}</dd></div>
                @endif
            </dl>

            @if ($isOwner)
                @foreach ($need->helpOffers as $offer)
                    <div class="mt-3 rounded-xl bg-stone-50 p-3 text-sm">
                        <div class="flex items-center justify-between gap-2">
                            <a href="{{ route('members.show', $offer->user) }}" class="font-semibold underline">{{ $offer->user->name }}</a>
                            <x-status :status="$offer->status" />
                        </div>
                        <p class="text-xs font-medium text-stone-500">{{ $offer->kind->label() }}</p>
                        <p class="mt-1 whitespace-pre-line text-stone-700">{{ $offer->message }}</p>
                        @if ($offer->status->value === 'proposee')
                            <div class="mt-2 flex gap-2">
                                <form method="POST" action="{{ route('help-offers.respond', $offer) }}">
                                    @csrf
                                    <input type="hidden" name="accepted" value="1">
                                    <x-button class="min-h-10 px-4 py-2 text-sm">Accepter</x-button>
                                </form>
                                <form method="POST" action="{{ route('help-offers.respond', $offer) }}">
                                    @csrf
                                    <input type="hidden" name="accepted" value="0">
                                    <x-button variant="secondary" class="min-h-10 px-4 py-2 text-sm">Décliner</x-button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach

                @if ($need->isOpen())
                    <details class="mt-3">
                        <summary class="cursor-pointer py-2 text-sm font-medium text-brand-700">Clôturer ce besoin</summary>
                        <form method="POST" action="{{ route('needs.close', $need) }}" class="mt-2">
                            @csrf
                            <x-select name="status" label="Résultat" :options="['comble' => 'Comblé', 'abandonne' => 'Abandonné']" />
                            <x-field name="outcome" label="En quelques mots (facultatif)" placeholder="Ex. : associée trouvée grâce à IDEE" />
                            <x-button variant="secondary">Clôturer</x-button>
                        </form>
                    </details>
                @endif
            @else
                @auth
                    <details class="mt-3">
                        <summary class="cursor-pointer py-2 text-sm font-semibold text-brand-700">Je peux aider</summary>
                        <form method="POST" action="{{ route('help-offers.store', $need) }}" class="mt-2">
                            @csrf
                            <x-field name="message" type="textarea" rows="3" label="Ce que vous pouvez apporter" required
                                     placeholder="Une compétence, un contact, du matériel, un local…" />
                            <x-button class="w-full">Proposer mon aide</x-button>
                        </form>
                    </details>
                    <details>
                        <summary class="cursor-pointer py-2 text-sm font-semibold text-brand-700">Je connais quelqu'un</summary>
                        <form method="POST" action="{{ route('help-offers.store', $need) }}" class="mt-2">
                            @csrf
                            <input type="hidden" name="kind" value="mise_en_relation">
                            <label for="connaissance-{{ $need->id }}" class="block text-sm font-medium text-stone-800">Qui connaissez-vous, et comment le joindre ?</label>
                            <textarea id="connaissance-{{ $need->id }}" name="message" rows="3" required minlength="20" maxlength="2000"
                                      placeholder="Ex. : mon cousin est grossiste à Tilène, je peux vous le présenter"
                                      class="mt-1 mb-3 block w-full rounded-xl border border-stone-300 px-3 py-3 text-base focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-100"></textarea>
                            <x-button variant="secondary" class="w-full">Signaler cette personne</x-button>
                        </form>
                    </details>
                    <a href="{{ route('contact-requests.create', [$project, 'besoin' => $need->id]) }}" class="mt-1 inline-block py-2 text-sm text-stone-600 underline">Contacter à propos de ce besoin</a>
                @endauth
            @endif
        </x-card>
    @empty
        <x-card class="text-sm text-stone-600">
            {{ $isOwner ? 'Publiez votre premier appel à action : dites précisément ce qui manque à votre projet.' : 'Aucun besoin ouvert pour le moment.' }}
        </x-card>
    @endforelse

    @if ($isOwner)
        <details id="nouveau-besoin" class="mb-4 rounded-2xl border border-brand-100 bg-brand-50 p-4" @if ($needDraft || $errors->hasAny(['type', 'title', 'description', 'need'])) open @endif>
            <summary class="cursor-pointer font-semibold text-brand-800">+ Ajouter un appel à action</summary>
            <form method="POST" action="{{ route('needs.store', $project) }}" class="mt-3">
                @csrf
                <x-select name="type" label="Type de besoin" :options="$needTypes" :value="$needDraft['type'] ?? null" placeholder="Choisir…" />
                <x-field name="title" label="Titre" :value="$needDraft['title'] ?? null" required placeholder="Ex. : Trouver un associé commercial" />
                <x-field name="description" type="textarea" label="Description" :value="$needDraft['description'] ?? null" required placeholder="Ce que vous cherchez, pourquoi, pour quand." />
                <x-field name="amount_fcfa" type="number" inputmode="numeric" label="Montant en FCFA (si financement)" min="1000" hint="Indicatif : aucun argent ne transite par IDEE." />
                <x-field name="profile_sought" label="Profil recherché (facultatif)" placeholder="Ex. : commercial avec un réseau à Dakar" />
                <x-field name="deadline" type="date" label="Échéance (facultatif)" />
                <x-field name="counterpart" type="textarea" rows="2" label="Contrepartie proposée (facultatif)" placeholder="Ex. : parts dans la société, rémunération, partenariat" />
                <x-button class="w-full">Publier ce besoin</x-button>
            </form>
        </details>

        <form method="POST" action="{{ route('projects.destroy', $project) }}" data-confirm="Supprimer définitivement ce projet ?" class="mt-8 text-center">
            @csrf
            @method('DELETE')
            <button type="submit" class="py-3 text-sm text-red-700 underline">Supprimer le projet</button>
        </form>
    @endif
    @auth
        @unless ($isOwner)
            <x-report-form type="projet" :id="$project->id" label="Signaler ce projet" class="mt-8 text-center" />
        @endunless
    @endauth
</x-layout>
