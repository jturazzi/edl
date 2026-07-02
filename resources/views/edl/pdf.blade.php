<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>État des lieux</title>
<style>
    /* ── Base ─────────────────────────────────────────────────── */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 10px;
        color: #1e293b;
        background: #ffffff;
        margin: 0;
        padding: 0 0 30px 0;
    }

    /* ── Page margins (fix top-of-page spacing) ──────────────── */
    @page { margin: 16mm 14mm 18mm 14mm; }

    /* ── En-tête ──────────────────────────────────────────────── */
    .header-wrap {
        background: #ffffff;
        margin: 0 0 16px 0;
        border-bottom: 2px solid #e2e8f0;
    }
    .header-logo-td {
        width: 110px;
        padding: 14px 16px 14px 24px;
        vertical-align: middle;
        border-right: 1px solid #e2e8f0;
    }
    .header-logo-inner {
        background: #ffffff;
        padding: 6px 8px;
    }
    .header-info-td {
        padding: 14px 24px;
        vertical-align: middle;
    }
    .header-type {
        font-size: 16px;
        font-weight: bold;
        color: #1e293b;
        letter-spacing: 0.3px;
        margin-bottom: 4px;
    }
    .header-address {
        font-size: 10px;
        color: #64748b;
        margin-bottom: 0;
    }
    /* bande infos sous le header */
    .header-info-band-td {
        background: {{ config('app.pdf_color', '#33CCFF') }};
        padding: 8px 24px;
    }
    .hib-label { font-size: 8px; color: rgba(0,0,0,0.55); text-transform: uppercase; letter-spacing: 0.3px; }
    .hib-value { font-size: 10px; font-weight: bold; color: #1e293b; }

    /* ── Bloc informations (supprimé — intégré au header) ─────── */

    /* ── Sections ─────────────────────────────────────────────── */
    .section { margin: 0 24px 12px; page-break-inside: avoid; }
    .section-header { background: {{ config('app.pdf_color', '#33CCFF') }}; }
    .section-header-td { padding: 6px 12px; color: #1e293b; font-size: 10px; font-weight: bold; letter-spacing: 0.3px; }
    .section-header-count-td { padding: 6px 12px; color: rgba(0,0,0,0.5); font-size: 9px; text-align: right; white-space: nowrap; }
    .section-body { border: 1px solid #e2e8f0; border-top: none; }

    /* ── Tableaux de données ──────────────────────────────────── */
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table thead tr { background: #f1f5f9; }
    .data-table th {
        padding: 5px 8px;
        font-size: 8px;
        font-weight: bold;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border-bottom: 1px solid #cbd5e1;
        text-align: left;
    }
    .data-table tbody tr { background: #ffffff; }
    .data-table tbody tr.row-alt { background: #f8fafc; }
    .data-table td { padding: 4px 8px; font-size: 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .col-label { width: 35%; }
    .col-etat  { width: 22%; white-space: nowrap; }
    .col-nb    { width: 8%; text-align: center; }
    .col-dim   { width: 14%; }
    .col-obs   { font-style: italic; color: #64748b; }

    /* ── États ────────────────────────────────────────────────── */
    .etat-bon     { color: #059669; font-weight: bold; }
    .etat-usure   { color: #d97706; font-weight: bold; }
    .etat-mauvais { color: #dc2626; font-weight: bold; }
    .etat-nr      { color: #94a3b8; font-style: italic; }

    /* ── Photos ───────────────────────────────────────────────── */
    .photos-label {
        font-size: 9px;
        font-weight: bold;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 6px 8px 4px;
        background: #d6f5ff;
        border-top: 1px solid #e2e8f0;
    }
    .photos-wrap { padding: 10px 12px; }
    .photo-img-landscape { max-width: 148px; max-height: 100px; border: 1px solid #cbd5e1; margin: 14px 8px 6px 0; vertical-align: top; }
    .photo-img-portrait  { max-width: 72px;  max-height: 110px; border: 1px solid #cbd5e1; margin: 14px 8px 6px 0; vertical-align: top; }

    /* ── Synthèse ─────────────────────────────────────────────── */
    .synthese-row-oui { background: #f0fdf4; }
    .synthese-row-non { background: #fef2f2; }
    .synthese-badge-oui { color: #ffffff; background: #059669; font-weight: bold; font-size: 9px; padding: 2px 8px; }
    .synthese-badge-non { color: #ffffff; background: #dc2626; font-weight: bold; font-size: 9px; padding: 2px 8px; }
    .synthese-obs {
        margin-top: 8px;
        padding: 8px 10px;
        background: #f8fafc;
        border-left: 3px solid {{ config('app.pdf_color', '#33CCFF') }};
        font-size: 10px;
        font-style: italic;
        color: #475569;
    }
    .synthese-obs-title { font-weight: bold; font-style: normal; color: #374151; margin-bottom: 3px; }

    /* ── Signature ────────────────────────────────────────────── */
    .signature-wrap { margin: 0 24px 20px; page-break-inside: avoid; }
    .signature-header { background: {{ config('app.pdf_color', '#33CCFF') }}; }
    .signature-header-td { padding: 6px 12px; color: #1e293b; font-size: 10px; font-weight: bold; letter-spacing: 0.3px; }
    .signature-body { border: 1px solid #e2e8f0; border-top: none; padding: 14px; }
    .signature-meta { font-size: 9px; color: #64748b; margin-bottom: 10px; }
    .signature-meta strong { color: #1e293b; }
    .signature-img { max-width: 260px; max-height: 110px; border: 1px solid #e2e8f0; background: #f8fafc; padding: 4px; }
    .signature-empty { color: #94a3b8; font-style: italic; font-size: 10px; }

    /* ── Footer fixe ──────────────────────────────────────────── */
    .footer {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        border-top: 2px solid {{ config('app.pdf_color', '#33CCFF') }};
        background: #ffffff;
        padding: 4px 24px;
        font-size: 8px;
        color: #1e293b;
    }
    .footer table { width: 100%; }
    .footer-left   { text-align: left; }
    .footer-center { text-align: center; color: #64748b; }
    .footer-right  { text-align: right; }

    .page-break { page-break-before: always; }
</style>
</head>
<body>

@php
    $appName = config('app.name', 'État des lieux');
    $data = $edl->survey_data ?? [];

    $logoSrc = '';
    $appLogoUrl = config('app.logo', '');
    if (!empty($appLogoUrl) && str_starts_with($appLogoUrl, 'https://')) {
        // Récupération depuis URL HTTPS
        $ctx = stream_context_create(['http' => [
            'timeout' => 5,
            'header' => "User-Agent: Mozilla/5.0 (compatible; EDL-PDF-Bot/1.0)\r\n",
            'ignore_errors' => true,
        ]]);
        $logoData = @file_get_contents($appLogoUrl, false, $ctx);
        $statusLine = $http_response_header[0] ?? '';
        if ($logoData !== false && $statusLine && !str_contains($statusLine, ' 200 ')) {
            $logoData = false;
        }
        if ($logoData !== false) {
            $ext = strtolower(pathinfo(parse_url($appLogoUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
            $mimeMap = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'svg' => 'image/svg+xml', 'webp' => 'image/webp'];
            $logoMime = $mimeMap[$ext] ?? 'image/png';
            $logoSrc = 'data:' . $logoMime . ';base64,' . base64_encode($logoData);
        }
    } else {
        // Fallback : fichier local
        $logoPath = public_path('images/logo.png');
        if (file_exists($logoPath)) {
            $logoMime = mime_content_type($logoPath) ?: 'image/png';
            $logoSrc = 'data:' . $logoMime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    }

    $etatLabel = function(?string $val): string {
        return match($val) {
            'bon'     => 'Bon état',
            'usure'   => 'Usure normale',
            'mauvais' => 'Mauvais état',
            default   => 'Non renseigné',
        };
    };

    $etatClass = function(?string $val): string {
        return match($val) {
            'bon'     => 'etat-bon',
            'usure'   => 'etat-usure',
            'mauvais' => 'etat-mauvais',
            default   => 'etat-nr',
        };
    };

    $roomsConfig = [
        'entree'   => ['label' => 'Entrée',        'items' => ['Sol','Murs','Porte palière','Interphone','Chauffage','Fenêtre','Luminaires','Prises électrique']],
        'couloir'  => ['label' => 'Couloir',        'items' => ['Sol','Murs','Plafond','Porte','Chauffage','Fenêtre','Luminaires','Prises électrique']],
        'cuisine'  => ['label' => 'Cuisine',        'items' => ['Plafond','Sol','Murs','Aération','Évier','Robinetterie','Chaudière','Fenêtre','Luminaires','Prises électrique']],
        'sejour'   => ['label' => 'Séjour',         'items' => ['Sol','Murs','Plafond','Chauffage','Fenêtre','Volet/Store','Meubles','Luminaires','Prises électrique']],
        'wc'       => ['label' => 'WC',             'items' => ['Cuvette WC','Abattant','Chasse d\'eau','Lavabo','Ouverture','Luminaires','Prises électrique']],
        'sdb'      => ['label' => 'Salle de bains', 'items' => [
            'Plafond','Murs','Sol','Porte','Chauffage','Fenêtre','VMC',
            'Colonne lavabo','Robinet/mélangeur Lavabo',
            'Douche/baignoire','Robinet/mélangeur douche',
            'Inverseur douche/baignoire','Flexible et douchette douche',
            'Joint lavabo','Joint douche/baignoire',
            'Luminaires','Prises électrique',
        ]],
        'chambre1' => ['label' => 'Chambre 1',      'items' => ['Sol','Murs','Plafond','Porte','Chauffage','Fenêtre','Volets/Stores','Luminaires','Prises électrique']],
        'chambre2' => ['label' => 'Chambre 2',      'items' => ['Sol','Murs','Plafond','Porte','Chauffage','Fenêtre','Volets/Stores','Luminaires','Prises électrique']],
        'chambre3' => ['label' => 'Chambre 3',      'items' => ['Sol','Murs','Plafond','Porte','Chauffage','Fenêtre','Volets/Stores','Luminaires','Prises électrique']],
    ];

    $inventaireConfig = [
        'vaisselle' => [
            'label' => 'Vaisselle',
            'items' => ['Passoire','Saladier','Plat four','Assiette plate','Assiette creuse','Bol','Tasse','Verre',
                'Fourchette','Couteau','Cuillère à soupe','Cuillère à café','Couvercle','Poêle','Econome',
                'Couteau service','Ouvre-boîte','Spatule en bois','Casserole','Dessous de plat',
                'Range couvert','Balai','Carafe','Égouttoir vaisselle'],
            'groupKey' => 'vaisselle', 'withDimension' => false,
        ],
        'petit_materiel' => [
            'label' => 'Petit matériel',
            'items' => ['Balayette + pelle','Seau','Aspirateur','Cafetière','Balai brosse',
                'Poubelle','Poubelle salle de bain','Balayette WC','Table à repasser'],
            'groupKey' => 'petit_materiel', 'withDimension' => false,
        ],
        'literie' => [
            'label' => 'Literie & Lingerie',
            'items' => ['Sommier 90x190','Sommier 140x190','Matelas','Couverture','Drap housse','Drap dessus',
                'Alèze','Housse couette','Couette','Oreillers','Taie d\'oreillers','Dessus de lit',
                'Rideaux + Tringles','Torchon vaisselle','Nappe'],
            'groupKey' => 'literie', 'withDimension' => true,
        ],
        'mobilier' => [
            'label' => 'Mobilier',
            'items' => ['Cuisinière','Machine à laver','Bureau','Armoire','Téléphone','Chevet',
                'Meuble rangement vaisselle','Table de cuisine','Chaises','Canapé',
                'Table de salon','Meuble de TV','Commode','Chaise de bureau'],
            'groupKey' => 'mobilier', 'withDimension' => false,
        ],
        'menager' => [
            'label' => 'Matériel ménager',
            'items' => ['Fer à repasser','TV','Télécommande TV'],
            'groupKey' => 'menager', 'withDimension' => false,
        ],
        'electro' => [
            'label' => 'Électroménager',
            'items' => ['Réfrigérateur / Congélateur','Micro-ondes'],
            'groupKey' => 'electro', 'withDimension' => false,
        ],
        'luminaire' => [
            'label' => 'Luminaires',
            'items' => ['Lampe de chevet (Salon)','Luminaire (Salon)','Lampe de salon','Luminaire (Chambre)','Autre luminaire'],
            'groupKey' => 'luminaire', 'withDimension' => false,
        ],
    ];

    $compteurs = [
        'compteur_eau'         => 'Eau (m³)',
        'compteur_gaz'         => 'Gaz (m³)',
        'compteur_electricite' => 'Électricité (kWh)',
    ];

    $cles = [
        'cles_porte_allee'  => 'Porte allée',
        'cles_porte_appart' => 'Porte appartement',
        'cles_verrou_haut'  => 'Verrou haut',
        'cles_verrou_bas'   => 'Verrou bas',
        'cles_local_commun' => 'Local commun',
        'cles_bal'          => 'Boîte aux lettres',
        'cles_total'        => 'Total remis',
    ];
@endphp

{{-- FOOTER (déclaré avant le contenu pour DomPDF) --}}
<div class="footer">
    <table><tr>
        <td class="footer-left">{{ $edl->type === 'entrant' ? 'EDL Entrant' : 'EDL Sortant' }} &mdash; {{ $edl->adresse }}, {{ $edl->ville }}</td>
        <td class="footer-right"></td>
    </tr></table>
</div>

{{-- EN-TÊTE --}}
<table class="header-wrap" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td class="header-logo-td" width="110">
            @if($logoSrc)
            <div class="header-logo-inner">
                <img src="{{ $logoSrc }}" alt="Logo" style="max-height:40px;max-width:90px;display:block;">
            </div>
            @endif
        </td>
        <td class="header-info-td">
            <div class="header-type">{{ $edl->type === 'entrant' ? 'État des lieux entrant' : 'État des lieux sortant' }}</div>
            <div class="header-address">{{ $edl->adresse }}, {{ $edl->ville }}</div>
        </td>
    </tr>
    <tr>
        <td class="header-info-band-td" colspan="2">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width:22%;padding-right:12px;vertical-align:top;">
                        <div class="hib-label">Locataire</div>
                        <div class="hib-value">{{ $edl->locataire_full_name ?: '—' }}</div>
                    </td>
                    <td style="width:26%;padding-right:12px;vertical-align:top;">
                        <div class="hib-label">Email</div>
                        <div class="hib-value">{{ $edl->locataire_email ?: '—' }}</div>
                    </td>
                    <td style="width:17%;padding-right:12px;vertical-align:top;">
                        <div class="hib-label">Date EDL</div>
                        <div class="hib-value">
                            @if($edl->date_edl)
                                {{ $edl->date_edl->format('d/m/Y') }}
                            @elseif($edl->created_at)
                                {{ $edl->created_at->format('d/m/Y') }}
                            @else
                                {{ date('d/m/Y') }}
                            @endif
                        </div>
                    </td>
                    <td style="width:18%;padding-right:12px;vertical-align:top;">
                        <div class="hib-label">Réalisé par</div>
                        <div class="hib-value">{{ $edl->agent_name }}</div>
                    </td>
                    <td style="width:17%;vertical-align:top;">
                        <div class="hib-label">Généré le</div>
                        <div class="hib-value">{{ date('d/m/Y H:i') }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>



{{-- COMPTEURS --}}
@php $compteursRemplis = collect($compteurs)->filter(fn($lbl, $k) => !empty($data[$k])); @endphp
@if($compteursRemplis->isNotEmpty())
<div class="section">
    <table class="section-header" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td class="section-header-td">Relevés de compteurs</td>
            <td class="section-header-count-td">{{ $compteursRemplis->count() }} {{ $compteursRemplis->count() > 1 ? 'relevés' : 'relevé' }}</td>
        </tr>
    </table>
    <div class="section-body">
        <table class="data-table">
            <thead><tr>
                <th class="col-label">Compteur</th>
                <th>Valeur relevée</th>
            </tr></thead>
            <tbody>
                @foreach($compteursRemplis as $k => $lbl)
                <tr class="{{ $loop->even ? 'row-alt' : '' }}">
                    <td>{{ $lbl }}</td>
                    <td><strong>{{ $data[$k] }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- CLÉS --}}
@php $clesRemplies = collect($cles)->filter(fn($lbl, $k) => !empty($data[$k])); @endphp
@if($clesRemplies->isNotEmpty())
<div class="section">
    <table class="section-header" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td class="section-header-td">Clés remises</td>
            <td class="section-header-count-td">{{ $clesRemplies->count() }} {{ $clesRemplies->count() > 1 ? 'types' : 'type' }}</td>
        </tr>
    </table>
    <div class="section-body">
        <table class="data-table">
            <thead><tr>
                <th class="col-label">Type de clé</th>
                <th class="col-nb">Nombre</th>
            </tr></thead>
            <tbody>
                @foreach($clesRemplies as $k => $lbl)
                <tr class="{{ $loop->even ? 'row-alt' : '' }}">
                    <td>{{ $lbl }}</td>
                    <td style="text-align:center;font-weight:bold;">{{ $data[$k] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- PHOTOS COMPTEURS & CLÉS --}}
@php $compteursPhotos = $edl->photos->where('room', 'compteurs'); @endphp
@if($compteursPhotos->isNotEmpty())
<div class="section">
    <table class="section-header" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td class="section-header-td">Photos — Compteurs &amp; Clés</td>
            <td class="section-header-count-td">{{ $compteursPhotos->count() }} photo(s)</td>
        </tr>
    </table>
    <div class="section-body">
        <div class="photos-wrap">
            @foreach($compteursPhotos as $photo)
            @php
                $photoPath   = \Illuminate\Support\Facades\Storage::disk('local')->path($photo->photo_path);
                $photoExists = file_exists($photoPath);
                $src         = '';
                $photoClass  = 'photo-img-landscape';

                if ($photoExists) {
                    $mime    = mime_content_type($photoPath) ?: 'image/jpeg';
                    $imgInfo = @getimagesize($photoPath);
                    $imgW    = $imgInfo ? $imgInfo[0] : 0;
                    $imgH    = $imgInfo ? $imgInfo[1] : 0;

                    $rotation = 0;
                    if (function_exists('exif_read_data') && in_array($mime, ['image/jpeg', 'image/tiff'])) {
                        $exif        = @exif_read_data($photoPath);
                        $orientation = isset($exif['Orientation']) ? (int)$exif['Orientation'] : 1;
                        $rotation    = match($orientation) {
                            3 => 180, 6 => -90, 8 => 90, default => 0,
                        };
                    }

                    if ($rotation !== 0 && function_exists('imagecreatefromjpeg')) {
                        $res = match($mime) {
                            'image/jpeg' => @imagecreatefromjpeg($photoPath),
                            'image/png'  => @imagecreatefrompng($photoPath),
                            'image/gif'  => @imagecreatefromgif($photoPath),
                            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($photoPath) : false,
                            default      => false,
                        };
                        if ($res) {
                            $rotated = imagerotate($res, $rotation, 0);
                            imagedestroy($res);
                            ob_start();
                            imagejpeg($rotated, null, 85);
                            $imgData = ob_get_clean();
                            imagedestroy($rotated);
                            $mime = 'image/jpeg';
                            $b64  = base64_encode($imgData);
                            if (abs($rotation) === 90) { [$imgW, $imgH] = [$imgH, $imgW]; }
                        } else {
                            $b64 = base64_encode(file_get_contents($photoPath));
                        }
                    } else {
                        $b64 = base64_encode(file_get_contents($photoPath));
                    }

                    $src        = "data:{$mime};base64,{$b64}";
                    $photoClass = ($imgW >= $imgH) ? 'photo-img-landscape' : 'photo-img-portrait';
                }
            @endphp
            @if($photoExists)
            <img src="{{ $src }}" class="{{ $photoClass }}" alt="Photo Compteurs & Clés">
            @endif
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- PIÈCES --}}
@foreach($roomsConfig as $pieceKey => $roomCfg)
@php
    $roomPhotos = $edl->photos->where('room', $pieceKey);
    $roomItemsFilled = collect($roomCfg['items'])->filter(function($item) use ($pieceKey, $data) {
        $itemKey = $pieceKey . '_' . \Str::slug($item, '_');
        return !empty($data["{$itemKey}_etat"]) || !empty($data["{$itemKey}_obs"]);
    });
    $roomHasData = $roomItemsFilled->isNotEmpty() || $roomPhotos->isNotEmpty();
@endphp
@if($roomHasData)
<div class="section">
    <table class="section-header" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td class="section-header-td">{{ $roomCfg['label'] }}</td>
            <td class="section-header-count-td">{{ $roomItemsFilled->count() }} {{ $roomItemsFilled->count() > 1 ? 'éléments' : 'élément' }}</td>
        </tr>
    </table>
    <div class="section-body">
        @if($roomItemsFilled->isNotEmpty())
        <table class="data-table">
            <thead><tr>
                <th class="col-label">Élément</th>
                <th class="col-etat">État</th>
                <th>Observations</th>
            </tr></thead>
            <tbody>
                @foreach($roomItemsFilled as $item)
                @php
                    $itemKey = $pieceKey . '_' . \Str::slug($item, '_');
                    $etatVal = $data["{$itemKey}_etat"] ?? null;
                    $obsVal  = $data["{$itemKey}_obs"]  ?? null;
                @endphp
                <tr class="{{ $loop->even ? 'row-alt' : '' }}">
                    <td>{{ $item }}</td>
                    <td class="{{ $etatClass($etatVal) }}">{{ $etatLabel($etatVal) }}</td>
                    <td class="col-obs">{{ $obsVal ?: '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        @if($roomPhotos->isNotEmpty())
        <div class="photos-label">Photos ({{ $roomPhotos->count() }})</div>
        <div class="photos-wrap">
            @foreach($roomPhotos as $photo)
            @php
                $photoPath   = \Illuminate\Support\Facades\Storage::disk('local')->path($photo->photo_path);
                $photoExists = file_exists($photoPath);
                $src         = '';
                $photoClass  = 'photo-img-landscape';

                if ($photoExists) {
                    $mime    = mime_content_type($photoPath) ?: 'image/jpeg';
                    $imgInfo = @getimagesize($photoPath);
                    $imgW    = $imgInfo ? $imgInfo[0] : 0;
                    $imgH    = $imgInfo ? $imgInfo[1] : 0;

                    // ── Correction EXIF (smartphones) ──────────────────────
                    $rotation = 0;
                    if (function_exists('exif_read_data') && in_array($mime, ['image/jpeg', 'image/tiff'])) {
                        $exif        = @exif_read_data($photoPath);
                        $orientation = isset($exif['Orientation']) ? (int)$exif['Orientation'] : 1;
                        $rotation    = match($orientation) {
                            3 => 180,
                            6 => -90,   // portrait capturé CW → corriger CCW
                            8 =>  90,   // portrait capturé CCW → corriger CW
                            default => 0,
                        };
                    }

                    // ── Rotation physique via GD si nécessaire ─────────────
                    if ($rotation !== 0 && function_exists('imagecreatefromjpeg')) {
                        $res = match($mime) {
                            'image/jpeg' => @imagecreatefromjpeg($photoPath),
                            'image/png'  => @imagecreatefrompng($photoPath),
                            'image/gif'  => @imagecreatefromgif($photoPath),
                            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($photoPath) : false,
                            default      => false,
                        };

                        if ($res) {
                            $rotated = imagerotate($res, $rotation, 0);
                            imagedestroy($res);
                            ob_start();
                            imagejpeg($rotated, null, 85);
                            $imgData = ob_get_clean();
                            imagedestroy($rotated);
                            $mime = 'image/jpeg';
                            $b64  = base64_encode($imgData);
                            // Les dimensions s'inversent après ±90°
                            if (abs($rotation) === 90) { [$imgW, $imgH] = [$imgH, $imgW]; }
                        } else {
                            $b64 = base64_encode(file_get_contents($photoPath));
                        }
                    } else {
                        $b64 = base64_encode(file_get_contents($photoPath));
                    }

                    $src        = "data:{$mime};base64,{$b64}";
                    $photoClass = ($imgW >= $imgH) ? 'photo-img-landscape' : 'photo-img-portrait';
                }
            @endphp
            @if($photoExists)
            <img src="{{ $src }}" class="{{ $photoClass }}" alt="Photo {{ $roomCfg['label'] }}">
            @endif
            @endforeach
        </div>
        @endif
    </div>
</div>
@endif
@endforeach

{{-- INVENTAIRE --}}
@foreach($inventaireConfig as $secKey => $secCfg)
@php
    $invItemsFilled = collect($secCfg['items'])->filter(function($item) use ($secCfg, $data) {
        $itemKey = $secCfg['groupKey'] . '_' . \Str::slug($item, '_');
        return ($data["{$itemKey}_nb"] ?? null) !== null && ($data["{$itemKey}_nb"] ?? '') !== ''
            || !empty($data["{$itemKey}_dim"])
            || !empty($data["{$itemKey}_obs"]);
    });
@endphp
@if($invItemsFilled->isNotEmpty())
<div class="section">
    <table class="section-header" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td class="section-header-td">{{ $secCfg['label'] }}</td>
            <td class="section-header-count-td">{{ $invItemsFilled->count() }} {{ $invItemsFilled->count() > 1 ? 'articles' : 'article' }}</td>
        </tr>
    </table>
    <div class="section-body">
        <table class="data-table">
            <thead><tr>
                <th class="col-label">Désignation</th>
                <th class="col-nb">Nb</th>
                @if($secCfg['withDimension'])
                <th class="col-dim">Dimension</th>
                @endif
                <th>Observations</th>
            </tr></thead>
            <tbody>
                @foreach($invItemsFilled as $item)
                @php
                    $itemKey = $secCfg['groupKey'] . '_' . \Str::slug($item, '_');
                    $nb  = $data["{$itemKey}_nb"]  ?? null;
                    $dim = $data["{$itemKey}_dim"] ?? null;
                    $obs = $data["{$itemKey}_obs"] ?? null;
                @endphp
                <tr class="{{ $loop->even ? 'row-alt' : '' }}">
                    <td>{{ $item }}</td>
                    <td style="text-align:center;font-weight:bold;">{{ $nb }}</td>
                    @if($secCfg['withDimension'])
                    <td>{{ $dim ?: '—' }}</td>
                    @endif
                    <td class="col-obs">{{ $obs ?: '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endforeach

{{-- SYNTHÈSE --}}
@php
    $syntheseFields = [
        'nettoyage_entreprise' => 'Nettoyage par une entreprise spécialisée requis',
        'depot_garantie'       => 'Dépôt de garantie à restituer',
    ];
    $syntheseObs = $data['synthese_obs'] ?? null;
    $hasSynthese = collect($syntheseFields)->keys()->filter(fn($k) => !empty($data[$k]))->isNotEmpty() || $syntheseObs;
@endphp
@if($hasSynthese)
<div class="section">
    <table class="section-header" width="100%" cellpadding="0" cellspacing="0">
        <tr><td class="section-header-td">Synthèse</td></tr>
    </table>
    <div class="section-body">
        <table class="data-table">
            <tbody>
                @foreach($syntheseFields as $key => $label)
                @php $val = $data[$key] ?? null; @endphp
                @if(!empty($val))
                <tr class="{{ $val === 'oui' ? 'synthese-row-oui' : 'synthese-row-non' }}">
                    <td style="width:70%;padding:6px 10px;font-weight:bold;">{{ $label }}</td>
                    <td style="padding:6px 10px;">
                        <span class="{{ $val === 'oui' ? 'synthese-badge-oui' : 'synthese-badge-non' }}">
                            {{ $val === 'oui' ? 'OUI' : 'NON' }}
                        </span>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
        @if($syntheseObs)
        <div class="synthese-obs">
            <div class="synthese-obs-title">Observations générales</div>
            {{ $syntheseObs }}
        </div>
        @endif
    </div>
</div>
@endif

{{-- SIGNATURE --}}
<div class="signature-wrap">
    <table class="signature-header" width="100%" cellpadding="0" cellspacing="0">
        <tr><td class="signature-header-td">Signature du locataire</td></tr>
    </table>
    <div class="signature-body">
        @if($edl->signature)
        <div class="signature-meta">
            Signé le <strong>{{ $edl->updated_at->format('d/m/Y à H:i') }}</strong>
            @if($edl->locataire_full_name)
            &nbsp;&mdash;&nbsp; par <strong>{{ $edl->locataire_full_name }}</strong>
            @endif
        </div>
        <img src="{{ $edl->signature }}" class="signature-img" alt="Signature">
        @else
        <div class="signature-empty">Aucune signature enregistrée.</div>
        @endif
    </div>
</div>

{{-- Pagination DomPDF (canvas API, exécuté sur chaque page) --}}
<script type="text/php">
    if (isset($pdf)) {
        $w        = $pdf->get_width();
        $h        = $pdf->get_height();
        $font     = $fontMetrics->getFont("DejaVu Sans", "normal");
        $size     = 7;
        $color    = array(0.12, 0.18, 0.24);
        // Singulier / Pluriel
        $label    = ($PAGE_COUNT > 1) ? "Page " . $PAGE_NUM . " / " . $PAGE_COUNT : "Page 1";
        $tw       = $fontMetrics->getTextWidth($label, $font, $size);
        $margin_r = 40; // ~14mm en points
        $x        = $w - $tw - $margin_r;
        // Footer zone : bottom margin ~51pt, border 2pt, padding 4pt → baseline à ~$h - 40
        $y        = $h - 42;
        $pdf->text($x, $y, $label, $font, $size, $color);
    }
</script>

</body>
</html>
