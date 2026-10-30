@props(['href', 'active' => false, 'icon', 'badge' => 0])

@php
    $paths = [
        'home' => 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z',
        'folder' => 'M3 6a1 1 0 0 1 1-1h5l2 2h9a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z',
        'plus' => 'M12 5v14M5 12h14',
        'inbox' => 'M4 13h4l2 3h4l2-3h4M5 5h14l1 8v6H4v-6z',
        'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm-7 9a7 7 0 0 1 14 0',
    ];
@endphp

<a href="{{ $href }}" @if ($active) aria-current="page" @endif
   class="relative flex min-h-14 flex-col items-center justify-center gap-1 py-2 {{ $active ? 'text-brand-700 font-semibold' : 'text-stone-500' }}">
    <svg aria-hidden="true" viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <path d="{{ $paths[$icon] }}"/>
    </svg>
    <span>{{ $slot }}</span>
    @if ($badge > 0)
        <span class="absolute right-[22%] top-1 rounded-full bg-amber-500 px-1.5 text-[10px] font-bold text-white">{{ $badge }}</span>
    @endif
</a>
