<?php

namespace App\Support;

/**
 * Barème documenté. Auparavant un score « 3 » ne voulait rien dire :
 * chaque niveau porte désormais un libellé et une couleur uniques,
 * partagés par l'interface, l'aperçu et le PDF.
 */
class ScoreScale
{
    public const MIN = 1;

    public const MAX = 5;

    private const LEVELS = [
        1 => ['label' => 'Critique',   'color' => '#b3001b', 'description' => 'Risque avéré, action immédiate requise.'],
        2 => ['label' => 'Insuffisant', 'color' => '#e8590c', 'description' => 'Écarts majeurs, plan de remédiation à court terme.'],
        3 => ['label' => 'Acceptable', 'color' => '#b58100', 'description' => 'Conforme au minimum, marges de progression nettes.'],
        4 => ['label' => 'Bon',        'color' => '#2f6f4f', 'description' => 'Maîtrisé, quelques optimisations possibles.'],
        5 => ['label' => 'Excellent',  'color' => '#1b5e3f', 'description' => 'État de l\'art, à maintenir.'],
    ];

    public static function all(): array
    {
        return self::LEVELS;
    }

    public static function label(int|float|null $score): string
    {
        return self::LEVELS[self::clampToLevel($score)]['label'];
    }

    public static function color(int|float|null $score): string
    {
        return self::LEVELS[self::clampToLevel($score)]['color'];
    }

    public static function description(int|float|null $score): string
    {
        return self::LEVELS[self::clampToLevel($score)]['description'];
    }

    /** Un score moyen de 3,4 relève du niveau 3 ; 3,5 bascule au niveau 4. */
    private static function clampToLevel(int|float|null $score): int
    {
        if ($score === null) {
            return self::MIN;
        }

        return (int) max(self::MIN, min(self::MAX, round($score)));
    }
}
