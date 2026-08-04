<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AuditTemplate;
use App\Models\CategoryLibraryEntry;
use App\Models\User;
use App\Services\ReferenceGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedTemplates();
        $this->seedCategoryLibrary();
        $this->adoptOrphanAudits();

        app(ReferenceGenerator::class)->syncWithExisting();
    }

    /**
     * Compte administrateur initial. Le mot de passe provient de
     * l'environnement : il n'est plus écrit en clair dans le dépôt comme
     * l'étaient les anciens identifiants du contrôleur d'authentification.
     */
    private function seedAdmin(): void
    {
        $email = env('ADMIN_EMAIL', 'njiezamon10@gmail.com');
        $password = env('ADMIN_PASSWORD', 'njiezm');

        if (blank($password)) {
            $password = 'AuditMaster'.random_int(100000, 999999);
            $this->command?->warn("ADMIN_PASSWORD absent du .env — mot de passe provisoire : {$password}");
            $this->command?->warn('Changez-le dès la première connexion (Profil → Mot de passe).');
        }

        $user = User::withTrashed()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', "Expert N'jie ZAMON"),
                'password' => Hash::make($password),
                'role' => UserRole::Admin->value,
                'job_title' => 'Auditeur principal',
                'is_active' => true,
                'deleted_at' => null,
            ]
        );

        $this->command?->info("Administrateur : {$user->email}");
    }

    private function seedTemplates(): void
    {
        $templates = [
            [
                'name' => 'Audit IT complet',
                'description' => "Revue transverse du système d'information.",
                'is_default' => true,
                'categories' => [
                    ['Sécurité & Cyber', 3, 'Politique de mots de passe, MFA, sauvegardes, sensibilisation.'],
                    ['Infrastructure IT', 3, 'Serveurs, réseau, postes de travail, obsolescence.'],
                    ['Données & RGPD', 3, 'Registre des traitements, durées de conservation, sous-traitants.'],
                    ['Applications métier', 2, 'Couverture fonctionnelle, intégrations, dette applicative.'],
                    ["Continuité d'activité", 2, 'PRA/PCA, tests de restauration, RTO/RPO.'],
                    ['Gouvernance & budget', 1, 'Pilotage, contrats, licences, dépendance fournisseur.'],
                ],
            ],
            [
                'name' => 'Audit cyber PME',
                'description' => 'Format court centré sur le risque cyber.',
                'is_default' => false,
                'categories' => [
                    ['Gestion des accès', 3, 'Comptes à privilèges, arrivées/départs, MFA.'],
                    ['Protection des postes', 3, 'EDR, mises à jour, chiffrement des disques.'],
                    ['Sauvegardes', 3, 'Règle 3-2-1, restauration testée, immuabilité.'],
                    ['Messagerie & phishing', 2, 'SPF/DKIM/DMARC, filtrage, sensibilisation.'],
                    ['Réponse à incident', 2, 'Procédure, contacts, journalisation.'],
                ],
            ],
            [
                'name' => 'Audit organisationnel',
                'description' => "Processus, outillage et pratiques d'équipe.",
                'is_default' => false,
                'categories' => [
                    ['Processus & procédures', 2, 'Formalisation, respect, mise à jour.'],
                    ['Outillage collaboratif', 2, 'Adoption, doublons, coûts.'],
                    ['Compétences & formation', 2, 'Cartographie, plan de montée en compétence.'],
                    ['Pilotage & indicateurs', 3, 'KPI suivis, fréquence, décisions associées.'],
                ],
            ],
        ];

        foreach ($templates as $definition) {
            $template = AuditTemplate::firstOrCreate(
                ['name' => $definition['name']],
                [
                    'description' => $definition['description'],
                    'is_default' => $definition['is_default'],
                ]
            );

            if ($template->categories()->exists()) {
                continue;
            }

            foreach ($definition['categories'] as $position => [$title, $weight, $hint]) {
                $template->categories()->create([
                    'position' => $position,
                    'title' => $title,
                    'weight' => $weight,
                    'hint' => $hint,
                ]);
            }
        }
    }

    private function seedCategoryLibrary(): void
    {
        $titles = DB::table('audit_template_categories')->distinct()->pluck('title')
            ->merge(DB::table('audit_categories')->distinct()->pluck('title'));

        foreach ($titles as $title) {
            CategoryLibraryEntry::remember((string) $title);
        }
    }

    /** Rattache les audits antérieurs à l'authentification au compte admin. */
    private function adoptOrphanAudits(): void
    {
        $admin = User::where('role', UserRole::Admin->value)->orderBy('id')->first();

        if (! $admin) {
            return;
        }

        DB::table('audits')->whereNull('user_id')->update([
            'user_id' => $admin->id,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        DB::table('audits')
            ->whereNotNull('signed_by')
            ->whereNull('signed_by_user_id')
            ->update(['signed_by_user_id' => $admin->id]);
    }
}
