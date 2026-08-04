<?php

namespace Database\Seeders;

use App\Enums\Priority;
use App\Models\Audit;
use App\Models\Client;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Jeu de démonstration, séparé du seeder principal.
 *
 *   php artisan db:seed --class=DemoSeeder     pour le charger
 *   php artisan demo:purge                     pour l'effacer
 *
 * Tous les enregistrements créés portent la marque DEMO_TAG, ce qui rend
 * la purge sûre : les données réelles ne sont jamais touchées.
 */
class DemoSeeder extends Seeder
{
    public const DEMO_TAG = '[demo]';

    public function run(): void
    {
        $author = User::orderBy('id')->first();

        if (! $author) {
            $this->command?->error('Aucun utilisateur : lancez d\'abord `php artisan db:seed`.');

            return;
        }

        Auth::login($author);

        $service = app(AuditService::class);

        foreach ($this->dataset() as $definition) {
            $client = Client::firstOrCreate(
                ['name' => $definition['client']],
                [
                    'sector' => $definition['sector'],
                    'contact_name' => $definition['contact'],
                    'contact_email' => $definition['email'],
                    'notes' => self::DEMO_TAG.' Client de démonstration.',
                ]
            );

            foreach ($definition['audits'] as $audit) {
                if (Audit::where('client_id', $client->id)->whereDate('audit_date', $audit['date'])->exists()) {
                    continue;
                }

                $created = $service->create([
                    'client_name' => $client->name,
                    'title' => $audit['title'],
                    'audit_date' => $audit['date'],
                    'follow_up_on' => $audit['follow_up'] ?? null,
                    'scoring_mode' => 'weighted',
                    'conclusion' => $audit['conclusion'],
                    'categories' => $audit['categories'],
                ], $author);

                if (! empty($audit['sign'])) {
                    $service->sign($created, $author);
                }
            }
        }

        Auth::logout();

        $this->command?->info('Jeu de démonstration chargé. Purge : php artisan demo:purge');
    }

