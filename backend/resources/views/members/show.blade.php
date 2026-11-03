<x-layout :title="$member->name">
    <x-card>
        <div class="flex items-center gap-3">
            <x-avatar :user="$member" size="h-16 w-16 text-xl" />
            <h1 class="text-2xl font-bold">{{ $member->name }}</h1>
        </div>
        <p class="text-sm text-stone-500">
            {{ $member->region ?? 'Région non précisée' }} · {{ $member->verification_level->label() }} · membre depuis {{ $member->created_at->translatedFormat('F Y') }}
        </p>

        @if ($member->publicRoles())
            <ul class="mt-3 flex flex-wrap gap-2">
                @foreach ($member->publicRoles() as $role)
                    <li class="rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-800">{{ $role->label() }}</li>
                @endforeach
            </ul>
        @endif

        @auth
            @unless ($member->is(auth()->user()))
                <div class="mt-3 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('follows.member', $member) }}">
                        @csrf
                        <x-button :variant="$following ? 'secondary' : 'primary'" class="min-h-10 px-4 py-2 text-sm">{{ $following ? '✓ Suivi' : 'Suivre' }}</x-button>
                    </form>
                    @if (($member->contact_preferences['accepts_requests'] ?? true) !== false)
                        <x-button :href="route('members.contact', $member)" variant="secondary" class="min-h-10 px-4 py-2 text-sm">Contacter à propos de mon projet</x-button>
                    @endif
                </div>
            @endunless
        @endauth

        @if ($member->availability)
            <p class="mt-3 text-sm font-medium {{ $member->availability->value === 'indisponible' ? 'text-stone-500' : 'text-brand-800' }}">{{ $member->availability->label() }}</p>
        @endif

        @if ($member->bio)
            <p class="mt-3 whitespace-pre-line text-stone-700">{{ $member->bio }}</p>
        @endif

        @php($reviewSummary = app(\App\Services\Accompaniments\ReviewService::class)->summary($member))
        @if ($reviewSummary['count'] > 0)
            <p class="mt-3 text-sm"><a href="#avis" class="font-semibold text-brand-800">★ {{ number_format($reviewSummary['average'], 1, ',', ' ') }}/5</a> <span class="text-stone-500">· {{ $reviewSummary['count'] }} avis après un accompagnement ou une prestation</span></p>
        @endif
        @php($memberProfiles = $member->publicMemberProfiles())
        @if ($memberProfiles->isNotEmpty())
            {{-- PM-02 : les métiers du membre, le principal en premier. --}}
            <ul class="mt-3 space-y-2">
                @foreach ($memberProfiles as $profile)
                    <li class="rounded-xl bg-stone-50 p-3 text-sm">
                        <p class="font-semibold text-stone-900">{{ $profile->label() }}@if ($profile->experience_years) <span class="font-normal text-stone-500">· {{ $profile->experience_years }} an{{ $profile->experience_years > 1 ? 's' : '' }} d'expérience</span>@endif</p>
                        <p class="text-xs text-brand-800">{{ implode(' · ', $profile->offerLabels()) }}@if ($profile->offers_paid) · {{ $profile->rate ?? 'tarif sur demande' }}@endif</p>
                        @if ($profile->description)
                            <p class="mt-1 whitespace-pre-line text-stone-700">{{ $profile->description }}</p>
                        @endif
                        @auth
                            @if (! $member->is(auth()->user()) && ($profile->offers_mentoring || $profile->offers_volunteer))
                                <a href="{{ route('accompaniments.create', $profile) }}" class="mt-2 inline-block font-medium text-brand-700 underline">Demander un accompagnement</a>
                            @endif
                        @endauth
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($member->expertise && $member->hasVerifiedIdentity())
            {{-- SE-01 : services et tarif indicatif d'un expert vérifié. --}}
            <div class="mt-3 rounded-xl bg-stone-50 p-3 text-sm">
                <p><span class="text-stone-500">Services :</span> {{ $member->expertise }}</p>
                <p class="mt-1"><span class="text-stone-500">Tarif indicatif :</span> {{ $member->rate ?? 'sur demande' }}</p>
                @auth
                    @unless ($member->is(auth()->user()))
                        <x-button :href="route('quotes.create', $member)" variant="secondary" class="mt-2 w-full min-h-10 py-2 text-sm">Demander un devis</x-button>
                    @endunless
                @endauth
            </div>
        @endif

        @if ($member->skills)
            <p class="mt-3 text-sm"><span class="text-stone-500">Compétences :</span> {{ implode(', ', $member->skills) }}</p>
        @endif
        @if ($member->languages)
            <p class="mt-1 text-sm"><span class="text-stone-500">Langues :</span> {{ implode(', ', $member->languages) }}</p>
        @endif

        <div class="mt-3 border-t border-stone-100 pt-3">
            <p class="text-sm font-medium">Réactivité</p>
            @include('members.partials.reactivity', ['reactivity' => $reactivity])
        </div>
    </x-card>

    @if ($organisations->isNotEmpty())
        <p class="mt-3 text-sm text-stone-600">Membre de
            @foreach ($organisations as $organisation)
                <a href="{{ route('organisations.show', $organisation) }}" class="font-medium underline">{{ $organisation->name }}</a>@if ($organisation->isVerified()) ✓@endif{{ $loop->last ? '' : ',' }}
            @endforeach
        </p>
    @endif

    <h2 class="mb-3 mt-6 text-lg font-bold">Projets</h2>
    @forelse ($projects as $project)
        <x-project-card :project="$project" />
    @empty
        <p class="text-sm text-stone-600">Aucun projet actif.</p>
    @endforelse

    {{-- CO-03 : collaborations confirmées par les deux parties. --}}
    @if ($collaborations->isNotEmpty())
        <h2 class="mb-3 mt-6 text-lg font-bold">Collaborations confirmées</h2>
        <ul class="space-y-2">
            @foreach ($collaborations as $collaboration)
                @php($partner = $collaboration->otherThan($member))
                <li class="rounded-xl border border-stone-200 bg-white p-3 text-sm">
                    <p class="font-medium text-stone-900">{{ $collaboration->summary }}</p>
                    <p class="mt-1 text-xs text-stone-500">
                        Avec <a href="{{ route('members.show', $partner) }}" class="underline">{{ $partner->name }}</a>
                        @if ($collaboration->project) · <a href="{{ route('projects.show', $collaboration->project) }}" class="underline">{{ $collaboration->project->title }}</a>@endif
                        · {{ $collaboration->confirmed_at->translatedFormat('F Y') }}
                    </p>
                </li>
            @endforeach
        </ul>
    @endif
    @auth
        @unless ($member->is(auth()->user()))
            <x-report-form type="membre" :id="$member->id" label="Signaler ce profil" class="mt-6 text-center" />
        @endunless
    @endauth
    @php($memberReviews = \App\Models\Review::where('reviewee_id', $member->id)->with('reviewer')->latest()->limit(10)->get())
    @if ($memberReviews->isNotEmpty())
        <h2 id="avis" class="mb-2 mt-5 font-semibold">Avis</h2>
        @foreach ($memberReviews as $review)
            @include('accompaniments.partials.review', ['review' => $review])
        @endforeach
    @endif
</x-layout>
