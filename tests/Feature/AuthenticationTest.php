<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_connexion_avec_des_identifiants_valides(): void
    {
        $user = $this->admin();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'motdepasse-solide-1',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_connexion_refusee_avec_un_mauvais_mot_de_passe(): void
    {
        $user = $this->admin();

        $this->post('/login', ['email' => $user->email, 'password' => 'incorrect'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_un_compte_desactive_ne_peut_pas_se_connecter(): void
    {
        $user = $this->admin(['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'motdepasse-solide-1'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /** Sans régénération, l'identifiant choisi avant login resterait valable. */
    public function test_la_session_est_regeneree_a_la_connexion(): void
    {
        $user = $this->admin();

        $this->get('/login');
        $before = session()->getId();

        $this->post('/login', ['email' => $user->email, 'password' => 'motdepasse-solide-1']);

        $this->assertNotSame($before, session()->getId());
    }

    public function test_la_deconnexion_invalide_la_session(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_le_nombre_de_tentatives_est_limite(): void
    {
        RateLimiter::clear('bruteforce@example.test|127.0.0.1');
        $user = $this->admin(['email' => 'bruteforce@example.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'faux']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'motdepasse-solide-1'])
            ->assertSessionHasErrors('email');

        // Même le bon mot de passe est refusé une fois le quota atteint.
        $this->assertGuest();
    }

    public function test_les_pages_protegees_redirigent_vers_la_connexion(): void
    {
        foreach (['/', '/audits', '/clients', '/modeles', '/profil'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_seul_un_administrateur_accede_a_l_administration(): void
    {
        $auditor = $this->auditor();

        $this->actingAs($auditor)->get('/utilisateurs')->assertForbidden();
        $this->actingAs($auditor)->get('/journal')->assertForbidden();

        Auth::logout();

        $this->actingAs($this->admin())->get('/utilisateurs')->assertOk();
    }

    public function test_un_auditeur_ne_voit_que_ses_propres_audits(): void
    {
        $mine = $this->auditor();
        $other = $this->auditor();

        $ownAudit = $this->makeAudit($mine, ['client_name' => 'Mon client']);
        $foreignAudit = $this->makeAudit($other, ['client_name' => 'Client du collègue']);

        $this->actingAs($mine)->get('/audits')
            ->assertSee($ownAudit->reference)
            ->assertDontSee($foreignAudit->reference);

        $this->actingAs($mine)->get("/audits/{$foreignAudit->id}")->assertForbidden();
    }

    public function test_un_compte_en_lecture_seule_ne_peut_rien_ecrire(): void
    {
        $viewer = $this->viewer();
        $audit = $this->makeAudit($this->admin());

        $this->actingAs($viewer)->get('/audits/create')->assertForbidden();
        $this->actingAs($viewer)->delete("/audits/{$audit->id}")->assertForbidden();
    }
}
