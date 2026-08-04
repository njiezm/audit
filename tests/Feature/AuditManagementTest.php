<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\Client;
use App\Services\ReferenceGenerator;
use App\Support\RichText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditManagementTest extends TestCase
{
    use RefreshDatabase;

    /** L'ancien mt_rand() entrait en collision dès la centaine d'audits. */
    public function test_les_references_sont_sequentielles_et_uniques(): void
    {
        $generator = app(ReferenceGenerator::class);
        $year = (int) now()->format('Y');

        $references = collect(range(1, 50))->map(fn () => $generator->next($year));

        $this->assertSame(50, $references->unique()->count());
        $this->assertSame("AUD-{$year}-0001", $references->first());
        $this->assertSame("AUD-{$year}-0050", $references->last());
    }

    public function test_le_score_global_est_pondere(): void
    {
        $audit = $this->makeAudit($this->admin(), [
            'categories' => [
                ['title' => 'Lourde', 'score' => 1, 'weight' => 4],
                ['title' => 'Légère', 'score' => 5, 'weight' => 1],
            ],
        ]);

        // (1×4 + 5×1) / 5 = 1,8 — la moyenne simple donnerait 3.
        $this->assertSame(1.8, $audit->global_score);
    }

    public function test_le_mode_simple_ignore_les_poids(): void
    {
        $audit = $this->makeAudit($this->admin(), [
            'scoring_mode' => 'simple',
            'categories' => [
                ['title' => 'Lourde', 'score' => 1, 'weight' => 4],
                ['title' => 'Légère', 'score' => 5, 'weight' => 1],
            ],
        ]);

        $this->assertSame(3.0, $audit->global_score);
    }

    /** L'ancienne mise à jour détruisait puis recréait toutes les catégories. */
    public function test_la_mise_a_jour_conserve_l_identite_des_categories(): void
    {
        $admin = $this->admin();
        $audit = $this->makeAudit($admin);
        $originalIds = $audit->categories->pluck('id')->all();

        $this->actingAs($admin)->put("/audits/{$audit->id}", [
            'client_name' => 'Client de test',
            'audit_date' => now()->toDateString(),
            'scoring_mode' => 'weighted',
            'categories' => $audit->categories->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'score' => 5,
                'weight' => $c->weight,
            ])->all(),
        ])->assertRedirect();

        $this->assertSame($originalIds, $audit->fresh()->categories->pluck('id')->all());
    }

    public function test_les_clients_sont_dedoublonnes_sans_tenir_compte_de_la_casse(): void
    {
        $admin = $this->admin();

        $this->makeAudit($admin, ['client_name' => 'SARL Dupont']);
        $this->makeAudit($admin, ['client_name' => 'sarl dupont']);
        $this->makeAudit($admin, ['client_name' => '  SARL DUPONT  ']);

        $this->assertSame(1, Client::where('name', 'SARL Dupont')->count());
        $this->assertSame(1, Client::count());
    }

    public function test_la_suppression_est_reversible(): void
    {
        $admin = $this->admin();
        $audit = $this->makeAudit($admin);

        $this->actingAs($admin)->delete("/audits/{$audit->id}")->assertRedirect();
        $this->assertSoftDeleted('audits', ['id' => $audit->id]);

        $this->actingAs($admin)->post("/audits/{$audit->id}/restaurer")->assertRedirect();
        $this->assertNotSoftDeleted('audits', ['id' => $audit->id]);
    }

    public function test_la_duplication_reprend_les_categories(): void
    {
        $admin = $this->admin();
        $source = $this->makeAudit($admin);

        $this->actingAs($admin)->post("/audits/{$source->id}/dupliquer")->assertRedirect();

        $copy = Audit::latest('id')->first();

        $this->assertNotSame($source->id, $copy->id);
        $this->assertNotSame($source->reference, $copy->reference);
        $this->assertSame($source->categories->count(), $copy->categories->count());
        $this->assertSame($source->client_id, $copy->client_id);
    }

    public function test_la_recherche_et_les_filtres_fonctionnent(): void
    {
        $admin = $this->admin();
        $cible = $this->makeAudit($admin, ['client_name' => 'Boulangerie Martin']);
        $autre = $this->makeAudit($admin, ['client_name' => 'Garage Leblanc']);

        $this->actingAs($admin)->get('/audits?q=martin')
            ->assertSee($cible->reference)
            ->assertDontSee($autre->reference);

        $this->actingAs($admin)->get('/audits?status=signed')
            ->assertDontSee($cible->reference);
    }

    public function test_la_date_d_audit_est_bornee(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/audits', [
            'client_name' => 'Client futuriste',
            'audit_date' => '2087-01-01',
            'scoring_mode' => 'weighted',
            'categories' => [['title' => 'Test', 'score' => 3, 'weight' => 1]],
        ])->assertSessionHasErrors('audit_date');
    }

    public function test_un_audit_sans_categorie_est_refuse(): void
    {
        $this->actingAs($this->admin())->post('/audits', [
            'client_name' => 'Client',
            'audit_date' => now()->toDateString(),
            'scoring_mode' => 'weighted',
            'categories' => [],
        ])->assertSessionHasErrors('categories');
    }

    public function test_le_plan_d_action_est_trie_par_criticite(): void
    {
        $audit = $this->makeAudit($this->admin(), [
            'categories' => [
                ['title' => 'Faible', 'score' => 4, 'weight' => 1, 'recommendations' => 'A', 'priority' => 'low'],
                ['title' => 'Critique', 'score' => 1, 'weight' => 1, 'recommendations' => 'B', 'priority' => 'critical'],
                ['title' => 'Moyenne', 'score' => 3, 'weight' => 1, 'recommendations' => 'C', 'priority' => 'medium'],
            ],
        ]);

        $this->assertSame(
            ['Critique', 'Moyenne', 'Faible'],
            $audit->actionPlan()->pluck('title')->all()
        );
    }

    public function test_le_balisage_leger_est_rendu_et_le_html_reste_echappe(): void
    {
        $html = RichText::render("Un point *capital*.\n\n· premier\n· second\n\nFin `code`.");

        $this->assertStringContainsString('<strong>capital</strong>', $html);
        $this->assertStringContainsString('<li>premier</li>', $html);
        $this->assertStringContainsString('<code>code</code>', $html);

        $injection = RichText::render('<script>alert(1)</script>');
        $this->assertStringNotContainsString('<script>', $injection);
    }
}
