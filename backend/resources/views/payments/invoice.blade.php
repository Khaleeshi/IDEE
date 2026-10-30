<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Facture {{ $invoice->number }}</title>
    @include('payments.partials.print-style')
</head>
<body>
    <p class="no-print note">Pour enregistrer en PDF : menu du navigateur, puis « Imprimer », puis « Enregistrer au format PDF ».</p>
    @include('payments.partials.seller')
    <h1>Facture {{ $invoice->number }}</h1>
    <p class="meta">Émise le {{ $invoice->created_at->translatedFormat('d F Y') }}@if ($invoice->due_on) · à régler avant le {{ $invoice->due_on->translatedFormat('d F Y') }}@endif · {{ $invoice->status->label() }}@if ($invoice->paid_at) le {{ $invoice->paid_at->translatedFormat('d F Y') }}@endif</p>

    <p><strong>Client :</strong> {{ $invoice->organisation->name }}@if ($invoice->organisation->rccm)<br>RCCM {{ $invoice->organisation->rccm }} · NINEA {{ $invoice->organisation->ninea }}@endif</p>
    <table>
        <tr><th>Désignation</th><th>Montant</th></tr>
        <tr><td>{{ $invoice->label }}</td><td>{{ number_format($invoice->amount_fcfa, 0, ',', "\u{00A0}") }} FCFA</td></tr>
        <tr><th>Total toutes taxes comprises</th><th>{{ number_format($invoice->amount_fcfa, 0, ',', "\u{00A0}") }} FCFA</th></tr>
    </table>
</body>
</html>
