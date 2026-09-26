<?php

namespace App\Services;

use App\Models\Edl;
use Illuminate\Support\Str;

/**
 * Compare un EDL sortant à l'EDL entrant correspondant.
 *
 * La structure des étapes est lue dans resources/js/data/steps.json, la même
 * source que le formulaire : les clés de survey_data sont `{étape}_{élément}_{champ}`.
 */
class EdlComparison
{
    private const ETAT_RANK = ['bon' => 0, 'usure' => 1, 'mauvais' => 2];

    private const ETAT_LABEL = ['bon' => 'Bon état', 'usure' => 'Usure normale', 'mauvais' => 'Mauvais état'];

    private const FONCTIONNEMENT_LABEL = ['oui' => 'Fonctionne', 'non' => 'Ne fonctionne pas'];

    private const COMPTEURS = [
        'compteur_eau' => 'Eau (m³)',
        'compteur_gaz' => 'Gaz (m³)',
        'compteur_electricite' => 'Électricité (kWh)',
    ];

    private const CLES = [
        'cles_porte_allee' => 'Porte allée',
        'cles_porte_appart' => 'Porte appartement',
        'cles_verrou_haut' => 'Verrou haut',
        'cles_verrou_bas' => 'Verrou bas',
        'cles_local_commun' => 'Local commun',
        'cles_bal' => 'Boîte aux lettres',
        'cles_total' => 'Total clés remises',
    ];

    /**
     * @return array{entrant: array, sortant: array, summary: array, sections: array}
     */
    public function build(Edl $entrant, Edl $sortant): array
    {
        $before = $entrant->survey_data ?? [];
        $after = $sortant->survey_data ?? [];

        $sections = [];

        foreach (EdlStructure::steps() as $step) {
            $rows = match ($step['type']) {
                'compteurs' => $this->compteursRows($before, $after),
                'room' => $this->roomRows($step, $before, $after),
                'checklist' => $this->checklistRows($step, $before, $after),
                'inventory' => $this->inventoryRows($step['key'], $step['items'], (bool) ($step['withDimension'] ?? false), $before, $after),
                'inventory_multi' => $this->inventoryMultiRows($step, $before, $after),
                default => [],
            };

            if ($rows) {
                $sections[] = ['key' => $step['key'], 'title' => $step['title'], 'rows' => $rows];
            }
        }

        $counts = ['degrade' => 0, 'ameliore' => 0, 'modifie' => 0, 'manquant' => 0];
        foreach ($sections as $section) {
            foreach ($section['rows'] as $row) {
                if (isset($counts[$row['change']])) {
                    $counts[$row['change']]++;
                }
            }
        }

        return [
            'entrant' => $this->meta($entrant),
            'sortant' => $this->meta($sortant),
            'summary' => $counts + ['total' => array_sum($counts)],
            'sections' => $sections,
        ];
    }

    private function meta(Edl $edl): array
    {
        return [
            'id' => $edl->id,
            'numero' => $edl->numero,
            'date_edl' => optional($edl->date_edl)->toIso8601String(),
            'adresse' => $edl->adresse_complete,
            'locataire' => $edl->locataire_full_name,
        ];
    }

    private function key(string $group, string $item, string $field): string
    {
        return $group.'_'.Str::slug($item, '_').'_'.$field;
    }

    private function filled(mixed $v): bool
    {
        return $v !== null && $v !== '';
    }

    private function row(string $label, string $before, string $after, string $change, ?string $note = null, ?string $beforeTone = null, ?string $afterTone = null): array
    {
        return [
            'label' => $label,
            'before' => $before,
            'after' => $after,
            'change' => $change,
            'note' => $note,
            'before_tone' => $beforeTone,
            'after_tone' => $afterTone,
        ];
    }

    /** Pièces : état (bon < usure < mauvais) + observations. */
    private function roomRows(array $step, array $before, array $after): array
    {
        $rows = [];

        foreach ($step['elements'] as $element) {
            $b = $before[$this->key($step['key'], $element, 'etat')] ?? null;
            $a = $after[$this->key($step['key'], $element, 'etat')] ?? null;
            $bObs = trim((string) ($before[$this->key($step['key'], $element, 'obs')] ?? ''));
            $aObs = trim((string) ($after[$this->key($step['key'], $element, 'obs')] ?? ''));

            $etatChanged = $b !== $a && ($this->filled($b) || $this->filled($a));
            $obsChanged = $bObs !== $aObs;

            if (! $etatChanged && ! $obsChanged) {
                continue;
            }

            $change = 'modifie';
            if ($etatChanged && isset(self::ETAT_RANK[$b], self::ETAT_RANK[$a])) {
                $change = self::ETAT_RANK[$a] > self::ETAT_RANK[$b] ? 'degrade' : 'ameliore';
            } elseif ($etatChanged && ! $this->filled($b) && isset(self::ETAT_RANK[$a]) && self::ETAT_RANK[$a] > 0) {
                $change = 'degrade';
            }

            $note = $obsChanged ? ($aObs !== '' ? $aObs : 'Observation supprimée') : null;

            $rows[] = $this->row(
                $element,
                self::ETAT_LABEL[$b] ?? '-',
                self::ETAT_LABEL[$a] ?? '-',
                $change,
                $note,
                $b,
                $a
            );
        }

        return $rows;
    }

