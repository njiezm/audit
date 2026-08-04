<?php

namespace App\Support;

/**
 * Rendu d'un balisage léger dans les champs libres d'un audit.
 *
 * Les observations et recommandations sont saisies en texte brut. Sans
 * traitement, un auteur qui écrit « *non négociable* » voyait ses
 * astérisques imprimées telles quelles dans le PDF, et ses listes à puces
 * s'écrasaient en un seul paragraphe.
 *
 * Le texte est échappé AVANT toute transformation : le HTML produit ne
 * contient donc que les balises générées ici, jamais celles saisies par
 * l'utilisateur.
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
        return self::blocks(e(trim($text)));
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
        // `code` — utile dans un audit technique (noms de fonctions, tables).
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

        $flushList = function () use (&$list, &$html) {
            if ($list === []) {
                return;
            }

            $html .= '<ul>'.implode('', array_map(fn ($item) => "<li>{$item}</li>", $list)).'</ul>';
            $list = [];
        };

        $flushParagraph = function () use (&$paragraph, &$html) {
            if ($paragraph === []) {
                return;
            }

            $html .= '<p>'.implode('<br>', $paragraph).'</p>';
            $paragraph = [];
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Puces : ·, •, - ou * en début de ligne.
            if (preg_match('/^(?:[·•]|-|\*)\s+(.*)$/u', $trimmed, $m)) {
                $flushParagraph();
                $list[] = self::inline($m[1]);

                continue;
            }

            // Puces numérotées « 1. » ou « 1) ».
            if (preg_match('/^\d+[.)]\s+(.*)$/u', $trimmed, $m)) {
                $flushParagraph();
                $list[] = self::inline($m[1]);

                continue;
            }

            $flushList();

            if ($trimmed === '') {
                $flushParagraph();

                continue;
            }

            $paragraph[] = self::inline($trimmed);
        }

        $flushList();
        $flushParagraph();

        return $html;
    }
}