    private function dataset(): array
    {
        $c = fn (string $t, int $s, int $w, string $o, string $r, ?string $p = null, ?string $due = null, ?string $owner = null) => [
            'title' => $t, 'score' => $s, 'weight' => $w,
            'observations' => $o, 'recommendations' => $r,
            'priority' => $p, 'due_on' => $due, 'owner' => $owner,
        ];

        return [
            [
                'client' => 'Groupe Belrive',
                'sector' => 'Industrie agroalimentaire',
                'contact' => 'Claire Aubert',
                'email' => 'c.aubert@belrive.example',
                'audits' => [
                    [
                        'title' => 'Audit IT annuel — exercice 2025',
                        'date' => '2025-03-14',
                        'conclusion' => "Le système d'information reste fonctionnel mais accuse un retard net sur la sécurité et la continuité d'activité. Deux chantiers sont prioritaires : la généralisation de l'authentification forte et la mise en place d'un plan de reprise réellement testé. Le budget IT, stable depuis trois ans, ne couvre plus le renouvellement du parc.",
                        'categories' => [
                            $c('Sécurité & Cyber', 2, 3, "Authentification à facteur unique sur l'ensemble des applications métier. Aucune campagne de sensibilisation depuis 2023. Trois comptes à privilèges partagés entre quatre personnes.", "Déployer le MFA sur la messagerie et l'ERP en priorité, puis sur le reste du parc. Nominativer les comptes à privilèges et instaurer une revue trimestrielle.", Priority::Critical->value, '2025-06-30', 'DSI'),
                            $c('Infrastructure IT', 3, 3, "Deux serveurs physiques hors garantie depuis 18 mois. Le réseau est correctement segmenté. Le parc de postes est renouvelé au fil de l'eau, sans plan pluriannuel.", "Budgéter le remplacement des deux serveurs sur l'exercice en cours. Formaliser un plan de renouvellement du parc sur 4 ans.", Priority::High->value, '2025-09-30', 'DSI'),
                            $c('Données & RGPD', 3, 3, "Registre des traitements tenu mais non revu depuis 14 mois. Les durées de conservation ne sont pas appliquées automatiquement. Contrats de sous-traitance en place.", "Réviser le registre et automatiser les purges sur les deux traitements les plus volumineux.", Priority::Medium->value, '2025-12-31', 'DPO'),
                            $c('Applications métier', 3, 2, "L'ERP couvre bien le besoin. Deux applications satellites font doublon sur la gestion des expéditions.", "Arbitrer entre les deux outils d'expédition et décommissionner le perdant.", Priority::Medium->value, null, 'Direction des opérations'),
                            $c("Continuité d'activité", 1, 2, "Aucun PRA formalisé. Les sauvegardes existent mais aucune restauration n'a été testée depuis la mise en production.", "Rédiger un PRA avec RTO et RPO cibles, puis conduire un test de restauration complet avant la fin du semestre.", Priority::Critical->value, '2025-06-15', 'DSI'),
                            $c('Gouvernance & budget', 3, 1, "Comité IT trimestriel effectif. Le budget est reconduit à l'identique sans analyse de la dette technique.", "Introduire une ligne budgétaire dédiée à la réduction de dette technique.", Priority::Low->value, null, 'Direction financière'),
                        ],
                        'sign' => true,
                    ],
                    [
                        'title' => 'Audit de suivi — semestre 1',
                        'date' => '2026-01-20',
                        'follow_up' => '2026-09-15',
                        'conclusion' => "Progression réelle sur la sécurité : le MFA est déployé sur la messagerie et l'ERP, les comptes à privilèges sont nominatifs. La continuité d'activité reste le point noir : le PRA est rédigé mais toujours pas testé. L'infrastructure a été partiellement renouvelée.",
                        'categories' => [
                            $c('Sécurité & Cyber', 4, 3, "MFA actif sur la messagerie et l'ERP. Comptes à privilèges nominatifs avec revue trimestrielle en place. Une campagne de sensibilisation a été menée en novembre.", "Étendre le MFA aux applications restantes et instaurer un test de phishing semestriel.", Priority::Medium->value, '2026-06-30', 'DSI'),
                            $c('Infrastructure IT', 4, 3, "Les deux serveurs hors garantie ont été remplacés. Le plan de renouvellement du parc est formalisé sur 4 ans.", "Maintenir le plan et intégrer les postes nomades au périmètre.", Priority::Low->value, null, 'DSI'),
                            $c('Données & RGPD', 4, 3, "Registre à jour. Purges automatiques en place sur les deux traitements ciblés.", "Étendre l'automatisation aux traitements RH.", Priority::Low->value, null, 'DPO'),
                            $c('Applications métier', 4, 2, "Le doublon sur les expéditions a été tranché, l'application redondante est décommissionnée.", "Suivre la reprise des données historiques jusqu'à clôture.", Priority::Low->value, null, 'Direction des opérations'),
                            $c("Continuité d'activité", 2, 2, "Le PRA est rédigé avec des RTO/RPO cibles. Aucun test de restauration n'a encore été conduit.", "Planifier et exécuter un test de restauration grandeur nature avant fin juin. Sans test, le PRA reste théorique.", Priority::Critical->value, '2026-06-30', 'DSI'),
                            $c('Gouvernance & budget', 4, 1, "Une ligne dette technique a été créée et dotée. Le comité IT suit désormais deux indicateurs de dette.", "Publier les indicateurs au comité de direction.", Priority::Low->value, null, 'Direction financière'),
                        ],
                        'sign' => true,
                    ],
                ],
            ],
            [
                'client' => 'Maison Vaudreuil',
                'sector' => 'Hôtellerie',
                'contact' => 'Samir Benali',
                'email' => 's.benali@vaudreuil.example',
                'audits' => [
                    [
                        'title' => 'Audit cyber — diagnostic initial',
                        'date' => '2025-11-05',
                        'follow_up' => '2026-05-05',
                        'conclusion' => "Exposition élevée pour une structure de cette taille : le système de réservation est directement accessible depuis Internet sans filtrage et les sauvegardes ne sont pas isolées. Trois actions simples réduiraient l'essentiel du risque en moins d'un trimestre.",
                        'categories' => [
                            $c('Gestion des accès', 2, 3, "Compte administrateur unique partagé par l'équipe de direction. Pas de MFA. Les départs ne déclenchent aucune révocation.", "Créer un compte nominatif par utilisateur, activer le MFA et intégrer la révocation au processus de départ.", Priority::Critical->value, '2026-02-28', 'Direction'),
                            $c('Protection des postes', 3, 2, "Antivirus intégré à jour sur l'ensemble du parc. Pas de chiffrement des disques sur les portables.", "Activer BitLocker sur les postes nomades.", Priority::High->value, '2026-03-31', 'Prestataire IT'),
                            $c('Sauvegardes', 2, 3, "Sauvegarde quotidienne locale uniquement, sur un NAS situé dans le même local que les serveurs. Pas de copie hors site.", "Appliquer la règle 3-2-1 avec une copie hors site immuable. Tester une restauration.", Priority::Critical->value, '2026-02-15', 'Prestataire IT'),
                            $c('Messagerie & phishing', 3, 2, "SPF publié, DKIM absent, DMARC absent. Aucun filtrage avancé.", "Publier DKIM et DMARC en mode surveillance, puis durcir progressivement.", Priority::High->value, '2026-04-30', 'Prestataire IT'),
                            $c('Réponse à incident', 1, 2, "Aucune procédure. Aucun contact d'astreinte identifié. Journalisation désactivée sur le serveur de réservation.", "Formaliser une procédure d'une page, identifier les contacts et activer la journalisation.", Priority::Critical->value, '2026-01-31', 'Direction'),
                        ],
                        'sign' => false,
                    ],
                ],
            ],
            [
                'client' => 'Cabinet Ardoise & Associés',
                'sector' => 'Conseil juridique',
                'contact' => 'Hélène Roux',
                'email' => 'h.roux@ardoise.example',
                'audits' => [
                    [
                        'title' => 'Audit organisationnel',
                        'date' => '2026-05-12',
                        'conclusion' => "Organisation saine et outillage cohérent. Les marges de progression portent sur la formalisation des processus et le pilotage par indicateurs, aujourd'hui informels. Aucun risque majeur relevé.",
                        'categories' => [
                            $c('Processus & procédures', 3, 2, "Les processus clés sont connus de tous mais documentés nulle part. La montée en compétence d'un nouvel arrivant repose entièrement sur le compagnonnage.", "Documenter les cinq processus les plus fréquents sous forme de fiches d'une page.", Priority::Medium->value, '2026-10-31', 'Associés'),
                            $c('Outillage collaboratif', 4, 2, "Suite collaborative unique, bien adoptée. Deux abonnements résiduels non utilisés.", "Résilier les deux abonnements dormants.", Priority::Low->value, null, 'Office manager'),
                            $c('Compétences & formation', 4, 2, "Plan de formation annuel formalisé et respecté. Cartographie des compétences tenue à jour.", "Maintenir en l'état ; envisager une revue semestrielle plutôt qu'annuelle.", Priority::Low->value, null, 'Associés'),
                            $c('Pilotage & indicateurs', 2, 3, "Aucun indicateur suivi de façon régulière. Les décisions s'appuient sur le ressenti des associés.", "Définir trois indicateurs (charge, délai, rentabilité par dossier) et les présenter mensuellement.", Priority::High->value, '2026-09-30', 'Associés'),
                        ],
                        'sign' => false,
                    ],
                ],
            ],
        ];
    }
}
