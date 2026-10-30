{{-- Documents privés du projet : accès sur autorisation du porteur, journalisé et révocable (PR-05, RG-05). --}}
@if ($isOwner || $documentsCount > 0)
    <section id="documents" class="mt-6" aria-labelledby="titre-documents">
        <h2 id="titre-documents" class="mb-1 text-lg font-bold">Documents privés</h2>
        <p class="mb-3 text-sm text-stone-500">Visibles seulement par les membres à qui le porteur donne accès. Chaque consultation est enregistrée.</p>

        @if ($documents !== null)
            @forelse ($documents as $document)
                <div class="mb-2 flex items-center justify-between gap-2 rounded-xl border border-stone-200 bg-white p-3 text-sm">
                    <a href="{{ route('documents.show', $document) }}" class="font-medium text-brand-800 underline">{{ $document->title }}</a>
                    @if ($isOwner)
                        <form method="POST" action="{{ route('documents.destroy', $document) }}" data-confirm="Supprimer ce document ?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="py-2 text-xs text-red-700 underline">Supprimer</button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="mb-2 text-sm text-stone-500">Aucun document pour le moment.</p>
            @endforelse
        @else
            <x-card class="text-sm">
                <p>{{ $documentsCount }} document{{ $documentsCount > 1 ? 's' : '' }} privé{{ $documentsCount > 1 ? 's' : '' }} : business plan, chiffres, statuts…</p>
                @auth
                    @if ($documentAccess?->status->value === 'demandee')
                        <p class="mt-2 font-medium text-amber-800">Votre demande attend la réponse du porteur.</p>
                    @else
                        @if (in_array($documentAccess?->status->value, ['refusee', 'revoquee'], true))
                            <p class="mt-2 text-stone-500">Accès {{ mb_strtolower($documentAccess->status->label()) }}. Vous pouvez renouveler votre demande.</p>
                        @endif
                        <form method="POST" action="{{ route('document-access.request', $project) }}" class="mt-3">
                            @csrf
                            <x-button variant="secondary" class="w-full">Demander l'accès aux documents</x-button>
                        </form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="mt-2 inline-block font-medium text-brand-700 underline">Connectez-vous pour demander l'accès</a>
                @endauth
            </x-card>
        @endif

        @if ($isOwner)
            <details class="mb-3 rounded-2xl border border-stone-200 bg-white p-4" @if ($errors->hasAny(['file'])) open @endif>
                <summary class="cursor-pointer font-semibold text-brand-800">+ Déposer un document</summary>
                <form method="POST" action="{{ route('documents.store', $project) }}" enctype="multipart/form-data" class="mt-3">
                    @csrf
                    <label for="document-titre" class="block text-sm font-medium text-stone-800">Nom du document</label>
                    <input id="document-titre" name="title" required maxlength="150" placeholder="Ex. : Business plan 2026"
                           class="mt-1 block w-full rounded-xl border border-stone-300 px-3 py-3 text-base focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-100">
                    <label for="document-fichier" class="mt-3 block text-sm font-medium text-stone-800">Fichier (PDF, Office ou photo)</label>
                    <input id="document-fichier" name="file" type="file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,image/jpeg,image/png" class="mt-1 block w-full text-sm">
                    <x-button class="mt-3 w-full">Déposer</x-button>
                </form>
            </details>

            @foreach ($accesses as $access)
                <div class="mb-2 rounded-xl bg-stone-50 p-3 text-sm">
                    <div class="flex items-center justify-between gap-2">
                        <a href="{{ route('members.show', $access->user) }}" class="font-semibold underline">{{ $access->user->name }}</a>
                        <span class="text-xs text-stone-500">Accès {{ mb_strtolower($access->status->label()) }}</span>
                    </div>
                    @if ($access->status->value === 'demandee')
                        <div class="mt-2 flex gap-2">
                            <form method="POST" action="{{ route('document-access.decide', $access) }}">
                                @csrf
                                <input type="hidden" name="granted" value="1">
                                <x-button class="min-h-10 px-4 py-2 text-sm">Accorder</x-button>
                            </form>
                            <form method="POST" action="{{ route('document-access.decide', $access) }}">
                                @csrf
                                <input type="hidden" name="granted" value="0">
                                <x-button variant="secondary" class="min-h-10 px-4 py-2 text-sm">Refuser</x-button>
                            </form>
                        </div>
                    @elseif ($access->status->value === 'accordee')
                        <form method="POST" action="{{ route('document-access.revoke', $access) }}" class="mt-1">
                            @csrf
                            <button type="submit" class="py-2 text-xs text-red-700 underline">Révoquer l'accès</button>
                        </form>
                    @endif
                </div>
            @endforeach

            @if ($documentLogs->isNotEmpty())
                <details class="mt-2">
                    <summary class="cursor-pointer py-2 text-sm font-medium text-brand-700">Journal d'accès</summary>
                    <ul class="mt-1 space-y-1 text-xs text-stone-600">
                        @foreach ($documentLogs as $log)
                            <li>{{ $log->created_at->translatedFormat('d/m/Y H:i') }} · {{ $log->user->name }} {{ $log->action->label() }}@if ($log->document) « {{ $log->document->title }} »@endif</li>
                        @endforeach
                    </ul>
                </details>
            @endif
        @endif
    </section>
@endif
