<x-layout title="Demandes de contact">
    <h1 class="mb-4 text-2xl font-bold">Demandes de contact</h1>

    <div class="mb-4 grid grid-cols-2 rounded-xl bg-stone-200 p-1 text-sm font-medium" role="tablist">
        <a href="{{ route('contact-requests.index') }}" role="tab" aria-selected="{{ $sent ? 'false' : 'true' }}"
           class="rounded-lg py-2 text-center {{ $sent ? 'text-stone-600' : 'bg-white shadow-sm' }}">Reçues</a>
        <a href="{{ route('contact-requests.index', ['boite' => 'envoyees']) }}" role="tab" aria-selected="{{ $sent ? 'true' : 'false' }}"
           class="rounded-lg py-2 text-center {{ $sent ? 'bg-white shadow-sm' : 'text-stone-600' }}">Envoyées</a>
    </div>

    @unless ($sent)
        <p class="mb-3 text-sm text-stone-600">Chaque demande attend une réponse : intéressé, pas maintenant ou refusée avec un motif. Sans réponse, elle expire au bout de {{ config('idee.contact_expire_after_days') }} jours.</p>
    @endunless

    @forelse ($requests as $request)
        @php($other = $sent ? $request->recipient : $request->sender)
        <a href="{{ route('contact-requests.show', $request) }}" class="mb-3 block rounded-2xl border bg-white p-4 shadow-sm {{ ! $sent && $request->status->isPending() ? 'border-amber-300' : 'border-stone-200' }}">
            <div class="flex items-center justify-between gap-2">
                <span class="font-semibold">{{ $sent ? 'À' : 'De' }} {{ $other->name }}</span>
                <x-status :status="$request->status" />
            </div>
            @if ($request->project)
                <p class="mt-1 text-sm text-stone-500">Projet : {{ $request->project->title }}@if ($request->need) · {{ $request->need->title }}@endif</p>
            @endif
            <p class="mt-2 line-clamp-2 text-sm text-stone-700">{{ $request->message }}</p>
            <p class="mt-2 text-xs text-stone-400">{{ $request->created_at->diffForHumans() }}</p>
        </a>
    @empty
        <x-card class="text-center text-stone-600">
            {{ $sent ? 'Vous n\'avez envoyé aucune demande.' : 'Vous n\'avez reçu aucune demande pour le moment.' }}
        </x-card>
    @endforelse

    {{ $requests->links() }}
</x-layout>
