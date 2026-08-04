<?php

namespace App\Services;

use App\Support\ScoreScale;

/**
 * Radar en SVG pur, sans aucune librairie : le même balisage est accepté
 * par le navigateur et par DomPDF, donc l'écran et le PDF montrent
 * exactement la même figure.
 */
class RadarChart
{
    /**
     * Cadre volontairement plus large que haut : les étiquettes des axes
     * horizontaux s'étendent vers l'extérieur et se faisaient rogner dans
     * un carré. La figure, elle, reste circulaire.
     */
    private const WIDTH = 460;

    private const HEIGHT = 330;

    private const RADIUS = 96;

    private const LABEL_GAP = 18;

    /** @param array<int, array{label: string, value: int|float}> $points */
    public function svg(array $points, string $stroke = '#003366', string $fill = 'rgba(0,51,102,0.18)'): string
    {
        $points = array_values(array_filter($points, fn ($p) => isset($p['value'])));
        $count = count($points);

        // En dessous de 3 axes, un radar n'a pas de sens géométrique.
        if ($count < 3) {
            return '';
        }

        $cx = self::WIDTH / 2;
        $cy = self::HEIGHT / 2;
        $radius = self::RADIUS;

        $svg = sprintf(
            '<svg viewBox="0 0 %1$d %2$d" width="%1$d" height="%2$d" class="radar-chart" role="img"'
                .' aria-label="Radar des scores par catégorie" xmlns="http://www.w3.org/2000/svg">',
            self::WIDTH,
            self::HEIGHT
        );

        // Toile de fond : un anneau par niveau du barème.
        for ($level = 1; $level <= ScoreScale::MAX; $level++) {
            $r = $radius * ($level / ScoreScale::MAX);
            $ring = [];

            for ($i = 0; $i < $count; $i++) {
                [$x, $y] = $this->vertex($cx, $cy, $r, $i, $count);
                $ring[] = round($x, 2).','.round($y, 2);
            }

            $svg .= sprintf(
                '<polygon points="%s" fill="none" stroke="#c9d4e0" stroke-width="%s" />',
                implode(' ', $ring),
                $level === ScoreScale::MAX ? '1.4' : '0.7'
            );
        }

        // Rayons + étiquettes.
        foreach ($points as $i => $point) {
            [$x, $y] = $this->vertex($cx, $cy, $radius, $i, $count);
            $svg .= sprintf(
                '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="#c9d4e0" stroke-width="0.7" />',
                $cx, $cy, round($x, 2), round($y, 2)
            );

            [$lx, $ly] = $this->vertex($cx, $cy, $radius + self::LABEL_GAP, $i, $count);
            $anchor = abs($lx - $cx) < 6 ? 'middle' : ($lx > $cx ? 'start' : 'end');

            $svg .= sprintf(
                '<text x="%s" y="%s" text-anchor="%s" font-size="9.5" fill="#4a5568">%s</text>',
                round($lx, 2),
                round($ly + 3, 2),
                $anchor,
                htmlspecialchars($this->truncate($point['label']), ENT_QUOTES, 'UTF-8')
            );
        }

        // Polygone des scores.
        $shape = [];

        foreach ($points as $i => $point) {
            $ratio = max(0, min(1, ((float) $point['value']) / ScoreScale::MAX));
            [$x, $y] = $this->vertex($cx, $cy, $radius * $ratio, $i, $count);
            $shape[] = round($x, 2).','.round($y, 2);
        }

        $svg .= sprintf(
            '<polygon points="%s" fill="%s" stroke="%s" stroke-width="2" />',
            implode(' ', $shape),
            $fill,
            $stroke
        );

        foreach ($points as $i => $point) {
            $ratio = max(0, min(1, ((float) $point['value']) / ScoreScale::MAX));
            [$x, $y] = $this->vertex($cx, $cy, $radius * $ratio, $i, $count);
            $svg .= sprintf(
                '<circle cx="%s" cy="%s" r="3" fill="%s" />',
                round($x, 2),
                round($y, 2),
                ScoreScale::color((float) $point['value'])
            );
        }

        return $svg.'</svg>';
    }

    /** Sommet i sur n, en partant du haut et en tournant dans le sens horaire. */
    private function vertex(float $cx, float $cy, float $r, int $index, int $total): array
    {
        $angle = (2 * M_PI * $index / $total) - (M_PI / 2);

        return [$cx + $r * cos($angle), $cy + $r * sin($angle)];
    }

    private function truncate(string $label, int $max = 22): string
    {
        return mb_strlen($label) > $max ? mb_substr($label, 0, $max - 1).'…' : $label;
    }
}
