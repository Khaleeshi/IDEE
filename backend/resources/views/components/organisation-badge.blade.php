@props(['organisation'])

@if ($organisation->isVerified())
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full border border-brand-100 bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-800']) }}>✓ Vérifiée</span>
@endif
