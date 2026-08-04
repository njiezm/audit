<?php

namespace Tests\Feature;

use App\Enums\AuditStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_signer_exige_le_mot_de_passe(): void
    {
        $admin = $this->admin();
        $audit = $this->makeAudit($admin);

        $this->actingAs($admin)
            ->post("/audits/{$audit->id}/signer", ['password' => 'mauvais-mot-de-passe'])
            ->assertSessionHasErrors('password');

        $this->assertFalse($audit->fresh()->is_signed);
    }

    public function test_signer_fige_le_contenu_et_delivre_un_code(): void
    {
        $admin = $this->admin();
        $audit = $this->makeAudit($admin);

        $this->actingAs($admin)
            ->post("/audits/{$audit->id}/signer", ['password' => 'motdepasse-solide-1'])
            ->assertRedirect(route('audits.show', $audit));

        $audit->refresh();

        $this->assertTrue($audit->is_signed);
        $this->assertSame(AuditStatus::Signed, $audit->status);
        $this->assertNotNull($audit->content_hash);
        $this->assertNotNull($audit->verification_code);
        $this->assertTrue($audit->isIntact());
        $this->assertSame(1, $audit->versions()->count());
    }

    /** La régression la plus grave de la version précédente. */
    public function test_un_audit_signe_ne_peut_plus_etre_modifie(): void
    {
        $admin = $this->admin();
        $audit = $this->makeAudit($admin);
        $this->actingAs($admin)->post("/audits/{$audit->id}/signer", ['password' => 'motdepasse-solide-1']);

        $this->actingAs($admin)->put("/audits/{$audit->id}", [
            'client_name' => 'Client réécrit après signature',
            'audit_date' => now()->toDateString(),
            'scoring_mode' => 'weighted',
            'categories' => [['title' => 'Bidon', 'score' => 5, 'weight' => 1]],
        ])->assertForbidden();

        $this->assertSame('Client de test', $audit->fresh()->client_name);
    }

    public function test_un_audit_signe_ne_peut_pas_etre_supprime(): void
    {
        $admin = $this->admin();
        $audit = $this->makeAudit($admin);
        $this->actingAs($admin)->post("/audits/{$audit->id}/signer", ['password' => 'motdepasse-solide-1']);

        $this->actingAs($admin)->delete("/audits/{$audit->id}")->assertForbidden();

        $this->assertNotSoftDeleted('audits', ['id' => $audit->id]);
    }

    public function test_retirer_la_signature_rend_l_audit_modifiable(): void
    {
        $admin = $this->admin();
        $audit = $this->makeAudit($admin);
        $this->actingAs($admin)->post("/audits/{$audit->id}/signer", ['password' => 'motdepasse-solide-1']);

        $this->actingAs($admin)->post("/audits/{$audit->id}/retirer-signature")->assertRedirect();

        $audit->refresh();
        $this->assertFalse($audit->is_signed);
        $this->assertSame(AuditStatus::Draft, $audit->status);
        $this->assertNull($audit->content_hash);
        // L'instantané signé reste consultable : c'est la preuve de ce qui a été signé.
        $this->assertSame(1, $audit->versions()->count());
    }

    public function test_une_alteration_du_contenu_est_detectee(): void
    {
        $admin = $this->admin();
        $audit = $this->makeAudit($admin);
        $this->actingAs($admin)->post("/audits/{$audit->id}/signer", ['password' => 'motdepasse-solide-1']);

        $audit->refresh();
        $this->assertTrue($audit->isIntact());

        // Écriture directe en base, contournant les garde-fous applicatifs.
        $audit->categories()->first()->forceFill(['score' => 5])->saveQuietly();

        $this->assertFalse($audit->fresh()->load('categories')->isIntact());
    }

    public function test_la_page_publique_de_verification_confirme_l_integrite(): void
    {
        $admin = $this->admin();
        $audit = $this->makeAudit($admin);
        $this->actingAs($admin)->post("/audits/{$audit->id}/signer", ['password' => 'motdepasse-solide-1']);

        $code = $audit->fresh()->verification_code;

        $this->get("/verifier/{$code}")
            ->assertOk()
            ->assertSee('Document authentique et intègre')
            ->assertSee($audit->reference)
            // Le contenu de l'audit n'est jamais divulgué publiquement.
            ->assertDontSee('Constat A');
    }

    public function test_un_code_inconnu_ne_confirme_rien(): void
    {
        $this->get('/verifier/AAAA-BBBB-CCCC')
            ->assertOk()
            ->assertSee('Aucun rapport signé ne correspond');
    }
}
