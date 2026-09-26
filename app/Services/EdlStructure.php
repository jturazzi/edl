<?php

namespace App\Services;

/**
 * Structure des étapes du formulaire, lue dans resources/js/data/steps.json
 * (même source que le frontend).
 */
class EdlStructure
{
    /** Étapes toujours présentes, quelles que soient les pièces choisies. */
    public const REQUIRED = ['compteurs', 'synthese'];

    /** @var array<int, array<string, mixed>>|null */
    private static ?array $steps = null;

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function steps(): array
    {
        return self::$steps ??= (json_decode((string) file_get_contents(resource_path('js/data/steps.json')), true) ?: []);
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_map(fn (array $step) => (string) $step['key'], self::steps());
    }

    /**
     * Normalise une sélection d'étapes : clés inconnues ignorées, étapes obligatoires ajoutées,
     * ordre du formulaire respecté. Renvoie null si toutes les étapes sont sélectionnées
     * (= comportement historique : tout afficher).
     *
     * @param  array<int, string>|null  $selected
     * @return list<string>|null
     */
    public static function normalize(?array $selected): ?array
    {
        if ($selected === null) {
            return null;
        }

        $wanted = array_unique([...$selected, ...self::REQUIRED]);
        $ordered = array_values(array_filter(self::keys(), fn (string $key) => in_array($key, $wanted, true)));

        return count($ordered) === count(self::keys()) ? null : $ordered;
    }
}
