@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'minLength' => null,
])

@php
    $id = 'champ-'.str_replace(['[', ']', '.'], '-', $name);
    $current = old($name, $value);
    $classes = 'mt-1 block w-full rounded-xl border px-3 py-3 text-base focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-100 '
        .($errors->has($name) ? 'border-red-400' : 'border-stone-300');
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'mb-4']) }}>
    <label for="{{ $id }}" class="block text-sm font-medium text-stone-800">{{ $label }}</label>

    @if ($type === 'textarea')
        {{-- IA-03 : bouton « Dicter » ajouté par le navigateur quand il sait reconnaître la voix. --}}
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $attributes->get('rows', 4) }}" data-dictation
                  @if ($minLength) data-min-length="{{ $minLength }}" data-counter="{{ $id }}-compteur" @endif
                  {{ $attributes->except(['class', 'rows'])->merge(['class' => $classes]) }}>{{ $current }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $current }}"
               {{ $attributes->except('class')->merge(['class' => $classes]) }}>
    @endif

    @if ($minLength)
        <p id="{{ $id }}-compteur" class="mt-1 text-xs text-stone-500" aria-live="polite"></p>
    @endif

    @if ($hint)
        <p class="mt-1 text-xs text-stone-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
