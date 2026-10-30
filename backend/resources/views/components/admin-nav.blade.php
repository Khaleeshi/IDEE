<nav aria-label="Administration" class="mb-4 flex flex-wrap gap-2 text-sm">
    @foreach (['admin.dashboard' => 'Indicateurs', 'admin.reports' => 'Signalements', 'admin.verifications' => 'Vérifications', 'admin.guides' => 'Fiches pratiques'] as $name => $label)
        <a href="{{ route($name) }}" @if (request()->routeIs($name.'*')) aria-current="page" @endif
           class="rounded-full border px-3 py-1.5 {{ request()->routeIs($name.'*') ? 'border-brand-700 bg-brand-700 text-white' : 'border-stone-300 bg-white text-stone-700' }}">{{ $label }}</a>
    @endforeach
</nav>
