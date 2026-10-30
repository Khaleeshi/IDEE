@props(['title' => null])

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#077a3f">
    <meta name="description" content="IDEE, le réseau où les projets trouvent les personnes qui les font avancer.">
    <title>{{ $title ? $title.' · IDEE' : 'IDEE · Projets et actions' }}</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if (session('clear_offline_cache'))
        <script>if ('caches' in window) { caches.keys().then((keys) => keys.forEach((key) => caches.delete(key))); }</script>
    @endif
</head>
<body class="min-h-screen bg-stone-50 font-sans text-stone-900 antialiased">
    <header class="sticky top-0 z-10 border-b border-stone-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-14 max-w-2xl items-center justify-between px-4">
            <a href="{{ route('home') }}" class="text-xl font-bold tracking-tight text-brand-700">IDEE</a>
            @guest
                <a href="{{ route('login') }}" class="rounded-full bg-brand-700 px-4 py-2 text-sm font-semibold text-white">Se connecter</a>
            @else
                <a href="{{ route('members.show', auth()->user()) }}" class="text-sm text-stone-600">{{ auth()->user()->name }}</a>
            @endguest
        </div>
    </header>

    <main class="mx-auto max-w-2xl px-4 pb-28 pt-4">
        @if (session('status'))
            <div role="status" class="mb-4 rounded-xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-brand-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-semibold">Quelque chose ne va pas :</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{ $slot }}
    </main>

    @auth
        <nav aria-label="Navigation principale" class="fixed inset-x-0 bottom-0 z-10 border-t border-stone-200 bg-white pb-[env(safe-area-inset-bottom)]">
            <div class="mx-auto grid max-w-2xl grid-cols-5 text-xs">
                <x-nav-link :href="route('home')" :active="request()->routeIs('home')" icon="home">Accueil</x-nav-link>
                <x-nav-link :href="route('projects.mine')" :active="request()->routeIs('projects.mine')" icon="folder">Mes projets</x-nav-link>
                <x-nav-link :href="route('projects.create')" :active="request()->routeIs('projects.create')" icon="plus">Publier</x-nav-link>
                <x-nav-link :href="route('contact-requests.index')" :active="request()->routeIs('contact-requests.*')" icon="inbox" :badge="$pendingRequestsCount ?? 0">Demandes</x-nav-link>
                <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')" icon="user">Profil</x-nav-link>
            </div>
        </nav>
    @endauth
</body>
</html>
