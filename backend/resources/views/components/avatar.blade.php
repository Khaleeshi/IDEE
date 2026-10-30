@props(['user', 'size' => 'h-12 w-12 text-base'])

{{-- Photo de profil, ou initiales quand le membre n'en a pas (CP-02). --}}
@if ($user->photoUrl())
    <img src="{{ $user->photoUrl() }}" alt="" width="256" height="256" loading="lazy" {{ $attributes->merge(['class' => "shrink-0 rounded-full object-cover {$size}"]) }}>
@else
    <span aria-hidden="true" {{ $attributes->merge(['class' => "flex shrink-0 items-center justify-center rounded-full bg-brand-100 font-semibold text-brand-800 {$size}"]) }}>
        {{ mb_strtoupper(collect(explode(' ', trim($user->name)))->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->join('')) }}
    </span>
@endif