    /** Checklist (volets…) : fonctionne / ne fonctionne pas. */
    private function checklistRows(array $step, array $before, array $after): array
    {
        $rows = [];

        foreach ($step['items'] as $item) {
            $b = $before[$this->key($step['key'], $item, 'fonctionnement')] ?? null;
            $a = $after[$this->key($step['key'], $item, 'fonctionnement')] ?? null;
            $bObs = trim((string) ($before[$this->key($step['key'], $item, 'obs')] ?? ''));
            $aObs = trim((string) ($after[$this->key($step['key'], $item, 'obs')] ?? ''));

            if ($b === $a && $bObs === $aObs) {
                continue;
            }

            $change = 'modifie';
            if ($b !== $a) {
                $change = ($b === 'oui' && $a === 'non') ? 'degrade' : (($b === 'non' && $a === 'oui') ? 'ameliore' : 'modifie');
            }

            $rows[] = $this->row(
                $item,
                self::FONCTIONNEMENT_LABEL[$b] ?? '-',
                self::FONCTIONNEMENT_LABEL[$a] ?? '-',
                $change,
                $bObs !== $aObs ? ($aObs !== '' ? $aObs : 'Observation supprimée') : null,
                $b === 'oui' ? 'bon' : ($b === 'non' ? 'mauvais' : null),
                $a === 'oui' ? 'bon' : ($a === 'non' ? 'mauvais' : null)
            );
        }

        return $rows;
    }

    /** Inventaire : quantités (un article en moins = manquant), dimension, observations. */
    private function inventoryRows(string $group, array $items, bool $withDimension, array $before, array $after): array
    {
        $rows = [];

        foreach ($items as $item) {
            $b = $before[$this->key($group, $item, 'nb')] ?? null;
            $a = $after[$this->key($group, $item, 'nb')] ?? null;
            $bDim = $withDimension ? trim((string) ($before[$this->key($group, $item, 'dim')] ?? '')) : '';
            $aDim = $withDimension ? trim((string) ($after[$this->key($group, $item, 'dim')] ?? '')) : '';
            $bObs = trim((string) ($before[$this->key($group, $item, 'obs')] ?? ''));
            $aObs = trim((string) ($after[$this->key($group, $item, 'obs')] ?? ''));

            $nbChanged = (string) $b !== (string) $a && ($this->filled($b) || $this->filled($a));
            if (! $nbChanged && $bDim === $aDim && $bObs === $aObs) {
                continue;
            }

            $change = 'modifie';
            if ($nbChanged && is_numeric($b ?? 0) && is_numeric($a ?? 0)) {
                $change = (float) ($a ?? 0) < (float) ($b ?? 0) ? 'manquant' : 'modifie';
            }

            $notes = array_filter([
                $bDim !== $aDim ? 'Dimension : '.($aDim ?: '-') : null,
                $bObs !== $aObs ? ($aObs !== '' ? $aObs : 'Observation supprimée') : null,
            ]);

            $rows[] = $this->row(
                $item,
                $this->filled($b) ? 'Qté '.$b : '-',
                $this->filled($a) ? 'Qté '.$a : '-',
                $change,
                $notes ? implode(' · ', $notes) : null
            );
        }

        return $rows;
    }

    private function inventoryMultiRows(array $step, array $before, array $after): array
    {
        $rows = [];

        foreach ($step['sections'] as $section) {
            foreach ($this->inventoryRows($section['groupKey'], $section['items'], false, $before, $after) as $row) {
                $row['label'] = $section['title'].' - '.$row['label'];
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /** Compteurs (avec consommation) et clés restituées. */
    private function compteursRows(array $before, array $after): array
    {
        $rows = [];

        foreach (self::COMPTEURS as $key => $label) {
            $b = $before[$key] ?? null;
            $a = $after[$key] ?? null;

            if (! $this->filled($b) && ! $this->filled($a)) {
                continue;
            }

            $note = null;
            if (is_numeric($b) && is_numeric($a)) {
                $note = 'Consommation : '.rtrim(rtrim(number_format((float) $a - (float) $b, 3, ',', ' '), '0'), ',');
            }

            $rows[] = $this->row($label, $this->filled($b) ? (string) $b : '-', $this->filled($a) ? (string) $a : '-', 'releve', $note);
        }

        foreach (self::CLES as $key => $label) {
            $b = $before[$key] ?? null;
            $a = $after[$key] ?? null;

            if ((string) $b === (string) $a) {
                continue;
            }

            $manquant = is_numeric($b) && is_numeric($a) && (float) $a < (float) $b;
            $rows[] = $this->row('Clés - '.$label, $this->filled($b) ? (string) $b : '-', $this->filled($a) ? (string) $a : '-', $manquant ? 'manquant' : 'modifie');
        }

        return $rows;
    }
}
