<x-layout title="Mes appareils">
    <a href="{{ route('profile.edit') }}" class="mb-2 inline-block text-sm text-stone-500">← Mon profil</a>
    <h1 class="text-2xl font-bold">Mes appareils</h1>
    <p class="mb-4 text-sm text-stone-600">Où votre compte est ouvert. Un téléphone perdu ou un appareil que vous ne reconnaissez pas ? Déconnectez-le ici.</p>

    @if (session('status'))
        <p class="mb-4 rounded-xl bg-brand-50 p-3 text-sm text-brand-800" role="status">{{ session('status') }}</p>
    @endif

    <ul class="mb-4 divide-y divide-stone-100 rounded-2xl border border-stone-200 bg-white">
        @forelse ($devices as $device)
            <li class="flex items-center justify-between gap-3 p-4">
                <span class="min-w-0">
                    <span class="block font-medium">{{ $device['name'] }}</span>
                    <span class="block text-xs text-stone-500">
                        {{ $device['kind'] === 'application' ? 'Application' : 'Site web' }}
                        @if ($device['last_active_at']) · actif {{ $device['last_active_at']->diffForHumans() }} @endif
                    </span>
                </span>
                @if ($device['current'])
                    <span class="shrink-0 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-800">Cet appareil</span>
                @else
                    <form method="POST" action="{{ route('devices.destroy', $device['id']) }}" class="shrink-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="py-2 text-sm font-medium text-red-700 underline">Déconnecter</button>
                    </form>
                @endif
            </li>
        @empty
            <li class="p-4 text-sm text-stone-600">Aucun autre appareil n'est connecté.</li>
        @endforelse
    </ul>

    @if ($devices->where('current', false)->isNotEmpty())
        <form method="POST" action="{{ route('devices.destroy-others') }}">
            @csrf
            <x-button variant="danger" class="w-full">Déconnecter tous les autres appareils</x-button>
        </form>
    @endif

    <p class="mt-4 text-xs text-stone-500">Un appareil inutilisé pendant {{ config('idee.sessions.idle_days') }} jours est déconnecté automatiquement.</p>
</x-layout>
