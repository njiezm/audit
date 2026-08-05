<?php

namespace Tests\Feature;

use App\Models\AuditTemplate;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Parcourt réellement chaque page de l'application.
 *
 * Un `view:cache` ne prouve rien : il compile les gabarits sans les exécuter.
 * Seul ce test attrape les erreurs qui n'apparaissent qu'au rendu — variable
 * absente, relation non chargée, appel sur null.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_toutes_les_pages_repondent_pour_un_administrateur(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = $this->admin();
        $audit = $this->makeAudit($admin);
        $client = $audit->client;
        $template = AuditTemplate::first();

        $pages = [
            'tableau de bord' => '/',
            'liste des audits' => '/audits',
            'création d\'audit' => '/audits/create',
            'détail d\'audit' => "/audits/{$audit->id}",
            'édition d\'audit' => "/audits/{$audit->id}/edit",
            'envoi au client' => "/audits/{$audit->id}/envoyer",
            'création de cahier des charges' => "/audits/{$audit->id}/cahier-des-charges/creer",
            'corbeille' => '/audits/corbeille',
            'liste des clients' => '/clients',
            'création de client' => '/clients/create',
            'fiche client' => "/clients/{$client->id}",
            'édition de client' => "/clients/{$client->id}/edit",
            'liste des modèles' => '/modeles',
            'création de modèle' => '/modeles/create',
            'édition de modèle' => "/modeles/{$template->id}/edit",
            'profil' => '/profil',
            'utilisateurs' => '/utilisateurs',
            'création d\'utilisateur' => '/utilisateurs/create',
            'journal d\'activité' => '/journal',
        ];

        foreach ($pages as $label => $url) {
            $response = $this->actingAs($admin)->get($url);

            $this->assertSame(
                200,
                $response->getStatusCode(),
                "La page « {$label} » ({$url}) a répondu {$response->getStatusCode()}."
            );
        }
    }

    public function test_les_pages_publiques_repondent(): void
    {
        foreach (['/login', '/mot-de-passe/oubli', '/verifier', '/verifier/CODE-INEXISTANT'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_le_pdf_se_genere(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = $this->admin();
        $audit = $this->makeAudit($admin);

        $response = $this->actingAs($admin)->get("/audits/{$audit->id}/pdf/telecharger");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_le_pdf_se_genere_avec_un_filigrane(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = $this->admin();
        $audit = $this->makeAudit($admin, ['watermark' => 'DIAGNOSTIC GRATUIT']);

        $this->actingAs($admin)->get("/audits/{$audit->id}/pdf")->assertOk();
    }

    public function test_l_export_csv_repond(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = $this->admin();
        $this->makeAudit($admin);

        $this->actingAs($admin)->get('/audits/export')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
