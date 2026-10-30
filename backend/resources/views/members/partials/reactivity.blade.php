@if ($reactivity['response_rate'] === null)
    <p class="mt-1 text-sm text-stone-500">Pas encore de demandes reçues.</p>
@else
    @php
        $hours = $reactivity['median_response_hours'];
        $delay = match (true) {
            $hours === null => null,
            $hours < 1 => 'en moins d\'une heure',
            $hours < 24 => "en {$hours} h",
            default => 'en '.intdiv($hours, 24).' jour(s)',
        };
    @endphp
    <p class="mt-1 text-sm text-stone-600">
        Répond à <strong>{{ $reactivity['response_rate'] }} %</strong> des demandes{{ $delay ? ', '.$delay.' en moyenne' : '' }}.
    </p>
@endif
