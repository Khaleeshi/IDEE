@props(['title' => null, 'description' => null, 'refresh' => null])

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#077a3f">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php($description ??= 'IDEE, le réseau où les projets trouvent les personnes qui les font avancer.')
    <meta name="description" content="{{ $description }}">
    <title>{{ $title ? $title.' · IDEE' : 'IDEE · Projets et actions' }}</title>
    {{-- Aperçu affiché quand un lien est partagé sur WhatsApp (FC-04). --}}
    <meta property="og:site_name" content="IDEE">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title ?? 'IDEE · Projets et actions' }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ url('/icons/icon-512.png') }}">
    @if ($refresh)
        <meta http-equiv="refresh" content="{{ (int) $refresh }}">
    @endif
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
                <div class="flex items-center gap-2">
                    <a href="{{ route('notifications.index') }}" class="relative flex h-10 w-10 items-center justify-center rounded-full text-stone-600"
                       aria-label="Notifications{{ ($unreadNotificationsCount ?? 0) > 0 ? ', '.$unreadNotificationsCount.' non lues' : '' }}">
                        <svg aria-hidden="true" viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10 21h4"/></svg>
                        @if (($unreadNotificationsCount ?? 0) > 0)
                            <span class="absolute right-0 top-0 rounded-full bg-amber-500 px-1.5 text-[10px] font-bold text-white">{{ $unreadNotificationsCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('projects.create') }}" class="rounded-full bg-brand-700 px-4 py-2 text-sm font-semibold text-white">+ Publier</a>
                </div>
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
                <x-nav-link :href="route('projects.mine')" :active="request()->routeIs('projects.mine', 'projects.create')" icon="folder">Mes projets</x-nav-link>
                <x-nav-link :href="route('journeys.index')" :active="request()->routeIs('journeys.*')" icon="route" :badge="$dueRemindersCount ?? 0">Parcours</x-nav-link>
                <x-nav-link :href="route('contact-requests.index')" :active="request()->routeIs('contact-requests.*')" icon="inbox" :badge="$pendingRequestsCount ?? 0">Demandes</x-nav-link>
                <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')" icon="user">Profil</x-nav-link>
            </div>
        </nav>
    @endauth
</body>
</html>
