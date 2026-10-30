<x-layout title="Notifications">
    <div class="mb-3 flex items-center justify-between gap-2">
        <h1 class="text-2xl font-bold">Notifications</h1>
        <a href="{{ route('alerts.index') }}" class="py-2 text-sm font-medium text-brand-700 underline">Mes alertes</a>
    </div>

    @if ($pushKey)
        {{-- Visible seulement si le navigateur sait recevoir des notifications push (NO-01). --}}
        <button type="button" hidden data-push-key="{{ $pushKey }}" data-push-url="{{ route('push.subscribe') }}"
                class="mb-4 inline-flex min-h-12 w-full items-center justify-center rounded-xl border border-brand-100 bg-brand-50 px-5 text-sm font-semibold text-brand-800">
            Recevoir les notifications sur ce téléphone
        </button>
    @endif

    @forelse ($notifications as $notification)
        @php($link = \App\Support\NotificationLink::for($notification->data))
        <a @if ($link) href="{{ $link }}" @endif class="mb-2 block rounded-xl border bg-white p-3 text-sm {{ $notification->read_at ? 'border-stone-200' : 'border-brand-100 bg-brand-50' }}">
            <span class="block text-stone-800">{{ $notification->data['message'] ?? '' }}</span>
            <time datetime="{{ $notification->created_at->toIso8601String() }}" class="text-xs text-stone-500">{{ $notification->created_at->diffForHumans() }}</time>
        </a>
    @empty
        <x-card class="text-center text-sm text-stone-600">Aucune notification pour le moment.</x-card>
    @endforelse
</x-layout>
