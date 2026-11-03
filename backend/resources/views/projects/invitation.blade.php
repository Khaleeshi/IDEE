<x-layout title="Invitation dans une équipe">
    <h1 class="text-2xl font-bold">Rejoindre l'équipe de « {{ $member->project->title }} »</h1>
    <p class="mt-1 text-stone-600">{{ $member->project->owner->name }} vous invite comme <strong>{{ $member->role }}</strong>.</p>

    <x-card class="mt-4 text-sm">
        <p>{{ \Illuminate\Support\Str::limit($member->project->summary, 300) }}</p>
        <a href="{{ route('projects.show', $member->project) }}" class="mt-2 inline-block font-medium text-brand-700 underline">Voir le projet</a>
        <p class="mt-3 text-stone-600">En acceptant, vous apparaissez dans l'équipe du projet, et vous accédez à son tableau de tâches et à son calendrier. Vous pouvez quitter l'équipe à tout moment en écrivant au porteur.</p>
    </x-card>

    @if ($member->isPending())
        <div class="grid grid-cols-2 gap-2">
            <form method="POST" action="{{ route('team.invitation.answer', $member) }}">@csrf<input type="hidden" name="accepted" value="1"><x-button class="w-full">Accepter</x-button></form>
            <form method="POST" action="{{ route('team.invitation.answer', $member) }}">@csrf<input type="hidden" name="accepted" value="0"><x-button variant="secondary" class="w-full">Décliner</x-button></form>
        </div>
    @else
        <p class="text-sm text-stone-600">Vous faites partie de l'équipe.</p>
    @endif
</x-layout>
