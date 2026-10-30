@props(['name', 'label', 'options' => [], 'value' => null, 'placeholder' => null])

@php
    $id = 'champ-'.$name;
    $current = (string) old($name, $value);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'mb-4']) }}>
    <label for="{{ $id }}" class="block text-sm font-medium text-stone-800">{{ $label }}</label>
    <select id="{{ $id }}" name="{{ $name }}"
            class="mt-1 block w-full rounded-xl border border-stone-300 bg-white px-3 py-3 text-base focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-100">
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($current === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
