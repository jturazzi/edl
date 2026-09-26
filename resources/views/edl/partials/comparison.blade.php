{{-- Comparatif entrée / sortie (généré par App\Services\EdlComparison) --}}
@php
    $changeLabel = [
        'degrade'  => ['Dégradé',  '#b91c1c'],
        'manquant' => ['Manquant', '#b91c1c'],
        'ameliore' => ['Amélioré', '#047857'],
        'modifie'  => ['Modifié',  '#475569'],
        'releve'   => ['Relevé',   '#475569'],
    ];
    $s = $comparison['summary'];
    $entrantDate = $comparison['entrant']['date_edl'] ? \Illuminate\Support\Carbon::parse($comparison['entrant']['date_edl'])->format('d/m/Y') : null;
@endphp
<div class="section">
    <table class="section-header" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td class="section-header-td">Comparatif avec l'état des lieux d'entrée</td>
            <td class="section-header-count-td">{{ $comparison['entrant']['numero'] }}{{ $entrantDate ? ' - ' . $entrantDate : '' }}</td>
        </tr>
    </table>
    <div class="section-body">
        <div style="padding:8px 12px;font-size:9px;color:#334155;">
            @if($s['total'] === 0)
                Aucune différence constatée entre l'entrée et la sortie.
            @else
                <strong>{{ $s['degrade'] }}</strong> dégradation(s) &nbsp;·&nbsp;
                <strong>{{ $s['manquant'] }}</strong> élément(s) manquant(s) &nbsp;·&nbsp;
                <strong>{{ $s['ameliore'] }}</strong> amélioration(s) &nbsp;·&nbsp;
                <strong>{{ $s['modifie'] }}</strong> modification(s)
            @endif
        </div>
        @foreach($comparison['sections'] as $section)
        <table class="data-table">
            <thead><tr>
                <th class="col-label" colspan="5" style="text-align:left;background:#f1f5f9;">{{ $section['title'] }}</th>
            </tr>
            <tr>
                <th class="col-label">Élément</th>
                <th>Entrée</th>
                <th>Sortie</th>
                <th>Évolution</th>
                <th>Observations</th>
            </tr></thead>
            <tbody>
                @foreach($section['rows'] as $row)
                @php [$lbl, $color] = $changeLabel[$row['change']] ?? ['-', '#475569']; @endphp
                <tr class="{{ $loop->even ? 'row-alt' : '' }}">
                    <td>{{ $row['label'] }}</td>
                    <td>{{ $row['before'] }}</td>
                    <td>{{ $row['after'] }}</td>
                    <td style="font-weight:bold;color:{{ $color }};">{{ $lbl }}</td>
                    <td class="col-obs">{{ $row['note'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endforeach
    </div>
</div>
