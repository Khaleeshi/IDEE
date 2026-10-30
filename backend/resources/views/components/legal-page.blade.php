@props(['title'])

<x-layout :title="$title">
    <article class="space-y-3 text-sm leading-relaxed text-stone-700 [&_a]:font-medium [&_a]:text-brand-700 [&_a]:underline [&_h2]:pt-4 [&_h2]:text-base [&_h2]:font-semibold [&_h2]:text-stone-900 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5">
        <h1 class="text-2xl font-bold text-stone-900">{{ $title }}</h1>
        <p class="text-xs text-stone-500">Version {{ config('idee.legal.version') }}</p>
        @unless (config('idee.legal.validated'))
            <p role="note" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
                Texte provisoire : il doit être validé par un avocat avant l'ouverture d'IDEE au public.
            </p>
        @endunless
        {{ $slot }}
    </article>
</x-layout>
