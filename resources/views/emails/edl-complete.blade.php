<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #ffffff; color: #333333;">

    <p>Bonjour,</p>

    <p>
        Veuillez trouver ci-joint le document d'état des lieux réalisé pour le logement
        situé au <strong>{{ $edl->adresse }}, {{ $edl->ville }}</strong>.
    </p>

    <p><strong>Récapitulatif :</strong></p>
    <ul>
        <li>Type : {{ $edl->type_label }}</li>
        @if($edl->locataire_full_name)
        <li>Locataire : {{ $edl->locataire_full_name }}</li>
        @endif
        <li>Date : {{ $edl->date_edl?->format('d/m/Y à H:i') }}</li>
        <li>Réalisé par : {{ $edl->agent_name }}</li>
    </ul>

    <p>Le PDF signé est disponible en pièce jointe de cet email.</p>

    <p>
        Cordialement,<br>
        {{ config('app.name') }}
    </p>

</body>
</html>
