<?php

namespace Tests\Unit;

use App\Support\RichText;
use PHPUnit\Framework\TestCase;

class RichTextTest extends TestCase
{
    public function test_le_html_saisi_est_echappe(): void
    {
        $html = RichText::render('<script>alert(1)</script>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_gras_et_code_en_ligne(): void
    {
        $html = RichText::render('Un point *capital* et une table `reservations`.');

        $this->assertStringContainsString('<strong>capital</strong>', $html);
        $this->assertStringContainsString('<code>reservations</code>', $html);
    }

    /** Un libellé long est coupé par l'éditeur : l'emphase doit survivre. */
    public function test_le_gras_traverse_un_retour_a_la_ligne(): void
    {
        $html = RichText::render("Un jour-homme = une personne travaillant **une journée\npleine sur ce projet**, et rien d'autre.");

        $this->assertStringContainsString('<strong>une journée pleine sur ce projet</strong>', $html);
    }

    public function test_les_puces_sont_regroupees(): void
    {
        $html = RichText::render("· premier\n· second\n· troisième");

        $this->assertStringContainsString('<ul>', $html);
        $this->assertSame(3, substr_count($html, '<li>'));
    }

    /** Une puce qui déborde sur la ligne suivante reste une seule puce. */
    public function test_une_puce_absorbe_sa_ligne_de_continuation(): void
    {
        $html = RichText::render("· Les attentes de validation. Chaque décision\nlaissée en suspens arrête l'avancement.\n\nParagraphe distinct.");

        $this->assertSame(1, substr_count($html, '<li>'));
        // L'apostrophe est échappée : c'est le comportement attendu.
        $this->assertStringContainsString('laissée en suspens arrête l&#039;avancement.</li>', $html);
        $this->assertStringContainsString('<p>Paragraphe distinct.</p>', $html);
    }

    public function test_bloc_preformate(): void
    {
        $html = RichText::render("Arborescence :\n```\napp/\n  Models/\n```");

        $this->assertStringContainsString('<pre><code>app/', $html);
        // L'indentation est significative dans un bloc préformaté.
        $this->assertStringContainsString("\n  Models/", $html);
    }

    public function test_le_contenu_prefomate_n_est_pas_mis_en_forme(): void
    {
        $html = RichText::render("```\nSELECT * FROM reservations;\n```");

        $this->assertStringNotContainsString('<strong>', $html);
        $this->assertStringContainsString('SELECT * FROM reservations;', $html);
    }

    public function test_tableau_avec_en_tete(): void
    {
        $html = RichText::render("| Couche | Rôle |\n| --- | --- |\n| Blade | Gabarits |\n| PostgreSQL | Données |");

        $this->assertStringContainsString('<table class="rich-table">', $html);
        $this->assertStringContainsString('<th>Couche</th>', $html);
        $this->assertSame(2, substr_count($html, '<tr>') - 1);
        $this->assertStringContainsString('<td>PostgreSQL</td>', $html);
    }

    public function test_un_bloc_preformate_non_ferme_ne_perd_pas_son_contenu(): void
    {
        $html = RichText::render("```\nligne orpheline");

        $this->assertStringContainsString('ligne orpheline', $html);
    }

    public function test_version_texte_brut(): void
    {
        $this->assertSame(
            'Un point capital.',
            RichText::plain('Un point *capital*.')
        );
    }
}
