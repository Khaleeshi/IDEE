<x-layout title="Notifications">
    <h1 class="mb-3 text-2xl font-bold">Notifications</h1>

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
