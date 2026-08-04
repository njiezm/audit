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
 * Diagnostic CITADEL — version offerte.
 *
 *   php artisan db:seed --class=CitadelSeeder
 *
 * Ce que ce rapport contient : l'état des lieux, les constats vérifiables,
 * les risques et les arbitrages à rendre. C'est le diagnostic, et il est
 * gratuit.
 *
 * Ce qu'il ne contient volontairement pas : l'arborescence cible, le modèle
 * de données détaillé, les contraintes SQL, le découpage en lots et leur
 * chiffrage en jours-homme. Cette partie constitue le livrable de la mission
 * payante — la donner ici reviendrait à livrer le travail avant de le vendre.
 *
 * Le filigrane « DIAGNOSTIC GRATUIT » marque le PDF en conséquence.
 */
class CitadelSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::orderBy('id')->first();

        if (! $author) {
            $this->command?->error('Aucun utilisateur : lancez d\'abord `php artisan db:seed`.');

            return;
        }

        Auth::login($author);

        $client = Client::firstOrCreate(
            ['name' => 'CITADEL'],
            [
                'sector' => 'Gestion locative courte durée',
                'contact_name' => 'Mathieu Virapin',
                'notes' => "Plateforme de conciergerie. Maquette front autonome, backend à construire.\n"
                    ."Propriété intellectuelle : la maquette et l'architecture sont annoncées comme "
                    ."appartenant à Mathieu Virapin — clause de cession ou de licence à expliciter au contrat.",
            ]
        );

        if (Audit::where('client_id', $client->id)->where('title', 'like', 'Diagnostic technique%')->exists()) {
            $this->command?->warn('Le diagnostic CITADEL existe déjà.');
            Auth::logout();

            return;
        }

        $audit = app(AuditService::class)->create([
            'client_name' => $client->name,
            'title' => 'Diagnostic technique — passage du prototype à la production',
            'audit_date' => '2026-08-03',
            'follow_up_on' => '2026-09-15',
            'scoring_mode' => 'weighted',
            'watermark' => 'DIAGNOSTIC GRATUIT',
            'conclusion' => $this->conclusion(),
            'categories' => $this->categories(),
        ], $author);

        Auth::logout();

        $this->command?->info("Diagnostic CITADEL créé : {$audit->reference} (score {$audit->global_score}/5).");
        $this->command?->line('Filigrane « DIAGNOSTIC GRATUIT » actif sur le PDF.');
    }

    private function categories(): array
    {
        return [
            [
                'title' => 'Socle applicatif existant',
                'score' => 1,
                'weight' => 3,
                'observations' => "Le projet se compose d'une maquette front entièrement autonome et d'une installation "
                    ."serveur restée à l'état de sortie d'installation : un seul modèle, une seule route, aucune table "
                    ."métier, aucune persistance.\n\n"
                    ."Autrement dit, la totalité de l'application visible existe côté navigateur, et rien n'existe "
                    ."côté serveur. Le taux de couverture backend est de 0 %.",
                'recommendations' => "Ne pas confondre l'avancement visuel avec l'avancement réel : la maquette donne "
                    ."l'impression d'un produit à 80 %, le backend est à zéro. Tout chiffrage doit partir de ce constat.",
                'priority' => Priority::Critical->value,
                'owner' => 'Direction projet',
            ],
            [
                'title' => 'Persistance et fiabilité des données',
                'score' => 1,
                'weight' => 3,
                'observations' => "C'est le point central du dossier : la maquette n'est pas un front en attente de "
                    ."données, c'est un front dont les données ne sont rien d'autre que le HTML affiché.\n\n"
                    ."Plusieurs fonctions ne lisent aucun état applicatif : elles relisent le texte présent à l'écran "
                    ."pour en déduire des informations métier — détection des conflits de dates, reconstruction du "
                    ."calendrier, alimentation des portails, recalcul des indicateurs, génération des exports "
                    ."comptables.\n\n"
                    ."Conséquences directes :\n"
                    ."· Les séjours sont des paires de numéros de jour, sans mois ni année. Un séjour à cheval sur "
                    ."deux mois est impossible à représenter, et aucun historique n'est exploitable.\n"
                    ."· Les montants sont stockés sous forme de texte français puis reconvertis à la volée. Aucune "
                    ."arithmétique fiable, donc aucun calcul de reversement digne de confiance.\n"
                    ."· L'avancement des arrivées, des ménages et des actions du jour vit uniquement en mémoire du "
                    ."navigateur : tout est perdu au rafraîchissement de la page.\n"
                    ."· Les couleurs d'avatar sont tirées au hasard à chaque affichage.",
                'recommendations' => "Le sens du chantier est une inversion du flux : la base de données doit devenir "
                    ."la source de vérité, le serveur produire l'affichage, et le JavaScript piloter des données "
                    ."plutôt que du texte affiché. Le rendu visuel, lui, ne bouge pas d'un pixel.\n\n"
                    ."Aucun développement de fonctionnalité ne doit démarrer avant cette bascule : construire par "
                    ."dessus l'existant reviendrait à refaire le travail deux fois.",
                'priority' => Priority::Critical->value,
                'owner' => 'Direction projet',
            ],
            [
                'title' => 'Sécurité applicative',
                'score' => 1,
                'weight' => 3,
                'observations' => "Une faille d'injection structurelle est présente : une quarantaine d'endroits "
                    ."assemblent du HTML par concaténation de texte avant de l'injecter dans la page. Aujourd'hui les "
                    ."contenus sont figés, donc inoffensifs. Dès qu'un nom de voyageur ou un message proviendra de la "
                    ."base, la porte est ouverte.\n\n"
                    ."La plateforme est par ailleurs destinée à manipuler des données sensibles : codes d'accès aux "
                    ."logements, mots de passe wifi, coordonnées bancaires de propriétaires, données de voyageurs. "
                    ."Aucun mécanisme de protection n'est en place à ce stade.\n\n"
                    ."Les liens vers les portails publics doivent impérativement être signés et expirants : à défaut, "
                    ."un code d'accès de logement reste valable indéfiniment et finit par fuiter.\n\n"
                    ."Les polices sont chargées depuis un service externe, ce qui pose une question de conformité.",
                'recommendations' => "La correction de la faille d'injection n'est pas négociable et doit être intégrée "
                    ."au tout premier lot, pas traitée en fin de chantier.\n\n"
                    ."Prévoir au contrat : chiffrement au repos des données sensibles, journalisation des accès, "
                    ."politique de rétention, accord de traitement des données avec l'hébergeur, hébergement local "
                    ."des polices.",
                'priority' => Priority::Critical->value,
                'due_on' => '2026-09-30',
                'owner' => 'Prestataire retenu',
            ],
            [
                'title' => 'Maintenabilité du code existant',
                'score' => 2,
                'weight' => 2,
                'observations' => "L'intégralité de l'application tient dans un fichier unique de plus de 5 000 lignes "
                    ."mêlant feuille de style, structure et script. On y compte environ 150 fonctions globales et des "
                    ."gestionnaires d'événements écrits directement dans le balisage.\n\n"
                    ."En l'état, ce code n'est ni testable, ni modularisable, ni sécurisable.",
                'recommendations' => "Le découpage de ce fichier constitue le premier lot du chantier et protège tous "
                    ."les suivants. Son intérêt majeur : il se réalise à rendu constant. À la fin du lot, "
                    ."l'application affiche exactement la même chose qu'aujourd'hui, mais sur une structure propre — "
                    ."donc vérifiable visuellement, écran par écran, avant d'introduire la moindre logique métier.",
                'priority' => Priority::High->value,
                'owner' => 'Prestataire retenu',
            ],
            [
                'title' => 'Couverture fonctionnelle de la maquette',
                'score' => 4,
                'weight' => 2,
                'observations' => "C'est la force réelle du projet. La maquette couvre dix écrans et une vingtaine de "
                    ."fenêtres : vue du jour, messagerie unifiée, logements, réservations, ménages, prestataires, "
                    ."propriétaires, analytics et paramètres. Quatre portails mobiles distincts sont simulés "
                    ."(voyageur, ménage, prestataire, propriétaire).\n\n"
                    ."Le besoin métier est donc exprimé avec précision et le travail de conception est déjà fait. "
                    ."C'est un atout considérable pour la suite : il n'y aura pas à inventer le produit.\n\n"
                    ."Réserve : trois sections portent la mention « Bientôt disponible » (contrats, rapports, double "
                    ."authentification). Leur présence ou non dans le périmètre doit être tranchée avant chiffrage.",
                'recommendations' => "Capitaliser sur cette maquette comme cahier des charges de référence, et "
                    ."l'inscrire comme telle au contrat : elle fait foi sur le rendu attendu.",
                'priority' => Priority::Low->value,
                'owner' => 'Direction projet',
            ],
            [
                'title' => 'Dépendances externes et messagerie',
                'score' => 2,
                'weight' => 2,
                'observations' => "La messagerie unifiée est la promesse la plus visible de la maquette, et la plus "
                    ."difficile à tenir. La difficulté n'est pas technique : elle est contractuelle. Aucun "
                    ."développement ne peut compenser un accès qui n'est pas accordé.\n\n"
                    ."· Les deux principales plateformes de réservation n'offrent pas d'accès public à leur "
                    ."messagerie : un statut de partenaire officiel est requis, avec dossier à déposer et délai de "
                    ."plusieurs semaines à plusieurs mois, sans garantie d'acceptation.\n"
                    ."· La messagerie instantanée professionnelle demande un compte dédié et une validation préalable "
                    ."des modèles de message : faisable, avec un délai administratif.\n"
                    ."· SMS et email sont disponibles immédiatement.\n\n"
                    ."Second point, à dire clairement pour éviter toute déception à la livraison : la synchronisation "
                    ."par fichier calendrier ne transporte que les disponibilités, avec un délai de latence. Ni "
                    ."tarifs, ni messages, ni coordonnées de voyageurs.",
                'recommendations' => "Livrer en direct ce qui est immédiatement disponible, et faire passer les "
                    ."plateformes de réservation par un intermédiaire spécialisé déjà agréé auprès d'elles. La "
                    ."maquette anticipe d'ailleurs cette solution.\n\n"
                    ."Cet arbitrage doit être rendu avant tout chiffrage ferme : c'est le seul lot dont la durée ne "
                    ."dépend pas du prestataire.",
                'priority' => Priority::High->value,
                'due_on' => '2026-09-15',
                'owner' => 'Direction projet',
            ],
            [
                'title' => 'Exploitation, coûts récurrents et conformité',
                'score' => 2,
                'weight' => 2,
                'observations' => "Aucune décision d'hébergement n'est arrêtée : ni prestataire, ni budget mensuel "
                    ."d'infrastructure, ni localisation des données. La volumétrie attendue n'est pas connue "
                    ."(nombre de logements, de conciergeries, de réservations par an), alors qu'elle conditionne le "
                    ."dimensionnement.\n\n"
                    ."Des coûts récurrents existeront indépendamment du développement : hébergement et base de "
                    ."données managée, SMS à l'unité, conversations de messagerie instantanée, éventuel abonnement à "
                    ."un intermédiaire de réservation, remontée d'erreurs, sauvegardes.\n\n"
                    ."Le back-office est par ailleurs conçu pour écran large uniquement, sans adaptation mobile.",
                'recommendations' => "Provisionner ces coûts récurrents dès maintenant, séparément du budget de "
                    ."développement : c'est un poste durable qui n'apparaît dans aucun devis de prestation.\n\n"
                    ."Trancher la question de la localisation des données avant de choisir l'hébergeur.",
                'priority' => Priority::Medium->value,
                'owner' => 'Direction projet',
            ],
            [
                'title' => 'Cadrage : décisions à rendre avant engagement',
                'score' => 2,
                'weight' => 2,
                'observations' => "Une dizaine de décisions conditionnent le chiffrage définitif. La première à elle "
                    ."seule peut faire varier le périmètre de plusieurs semaines :\n\n"
                    ."1. La plateforme sert-elle une seule conciergerie, ou est-ce un service ouvert à plusieurs "
                    ."clients ? La maquette présente des formules d'abonnement, des quotas et quatre rôles, ce qui "
                    ."suggère un service multi-clients — mais il faut le confirmer, car cette réponse structure tout "
                    ."le reste.\n"
                    ."2. Le portail des équipes de ménage : web mobile, ou application native en option ?\n"
                    ."3. Les reversements aux propriétaires : calcul et export, ou virements réellement automatisés ?\n"
                    ."4. Périmètre des trois sections aujourd'hui désactivées : dedans ou dehors ?\n"
                    ."5. Gestion du temps : fuseau horaire, séjours à cheval sur deux mois, heures d'arrivée et de "
                    ."départ propres à chaque logement — à confirmer.\n"
                    ."6. Propriété intellectuelle : la maquette indique que le projet et l'architecture appartiennent "
                    ."à un tiers nommé. La clause de cession ou de licence doit être explicite au contrat.",
                'recommendations' => "Rendre ces arbitrages avant toute signature. Un chiffrage produit sans eux sera "
                    ."nécessairement révisé en cours de route, au détriment des deux parties.",
                'priority' => Priority::High->value,
                'due_on' => '2026-09-15',
                'owner' => 'Direction projet',
            ],
        ];
    }

    private function conclusion(): string
    {
        return "Le projet est en bien meilleure posture que ne le suggère la note globale. Ce qui est le plus long à "
            ."obtenir sur ce type de plateforme — la définition précise du besoin, le parcours des quatre profils "
            ."d'utilisateurs, le dessin de chaque écran — est déjà fait, et bien fait.\n\n"
            ."Ce qui manque, c'est l'intégralité du socle : il n'y a aujourd'hui aucune donnée persistée, aucune "
            ."logique métier, aucune sécurité. La maquette donne l'apparence d'un produit presque terminé alors que "
            ."tout le travail serveur reste à faire. C'est ce décalage qu'il faut avoir en tête au moment d'engager "
            ."un budget.\n\n"
            ."Trois points sont non négociables et doivent figurer au contrat : la correction de la faille "
            ."d'injection, le découpage préalable du code avant toute écriture de logique métier, et un arbitrage "
            ."explicite sur la messagerie — dont la faisabilité dépend d'accords avec des tiers, pas du prestataire.\n\n"
            ."Ce diagnostic est offert. Il vous appartient et vous pouvez le présenter à n'importe quel prestataire : "
            ."il vous donne de quoi juger les propositions que vous recevrez, et repérer celles qui sous-estiment le "
            ."chantier.\n\n"
            ."Ce qu'il ne contient pas, volontairement : l'architecture cible détaillée, le modèle de données, le "
            ."découpage du chantier en lots et leur chiffrage en jours-homme. Ces éléments constituent le travail de "
            ."conception lui-même, et font l'objet d'une mission distincte.";
    }
}
