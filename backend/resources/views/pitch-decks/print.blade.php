<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Présentation · {{ $project->title }}</title>
    <style>
        @page { size: A4 landscape; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Helvetica Neue', Arial, sans-serif; color: #1c1917; background: #e7e5e4; }
        .slide { width: 100%; max-width: 60rem; aspect-ratio: 297 / 210; margin: 1rem auto; padding: 3rem 3.5rem; background: #fff; display: flex; flex-direction: column; justify-content: center; page-break-after: always; break-after: page; }
        .slide h1 { font-size: 2.6rem; margin: 0; color: #065f35; }
        .slide h2 { font-size: 2rem; margin: 0 0 1.5rem; color: #065f35; }
        .slide ul { font-size: 1.35rem; line-height: 1.6; margin: 0; padding-left: 1.4rem; }
        .tagline { font-size: 1.4rem; color: #44403c; margin-top: 1rem; }
        .meta, .note { color: #78716c; font-size: .85rem; }
        .no-print { max-width: 60rem; margin: 1rem auto; padding: 0 1rem; }
        @media print { body { background: #fff; } .no-print { display: none; } .slide { margin: 0; max-width: none; height: 100vh; aspect-ratio: auto; } }
    </style>
</head>
<body>
    <p class="no-print note">Pour enregistrer en PDF : menu du navigateur, puis « Imprimer », orientation paysage, puis « Enregistrer au format PDF ».</p>

    <section class="slide">
        <h1>{{ $project->title }}</h1>
        <p class="tagline">{{ $deck->content['tagline'] ?: $project->summary }}</p>
        <p class="meta">{{ $project->region }}@if ($project->sector) · {{ $project->sector->name }}@endif · {{ now()->translatedFormat('F Y') }}</p>
    </section>

    @foreach ($deck->content['slides'] as $slide)
        <section class="slide">
            <h2>{{ $slide['title'] }}</h2>
            <ul>
                @foreach ($slide['bullets'] as $bullet)
                    <li>{{ $bullet }}</li>
                @endforeach
            </ul>
        </section>
    @endforeach

    <p class="no-print note">Présentation préparée par le porteur du projet avec l'assistant d'IDEE. Elle s'adresse à des interlocuteurs rencontrés individuellement et ne constitue ni une offre de titres au public ni une promesse de rendement.</p>
</body>
</html>
