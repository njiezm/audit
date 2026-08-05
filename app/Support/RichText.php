<?php

namespace App\Support;

/**
 * Rendu d'un balisage léger dans les champs libres.
 *
 * Le texte est échappé AVANT toute transformation : le HTML produit ne
 * contient donc que les balises générées ici, jamais celles saisies par
 * l'utilisateur.
 *
 * Balisage reconnu :
 *   *gras*  **gras**  `code`
 *   · - *   en début de ligne → liste à puces
 *   1. 1)   en début de ligne → liste à puces
 *   ```     bloc préformaté (arborescences, extraits SQL…)
 *   | a | b | tableau, la ligne de tirets faisant office d'en-tête
 */
class RichText
{
    public static function render(?string $text): string
    {
        if (blank($text)) {
            return '';
        }

        // La structure est détectée avant la mise en forme : sinon, une ligne
        // « * point avec *emphase* » verrait son astérisque de puce avalée
        // par le motif du gras.
        return self::blocks(e(rtrim($text)));
    }

    /** Version sans balise, pour les résumés et les exports CSV. */
    public static function plain(?string $text): string
    {
        if (blank($text)) {
            return '';
        }

        return trim(preg_replace('/\s+/u', ' ', strip_tags(self::render($text))) ?? '');
    }

    private static function inline(string $text): string
    {
        // `code` — utile dans un document technique (noms de tables, fonctions).
        $text = preg_replace('/`([^`\n]+)`/u', '<code>$1</code>', $text);

        // **gras** puis *gras*. L'italique par tiret bas n'est volontairement
        // pas géré : il mutilerait les identifiants en snake_case.
        $text = preg_replace('/\*\*([^*\n]+)\*\*/u', '<strong>$1</strong>', $text);

        return preg_replace('/\*([^*\n]+)\*/u', '<strong>$1</strong>', $text);
    }

    private static function blocks(string $text): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $html = '';
        $list = [];
        $paragraph = [];
        $table = [];
        $fence = null;

        $flushList = function () use (&$list, &$html) {
            if ($list === []) {
                return;
            }

            $html .= '<ul>'.implode('', array_map(
                fn ($item) => '<li>'.self::inline($item).'</li>',
                $list
            )).'</ul>';
            $list = [];
        };

        // La mise en forme est appliquée au paragraphe reconstitué, pas ligne
        // par ligne : un *gras* ouvert en fin de ligne et refermé sur la
        // suivante doit être reconnu comme tel.
        $flushParagraph = function () use (&$paragraph, &$html) {
            if ($paragraph === []) {
                return;
            }

            $html .= '<p>'.self::inline(implode(' ', $paragraph)).'</p>';
            $paragraph = [];
        };

        $flushTable = function () use (&$table, &$html) {
            if ($table === []) {
                return;
            }

            $html .= self::renderTable($table);
            $table = [];
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // --- Blocs préformatés ---
            if (str_starts_with($trimmed, '```')) {
                if ($fence === null) {
                    $flushParagraph();
                    $flushList();
                    $flushTable();
                    $fence = [];
                } else {
                    // Le contenu est déjà échappé et ne reçoit aucune mise en
                    // forme : une arborescence doit rester telle quelle.
                    $html .= '<pre><code>'.implode("\n", $fence).'</code></pre>';
                    $fence = null;
                }

                continue;
            }

            if ($fence !== null) {
                $fence[] = rtrim($line);

                continue;
            }

            // --- Tableaux ---
            if (str_starts_with($trimmed, '|')) {
                $flushParagraph();
                $flushList();
                $table[] = $trimmed;

                continue;
            }

            $flushTable();

            // --- Puces ---
            if (preg_match('/^(?:[·•]|-|\*)\s+(.*)$/u', $trimmed, $m)) {
                $flushParagraph();
                $list[] = $m[1];

                continue;
            }

            if (preg_match('/^\d+[.)]\s+(.*)$/u', $trimmed, $m)) {
                $flushParagraph();
                $list[] = $m[1];

                continue;
            }

            if ($trimmed === '') {
                $flushList();
                $flushParagraph();

                continue;
            }

            // Ligne de continuation : rattachée à la puce précédente plutôt
            // que transformée en paragraphe. Sans cela, une puce dépassant la
            // largeur de la source se scindait en deux blocs distincts.
            if ($list !== [] && $paragraph === []) {
                $list[array_key_last($list)] .= ' '.$trimmed;

                continue;
            }

            $flushList();
            $paragraph[] = $trimmed;
        }

        // Une clôture manquante ne doit pas faire disparaître le contenu.
        if ($fence !== null && $fence !== []) {
            $html .= '<pre><code>'.implode("\n", $fence).'</code></pre>';
        }

        $flushTable();
        $flushList();
        $flushParagraph();

        return $html;
    }

    /**
     * @param  array<int, string>  $rows  lignes brutes commençant par « | »
     */
    private static function renderTable(array $rows): string
    {
        $parse = function (string $row): array {
            $cells = explode('|', trim($row, '| '));

            return array_map(fn (string $cell) => trim($cell), $cells);
        };

        $isSeparator = fn (string $row) => (bool) preg_match('/^\|[\s:|-]+$/', $row);

        $head = [];
        $body = [];

        foreach ($rows as $index => $row) {
            if ($isSeparator($row)) {
                // La ligne de tirets promeut la ligne précédente en en-tête.
                if ($index === 1 && $body !== []) {
                    $head = array_shift($body);
                }

                continue;
            }

            $body[] = $parse($row);
        }

        $columns = max(
            count($head),
            $body === [] ? 0 : max(array_map('count', $body))
        );

        if ($columns === 0) {
            return '';
        }

        $pad = fn (array $cells) => array_pad(array_slice($cells, 0, $columns), $columns, '');

        $html = '<table class="rich-table">';

        if ($head !== []) {
            $html .= '<thead><tr>';
            foreach ($pad($head) as $cell) {
                $html .= '<th>'.self::inline($cell).'</th>';
            }
            $html .= '</tr></thead>';
        }

        $html .= '<tbody>';
        foreach ($body as $row) {
            $html .= '<tr>';
            foreach ($pad($row) as $cell) {
                $html .= '<td>'.self::inline($cell).'</td>';
            }
            $html .= '</tr>';
        }

        return $html.'</tbody></table>';
    }
}
