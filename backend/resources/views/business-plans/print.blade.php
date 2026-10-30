<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Business plan · {{ $project->title }}</title>
    <style>
        body { font-family: Georgia, 'Times New Roman', serif; max-width: 42rem; margin: 2rem auto; padding: 0 1rem; color: #1c1917; line-height: 1.55; }
        h1 { font-size: 1.6rem; margin-bottom: .2rem; }
        h2 { font-size: 1.15rem; margin-top: 1.6rem; border-bottom: 1px solid #d6d3d1; padding-bottom: .2rem; }
        .meta, .note { color: #57534e; font-size: .85rem; }
        table { width: 100%; border-collapse: collapse; font-size: .9rem; }
        td, th { border: 1px solid #d6d3d1; padding: .35rem .5rem; text-align: left; vertical-align: top; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <p class="no-print note">Pour enregistrer en PDF : menu du navigateur, puis « Imprimer », puis « Enregistrer au format PDF ».</p>
    <h1>{{ $project->title }}</h1>
    <p class="meta">Business plan · version {{ $plan->version }} · {{ ($plan->edited_at ?? $plan->generated_at)?->translatedFormat('d F Y') }}</p>

    @foreach ($plan->sections as $section)
        @if (filled($section['content']))
            <h2>{{ $section['title'] }}</h2>
            <p style="white-space: pre-line">{{ $section['content'] }}</p>
        @endif
    @endforeach

    @if ($plan->assumptions)
        <h2>Hypothèses</h2>
        <table>
            <tr><th>Hypothèse</th><th>Valeur</th><th>Sur quoi elle repose</th></tr>
            @foreach ($plan->assumptions as $assumption)
                <tr><td>{{ $assumption['label'] ?? '' }}</td><td>{{ $assumption['value'] ?? '' }}</td><td>{{ $assumption['basis'] ?? '' }}</td></tr>
            @endforeach
        </table>
    @endif

    <p class="note">Document préparé par le porteur du projet avec l'assistant d'IDEE. Les chiffres sont des hypothèses à vérifier ; ce document n'est ni une offre de titres ni une promesse de rendement.</p>
</body>
</html>
