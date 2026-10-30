<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Reçu {{ $payment->receipt_number }}</title>
    @include('payments.partials.print-style')
</head>
<body>
    <p class="no-print note">Pour enregistrer en PDF : menu du navigateur, puis « Imprimer », puis « Enregistrer au format PDF ».</p>
    @include('payments.partials.seller')
    <h1>Reçu {{ $payment->receipt_number }}</h1>
    <p class="meta">Payé le {{ $payment->paid_at->translatedFormat('d F Y à H\hi') }} par {{ $payment->method->label() }}@if ($payment->payer_reference) (transfert {{ $payment->payer_reference }})@endif</p>

    <p><strong>Client :</strong> {{ $payment->user->name }}</p>
    <table>
        <tr><th>Désignation</th><th>Quantité</th><th>Montant</th></tr>
        <tr>
            <td>{{ $payment->offer->label() }}@if ($payment->project) — {{ $payment->project->title }}@endif</td>
            <td>{{ $payment->quantity }} × {{ $payment->offer->days() }} jours</td>
            <td>{{ number_format($payment->amount_fcfa, 0, ',', "\u{00A0}") }} FCFA</td>
        </tr>
        <tr><th colspan="2">Total toutes taxes comprises</th><th>{{ number_format($payment->amount_fcfa, 0, ',', "\u{00A0}") }} FCFA</th></tr>
    </table>
    <p class="note">Référence de paiement : {{ $payment->id }}. Paiement unique, sans renouvellement automatique.</p>
</body>
</html>
