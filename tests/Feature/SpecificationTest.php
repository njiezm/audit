<?php

namespace Tests\Feature;

use App\Mail\AuditReportMail;
use App\Models\Audit;
use App\Models\Specification;
use App\Models\User;
use App\Services\SpecificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SpecificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeSpecification(Audit $audit, User $author, array $overrides = []): Specification
    {
        return app(SpecificationService::class)->createFor($audit, array_merge([
            'title' => 'Cahier des charges de test',
            'version' => '1.0',
            'status' => 'proposed',
            'currency' => 'EUR',
            'include_in_pdf' => true,
            'context' => 'Contexte de test.',
            'scope_in' => "· Inclus A\n· Inclus B",
            'scope_out' => '· Exclu A',
            'announced_days_min' => 30,
            'announced_days_max' => 45,
            'lots' => [
                ['code' => '0', 'name' => 'Cadrage', 'phase' => '1 — Socle', 'days_min' => 2, 'days_max' => 3, 'content' => 'Mise en place.'],
                ['code' => '1', 'name' => 'Découpage', 'phase' => '1 — Socle', 'days_min' => 10, 'days_max' => 15, 'content' => 'À rendu constant.'],
                ['code' => '2', 'name' => 'Messagerie', 'phase' => '3 — Communication', 'days_min' => 6, 'days_max' => 10, 'content' => 'Dépend de tiers.', 'is_at_risk' => true, 'risk_note' => 'Accord de partenariat requis.'],
                ['code' => 'O1', 'name' => 'Application mobile', 'phase' => 'Options', 'days_min' => 15, 'days_max' => 25, 'content' => 'Hors périmètre.', 'is_option' => true],
            ],
            'sections' => [
                ['title' => 'Hypothèses', 'body' => 'Une hypothèse.', 'page_break_before' => false],
            ],
        ], $overrides), $author);
    }

    public function test_un_audit_peut_recevoir_un_cahier_des_charges(): void
    {
        $admin = $this->admin();
        $audit = $this->makeAudit($admin);

        $specification = $this->makeSpecification($audit, $admin);

        $this->assertSame($audit->id, $specification->audit_id);
        $this->assertMatchesRegularExpression('/^CDC-\d{4}-\d{4}$/', $specification->reference);
        $this->assertSame(4, $specification->lots()->count());
        $this->assertSame(1, $specification->sections()->count());
    }

    /** Les options sont chiffrées mais exclues du total du périmètre de base. */
    public function test_les_options_sont_exclues_du_total(): void
    {
        $admin = $this->admin();
        $specification = $this->makeSpecification($this->makeAudit($admin), $admin);

        $this->assertSame(18, $specification->daysMin());   // 2 + 10 + 6
        $this->assertSame(28, $specification->daysMax());   // 3 + 15 + 10
        $this->assertSame(1, $specification->optionLots()->count());
    }

    public function test_la_marge_de_cadrage_est_calculee(): void
    {
        $admin = $this->admin();
        $specification = $this->makeSpecification($this->makeAudit($admin), $admin);

        $this->assertSame(['min' => 12, 'max' => 17], $specification->announcedMargin());
    }

    /** groupBy suit l'ordre d'apparition : le tri par phase est indispensable. */
    public function test_les_phases_sortent_dans_l_ordre(): void
    {
        $admin = $this->admin();
        $specification = $this->makeSpecification($this->makeAudit($admin), $admin);

        $this->assertSame(
            ['1 — Socle', '3 — Communication'],
            $specification->lotsByPhase()->keys()->all()
        );
    }

    public function test_le_budget_suit_le_taux_journalier(): void
    {
        $admin = $this->admin();
        $specification = $this->makeSpecification($this->makeAudit($admin), $admin, ['daily_rate' => 500]);

        $this->assertSame(['min' => 15000, 'max' => 22500], $specification->budgetRange());
    }

    public function test_le_lot_zero_garde_son_numero(): void
    {
        $admin = $this->admin();
        $specification = $this->makeSpecification($this->makeAudit($admin), $admin);

        $this->assertSame('0', $specification->lots()->where('name', 'Cadrage')->first()->codeLabel());
    }

    public function test_les_pages_du_module_repondent(): void
    {
        $admin = $this->admin();
        $audit = $this->makeAudit($admin);
        $this->makeSpecification($audit, $admin);

        $this->actingAs($admin)->get("/audits/{$audit->id}/cahier-des-charges")->assertOk();
        $this->actingAs($admin)->get("/audits/{$audit->id}/cahier-des-charges/modifier")->assertOk();
        $this->actingAs($admin)->get("/audits/{$audit->id}/cahier-des-charges/pdf")->assertOk();
    }

    public function test_la_creation_est_proposee_quand_il_n_y_en_a_pas(): void
    {
        $admin = $this->admin();
        $audit = $this->makeAudit($admin);

        $this->actingAs($admin)->get("/audits/{$audit->id}/cahier-des-charges/creer")->assertOk();
        $this->actingAs($admin)->get("/audits/{$audit->id}/cahier-des-charges")
            ->assertRedirect(route('audits.show', $audit));
    }

    /** Le cahier suit le sort de son audit : signé, il est figé lui aussi. */
    public function test_le_cahier_d_un_audit_signe_n_est_pas_modifiable(): void
    {
        $admin = $this->admin();
        $audit = $this->makeAudit($admin);
        $this->makeSpecification($audit, $admin);

        $this->actingAs($admin)->post("/audits/{$audit->id}/signer", ['password' => 'motdepasse-solide-1']);

        $this->actingAs($admin)->put("/audits/{$audit->id}/cahier-des-charges", [
            'title' => 'Tentative de réécriture',
            'version' => '2.0',
            'status' => 'accepted',
            'currency' => 'EUR',
            'lots' => [['name' => 'Bidon', 'days_min' => 1, 'days_max' => 1]],
        ])->assertForbidden();

        $this->assertSame('Cahier des charges de test', $audit->fresh()->specification->title);
    }

    public function test_le_cahier_est_accole_au_pdf_de_l_audit(): void
    {
        $admin = $this->admin();
        $audit = $this->makeAudit($admin);
        $this->makeSpecification($audit, $admin);

        $response = $this->actingAs($admin)->get("/audits/{$audit->id}/pdf/telecharger");

        $response->assertOk();
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_l_annexe_disparait_quand_l_option_est_decochee(): void
    {
        $admin = $this->admin();
        $audit = $this->makeAudit($admin);
        $specification = $this->makeSpecification($audit, $admin, ['include_in_pdf' => false]);

        $this->assertFalse($specification->include_in_pdf);
        $this->actingAs($admin)->get("/audits/{$audit->id}/pdf/telecharger")->assertOk();
    }

    // ------------------------------------------------------------------
    // Envoi par e-mail
    // ------------------------------------------------------------------

    public function test_le_cahier_peut_etre_joint_a_l_envoi(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $audit = $this->makeAudit($admin);
        $this->makeSpecification($audit, $admin);

        $this->actingAs($admin)->post("/audits/{$audit->id}/envoyer", [
            'to' => 'client@example.test',
            'subject' => 'Rapport',
            'attach_specification' => '1',
        ])->assertRedirect();

        Mail::assertSent(AuditReportMail::class, fn (AuditReportMail $mail) => $mail->attachSpecification === true);
    }

    public function test_le_cahier_n_est_pas_joint_sans_l_option(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $audit = $this->makeAudit($admin);
        $this->makeSpecification($audit, $admin);

        $this->actingAs($admin)->post("/audits/{$audit->id}/envoyer", [
            'to' => 'client@example.test',
            'subject' => 'Rapport',
        ])->assertRedirect();

        Mail::assertSent(AuditReportMail::class, fn (AuditReportMail $mail) => $mail->attachSpecification === false);
    }

    /** Sans cahier des charges, l'option cochée par erreur reste sans effet. */
    public function test_l_option_est_ignoree_en_l_absence_de_cahier(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $audit = $this->makeAudit($admin);

        $this->actingAs($admin)->post("/audits/{$audit->id}/envoyer", [
            'to' => 'client@example.test',
            'subject' => 'Rapport',
            'attach_specification' => '1',
        ])->assertRedirect();

        Mail::assertSent(AuditReportMail::class, fn (AuditReportMail $mail) => $mail->attachSpecification === false);
    }

    public function test_les_deux_pieces_jointes_sont_produites(): void
    {
        $admin = $this->admin();
        $audit = $this->makeAudit($admin);
        $this->makeSpecification($audit, $admin);

        $attachments = (new AuditReportMail($audit->fresh(), 'Test', null, true))->attachments();

        $this->assertCount(2, $attachments);
    }
}
