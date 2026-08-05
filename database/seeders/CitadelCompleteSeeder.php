<?php

namespace Database\Seeders;

use App\Enums\Priority;
use App\Enums\SpecificationStatus;
use App\Models\Audit;
use App\Models\Client;
use App\Models\Specification;
use App\Models\User;
use App\Services\AuditService;
use App\Services\SpecificationService;
use Database\Seeders\Citadel\SpecificationSections;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * CITADEL — audit complet et son cahier des charges.
 *
 *   php artisan db:seed --class=CitadelCompleteSeeder
 *
 * Version payante, complémentaire du diagnostic offert produit par
 * CitadelSeeder : elle livre ce que celui-ci retenait volontairement —
 * l'architecture cible, le modèle de données, le découpage en lots et leur
 * chiffrage.
 *
 * Le seeder est additif et idempotent : il réutilise le client CITADEL s'il
 * existe, crée un audit distinct de tout autre, et s'interrompt si cet audit
 * est déjà présent. Aucune donnée existante n'est modifiée ni supprimée.
 */
class CitadelCompleteSeeder extends Seeder
{
    private const AUDIT_TITLE = 'Audit technique complet — architecture, chiffrage et plan de chantier';

    public function run(): void
    {
        $author = User::orderBy('id')->first();

        if (! $author) {
            $this->command?->error("Aucun utilisateur : lancez d'abord `php artisan db:seed`.");

            return;
        }

        Auth::login($author);

        $client = Client::firstOrCreate(
            ['name' => 'CITADEL'],
            [
                'sector' => 'Gestion locative courte durée',
                'contact_name' => 'Mathieu Virapin',
                'notes' => "Plateforme de conciergerie. Maquette front autonome, backend à construire.",
            ]
        );

        $existing = Audit::where('client_id', $client->id)
            ->where('title', self::AUDIT_TITLE)
            ->first();

        if ($existing) {
            $this->command?->warn("L'audit complet CITADEL existe déjà ({$existing->reference}). Rien n'a été modifié.");
            Auth::logout();

            return;
        }

        $audit = app(AuditService::class)->create([
            'client_name' => $client->name,
            'title' => self::AUDIT_TITLE,
            'audit_date' => '2026-08-03',
            'follow_up_on' => '2026-11-03',
            'scoring_mode' => 'weighted',
            'watermark' => null,
            'conclusion' => $this->conclusion(),
            'categories' => $this->categories(),
        ], $author);

        $specification = app(SpecificationService::class)->createFor(
            $audit,
            $this->specification(),
            $author
        );

        Auth::logout();

        $this->command?->info("Audit complet CITADEL : {$audit->reference} (score {$audit->global_score}/5).");
        $this->command?->info("Cahier des charges : {$specification->reference} — "
            ."{$specification->daysMin()} à {$specification->daysMax()} j de lots, "
            ."enveloppe annoncée {$specification->announced_days_min}–{$specification->announced_days_max} j.");
    }

    // ------------------------------------------------------------------
    // Audit
    // ------------------------------------------------------------------

    private function categories(): array
    {
        return [
            [
                'title' => 'Socle applicatif existant',
                'score' => 1,
                'weight' => 3,
                'observations' => "Maquette front autonome de 5 416 lignes en un seul fichier (332 Ko), et "
                    ."installation Laravel 13.8 restée à l'état de sortie d'installation : un modèle `User`, "
                    ."une route, trois migrations de squelette, une base SQLite locale en anglais.\n\n"
                    ."Composition du fichier de maquette :\n"
                    ."· lignes 8 à 1027 — feuille de style maison, thème sombre, environ 295 classes ;\n"
                    ."· lignes 1029 à 3433 — structure : sidebar, header, 10 écrans, ~20 modales, 4 portails ;\n"
                    ."· lignes 3434 à 5416 — ~1 980 lignes de JavaScript natif, ~150 fonctions globales.\n\n"
                    ."Vite et Tailwind figurent dans les dépendances mais ne sont pas utilisés : la maquette "
                    ."repose sur son propre CSS. Aucun appel réseau, aucune dépendance de build.\n\n"
                    ."**Taux de couverture backend : 0 %.**",
                'recommendations' => "Le chiffrage doit partir de ce constat et non de l'impression visuelle. "
                    ."L'avancement perçu est d'environ 80 % ; l'avancement réel du logiciel livrable est nul.",
                'priority' => Priority::Critical->value,
                'owner' => 'Direction projet',
            ],
            [
                'title' => 'Persistance et fiabilité des données',
                'score' => 1,
                'weight' => 3,
                'observations' => "La maquette n'est pas un front en attente de données : le document HTML "
                    ."*tient lieu* de base de données. Sept fonctions relisent le texte affiché à l'écran pour "
                    ."en déduire des informations métier :\n\n"
                    ."· `findReservationConflict()` — détecte les conflits de dates par expression régulière "
                    ."sur le texte de la cellule affichée ;\n"
                    ."· `openCalendarModal()` — reconstruit le calendrier mensuel en parcourant les lignes du "
                    ."tableau des réservations ;\n"
                    ."· `openOwnerPortal()` et `openPropertyDetail()` — alimentent les portails par le même "
                    ."parcours de tableau ;\n"
                    ."· `renderAnalytics()` — recalcule occupation, prix moyen et revenu depuis un objet "
                    ."JavaScript figé dans le code ;\n"
                    ."· `exportPayoutsCSV()` — génère l'export comptable depuis ce même objet, montants sous "
                    ."forme de texte ;\n"
                    ."· `parseEuro()` — reconvertit la chaîne « 2 340 € » en nombre.\n\n"
                    ."Conséquences directes :\n"
                    ."· **Dates sans mois ni année** — les séjours sont des paires de numéros de jour "
                    ."(« 12 → 19 »). Un séjour à cheval sur deux mois est impossible à représenter, et aucun "
                    ."historique n'est exploitable.\n"
                    ."· **Montants en texte français** reconvertis à la volée : aucune arithmétique fiable, "
                    ."donc aucun calcul de reversement digne de confiance.\n"
                    ."· **État volatil** — arrivées, ménages et actions du jour vivent en mémoire du "
                    ."navigateur ; tout est perdu au rafraîchissement.\n"
                    ."· **Couleurs d'avatar tirées au hasard** à chaque affichage.",
                'recommendations' => "Inverser le flux : la base de données devient la source de vérité, le "
                    ."serveur produit le HTML, le JavaScript pilote des données plutôt que du texte affiché. "
                    ."Le rendu visuel ne bouge pas d'un pixel.\n\n"
                    ."Cette bascule conditionne tout le reste : aucune logique métier ne doit être écrite avant.",
                'priority' => Priority::Critical->value,
                'due_on' => '2026-09-30',
                'owner' => 'Prestataire retenu',
            ],
            [
                'title' => 'Sécurité applicative et conformité',
                'score' => 1,
                'weight' => 3,
                'observations' => "**Faille d'injection structurelle** — une quarantaine d'endroits assemblent "
                    ."du HTML par concaténation de texte avant injection dans la page. Inoffensif tant que les "
                    ."contenus sont figés ; exploitable dès qu'un nom de voyageur ou un message provient de la base.\n\n"
                    ."Données sensibles manipulées par la plateforme : codes d'accès aux logements, mots de "
                    ."passe wifi, IBAN de propriétaires, données de voyageurs. Aucune protection en place.\n\n"
                    ."Les liens vers les quatre portails publics doivent être signés et expirants. À défaut, "
                    ."un code d'accès de logement reste valable indéfiniment et finit par fuiter.\n\n"
                    ."Les polices sont chargées depuis un CDN externe : à héberger localement pour la conformité RGPD.",
                'recommendations' => "Correction de l'injection intégrée au lot 1, non négociable.\n\n"
                    ."À inscrire au contrat : chiffrement au repos des IBAN, codes d'accès et mots de passe "
                    ."wifi ; journalisation des accès ; politique de rétention ; accord de traitement des "
                    ."données avec l'hébergeur ; hébergement local des polices ; limitation de débit sur les "
                    ."portails publics.",
                'priority' => Priority::Critical->value,
                'due_on' => '2026-09-30',
                'owner' => 'Prestataire retenu',
            ],
            [
                'title' => 'Architecture cible et découpage des vues',
                'score' => 2,
                'weight' => 3,
                'observations' => "Un fichier de 5 416 lignes ne peut pas être rendu dynamique sur place. Le "
                    ."découpage constitue le premier lot et protège tous les suivants. Son intérêt majeur : il "
                    ."se réalise **à rendu constant** — à la fin du lot, l'application affiche exactement la "
                    ."même chose, sur une structure propre, vérifiable écran par écran.\n\n"
                    ."Volumétrie cible estimée : 60 à 70 fichiers de vues et environ 25 modules JavaScript, "
                    ."répartis entre le gabarit et ses partiels, 9 écrans, une vingtaine de composants "
                    ."réutilisables, une vingtaine de modales et 4 portails publics.\n\n"
                    ."Règle de conversion : tout bloc HTML aujourd'hui fabriqué par assemblage de texte dans "
                    ."le JavaScript devient un composant rendu côté serveur — panneau d'équipe ménage, listes "
                    ."de propriétés, comparatif analytics, fiche et historique de réservation, blocs "
                    ."d'assignation, éditeur de checklist, liens d'avis, carrousel de tarification.\n\n"
                    ."**Trois options d'architecture front, et c'est le choix qui pilote le devis :**\n"
                    ."· **A — vues serveur enrichies (recommandée)** : le serveur produit le HTML, le "
                    ."JavaScript reste mince. Option la plus proche du front actuel ; les portails publics et "
                    ."la protection contre les injections viennent gratuitement. Référence de chiffrage.\n"
                    ."· **B — API JSON et JavaScript réécrit** : +8 à 12 j. Ne se justifie que si une "
                    ."application mobile native est prévue.\n"
                    ."· **C — réécriture en framework front** : +25 j. Écartée, contredit la contrainte de "
                    ."conservation du front.",
                'recommendations' => "Retenir l'option A. Si une application mobile native pour les équipes de "
                    ."ménage entre au programme, la retenir tout de même, complétée d'une API restreinte aux "
                    ."seuls portails.",
                'priority' => Priority::High->value,
                'due_on' => '2026-09-15',
                'owner' => 'Direction projet',
            ],
            [
                'title' => 'Modèle de données',
                'score' => 2,
                'weight' => 3,
                'observations' => "Environ 30 tables. La maquette révèle une plateforme multi-conciergerie "
                    ."(formules d'abonnement, quotas, rôles, facturation) — hypothèse à confirmer, car elle "
                    ."structure tout le reste.\n\n"
                    ."**Socle et sécurité** : organisations, utilisateurs, rôles et permissions, invitations, "
                    ."quotas et compteurs d'usage, journal d'activité, notifications.\n\n"
                    ."**Cœur métier** : propriétaires (IBAN chiffré, taux de commission), logements, tarifs "
                    ."par logement, saisons tarifaires, accès par logement (codes et wifi chiffrés), liens "
                    ."d'avis, modèles de checklist, voyageurs, réservations (arrivée et départ **horodatés**, "
                    ."montants **en centimes**), événements de réservation, ménages, affectations "
                    ."multi-agents, checklists d'exécution, photos, disponibilités et congés, prestataires, "
                    ."interventions, signalements.\n\n"
                    ."**Messagerie, finance, intégrations** : conversations et messages, relevés de "
                    ."reversement, charges, règles de commission, connecteurs, flux de calendrier, jetons "
                    ."d'API, liens de portails signés.\n\n"
                    ."**Ce que PostgreSQL apporte concrètement** — une contrainte d'exclusion interdit "
                    ."physiquement deux séjours qui se chevauchent sur un même logement. La détection de "
                    ."conflit cesse de dépendre d'une expression régulière côté navigateur : elle devient une "
                    ."garantie du moteur, y compris pour les réservations arrivant par synchronisation "
                    ."automatique. S'ajoutent le stockage documentaire natif pour les données brutes de "
                    ."synchronisation, une colonne calculée pour le nombre de nuits, l'unicité des e-mails "
                    ."insensible à la casse, des agrégats matérialisés pour les analytics, la recherche plein "
                    ."texte intégrée et le chiffrement au repos.",
                'recommendations' => "Trancher la question multi-conciergerie avant toute écriture de "
                    ."migration : elle fait tomber ou non le lot de facturation et conditionne le modèle entier.",
                'priority' => Priority::Critical->value,
                'due_on' => '2026-09-15',
                'owner' => 'Direction projet',
            ],
            [
                'title' => 'Couverture fonctionnelle de la maquette',
                'score' => 4,
                'weight' => 2,
                'observations' => "C'est la force réelle du projet. Dix écrans et une vingtaine de fenêtres : "
                    ."vue du jour, messagerie unifiée, logements, réservations, ménages, prestataires, "
                    ."propriétaires, analytics, paramètres, check-in.\n\n"
                    ."Quatre portails mobiles distincts sont simulés : voyageur (code d'accès, wifi, "
                    ."confirmation d'arrivée, signalement, liens d'avis), ménage (mission, checklist, photos), "
                    ."prestataire (arrivée et départ en un clic), propriétaire (revenus, réservations, "
                    ."reversements).\n\n"
                    ."Le besoin métier est exprimé avec précision et le travail de conception est déjà fait. "
                    ."Il n'y aura pas à inventer le produit.\n\n"
                    ."Réserve : contrats, rapports et double authentification portent la mention « Bientôt "
                    ."disponible ». Leur présence dans le périmètre doit être tranchée avant chiffrage.",
                'recommendations' => "Inscrire la maquette au contrat comme cahier des charges de référence : "
                    ."elle fait foi sur le rendu attendu, écran par écran.",
                'priority' => Priority::Low->value,
                'owner' => 'Direction projet',
            ],
            [
                'title' => 'Dépendances externes et messagerie unifiée',
                'score' => 2,
                'weight' => 3,
                'observations' => "La promesse la plus visible de la maquette, et la plus difficile à tenir. "
                    ."La difficulté n'est pas technique : elle est **contractuelle**. Aucun développement ne "
                    ."compense un accès qui n'est pas accordé.\n\n"
                    ."· **Airbnb** — aucune interface publique de messagerie. Statut de partenaire officiel "
                    ."requis : dossier à déposer, validation à obtenir, délai de plusieurs semaines à "
                    ."plusieurs mois, sans garantie d'acceptation.\n"
                    ."· **Booking.com** — accord de partenariat technique requis, selon la même logique.\n"
                    ."· **WhatsApp Business** — compte professionnel Meta, numéro dédié, validation préalable "
                    ."des modèles de message. Faisable, avec un délai administratif.\n"
                    ."· **SMS** et **e-mail** — disponibles immédiatement.\n\n"
                    ."Second point, à dire clairement pour éviter toute déception à la livraison : la "
                    ."synchronisation par fichier calendrier ne transporte que les **disponibilités**, avec "
                    ."quinze à soixante minutes de latence. Ni tarifs, ni messages, ni coordonnées de "
                    ."voyageurs. Le risque de double réservation subsiste pendant la fenêtre de latence — "
                    ."c'est précisément ce que la contrainte d'exclusion vient neutraliser, en filet de sécurité.",
                'recommendations' => "Livrer SMS, WhatsApp et e-mail en direct ; faire passer Airbnb et "
                    ."Booking par un intermédiaire spécialisé déjà agréé auprès de ces plateformes. La "
                    ."maquette anticipe cette solution : un « Channel Manager » marqué *Recommandé* figure "
                    ."déjà dans l'écran des intégrations.\n\n"
                    ."Cet arbitrage doit être rendu **avant** tout chiffrage ferme du lot 8.",
                'priority' => Priority::High->value,
                'due_on' => '2026-09-15',
                'owner' => 'Direction projet',
            ],
            [
                'title' => 'Exploitation, coûts récurrents et propriété',
                'score' => 2,
                'weight' => 2,
                'observations' => "Aucune décision d'hébergement n'est arrêtée : ni prestataire, ni budget "
                    ."mensuel d'infrastructure, ni localisation des données. La volumétrie attendue n'est pas "
                    ."connue (nombre de logements, de conciergeries, de réservations par an), alors qu'elle "
                    ."conditionne le dimensionnement.\n\n"
                    ."Coûts récurrents à provisionner, indépendants du développement : hébergement et base "
                    ."managée, SMS à l'unité, conversations WhatsApp, éventuel abonnement à un intermédiaire "
                    ."de réservation, remontée d'erreurs, sauvegardes.\n\n"
                    ."Le back-office est conçu pour écran large uniquement : sidebar de largeur fixe, aucune "
                    ."adaptation mobile. Un back-office utilisable sur tablette constitue un lot supplémentaire "
                    ."qui touche le front.\n\n"
                    ."**Propriété intellectuelle** — l'écran d'accueil de la maquette indique que le projet et "
                    ."l'architecture appartiennent à Mathieu Virapin. La clause de cession ou de licence doit "
                    ."être explicite au contrat.",
                'recommendations' => "Provisionner les coûts récurrents séparément du budget de développement : "
                    ."c'est un poste durable qui n'apparaît dans aucun devis de prestation. Trancher la "
                    ."localisation des données avant de choisir l'hébergeur. Régler la question de la propriété "
                    ."intellectuelle avant la signature.",
                'priority' => Priority::Medium->value,
                'due_on' => '2026-10-15',
                'owner' => 'Direction projet',
            ],
        ];
    }

    private function conclusion(): string
    {
        return "Le projet est en bien meilleure posture que ne le suggère la note globale. Ce qui est le plus "
            ."long à obtenir sur ce type de plateforme — la définition précise du besoin, le parcours des "
            ."quatre profils d'utilisateurs, le dessin de chaque écran — est déjà fait, et bien fait.\n\n"
            ."Ce qui manque, c'est l'intégralité du socle. Le chantier est chiffré à **65 à 100 jours-homme** "
            ."hors application mobile native et hors connecteurs propriétaires, découpés en 18 lots et trois "
            ."phases dans le cahier des charges joint. La première phase suffit à mettre l'application en "
            ."production et en usage quotidien.\n\n"
            ."Trois points sont non négociables : la correction de la faille d'injection, le découpage "
            ."préalable du code avant toute écriture de logique métier, et un arbitrage explicite sur la "
            ."messagerie — dont la faisabilité dépend d'accords avec des tiers, pas du prestataire.\n\n"
            ."La décision la plus structurante reste la première du cahier des charges : plateforme "
            ."mono-conciergerie ou service ouvert à plusieurs clients. Elle fait varier le périmètre de "
            ."plusieurs semaines et doit être rendue avant signature.";
    }

    // ------------------------------------------------------------------
    // Cahier des charges
    // ------------------------------------------------------------------

    private function specification(): array
    {
        return [
            'title' => 'Cahier des charges — construction du backend CITADEL',
            'version' => '1.0',
            'status' => SpecificationStatus::Proposed->value,
            'currency' => 'EUR',
            'include_in_pdf' => true,
            'starts_on' => '2026-09-15',
            'valid_until' => '2026-11-03',

            // L'enveloppe annoncée dépasse la somme des lots : l'écart est la
            // marge de cadrage, affichée telle quelle sur le document.
            'announced_days_min' => 65,
            'announced_days_max' => 100,
            'daily_rate' => null,

            'context' => "CITADEL est une plateforme de gestion locative courte durée. Le projet se compose "
                ."aujourd'hui d'une maquette front autonome de 5 416 lignes, entièrement fonctionnelle en "
                ."apparence, et d'une installation Laravel vierge. Aucune donnée n'est persistée, aucune "
                ."logique métier n'existe.\n\n"
                ."Le présent cahier des charges décrit le chantier de construction du backend, à partir du "
                ."diagnostic technique établi le 3 août 2026.",

            'objectives' => "· Faire de la base de données la source de vérité, à la place du document HTML "
                ."affiché.\n"
                ."· Mettre l'application en production et en usage quotidien dès la première phase.\n"
                ."· Conserver le rendu visuel existant à l'identique, écran par écran.\n"
                ."· Corriger la faille d'injection avant toute mise en ligne.\n"
                ."· Livrer une plateforme conforme au RGPD sur des données sensibles (IBAN, codes d'accès, "
                ."mots de passe wifi, données de voyageurs).",

            'scope_in' => "· Découpage du front existant et extraction des ressources, à rendu constant.\n"
                ."· Modèle de données PostgreSQL complet, environ 30 tables.\n"
                ."· Authentification, organisations, quatre rôles, invitations, quotas.\n"
                ."· Logements, référentiel, tarification, accès chiffrés, liens d'avis.\n"
                ."· Réservations, calendrier, contrainte anti-conflit garantie par la base.\n"
                ."· Ménages, équipe, Kanban, affectation multi-agents, checklists et photos.\n"
                ."· Prestataires et interventions.\n"
                ."· Quatre portails publics à liens signés et expirants.\n"
                ."· Reversements, commissions, exports comptables, portail propriétaire.\n"
                ."· Analytics, notifications automatiques, recherche plein texte.\n"
                ."· Synchronisation des calendriers par fichier.\n"
                ."· Tests automatisés sur les parcours critiques, conformité RGPD, optimisation.\n"
                ."· Déploiement, sauvegardes avec restauration testée, supervision, recette et documentation.",

            'scope_out' => "· Application mobile native (proposée en option).\n"
                ."· Connecteurs propriétaires des plateformes de réservation : leur obtention dépend d'accords "
                ."avec des tiers et non du prestataire.\n"
                ."· Modification du rendu visuel du front, hors adaptation tablette proposée en option.\n"
                ."· Sections désactivées dans la maquette — contrats, rapports, double authentification — "
                ."tant que leur périmètre n'est pas tranché.\n"
                ."· Reprise des données existantes, à chiffrer une fois les sources connues.\n"
                ."· Coûts récurrents d'exploitation : hébergement, base managée, SMS à l'unité, conversations "
                ."WhatsApp, abonnement à un intermédiaire de réservation, sauvegardes.",

            'lots' => $this->lots(),
            'sections' => $this->sections(),
        ];
    }

    private function lots(): array
    {
        $p1 = '1 — Socle opérationnel';
        $p2 = '2 — Pilotage et finance';
        $p3 = '3 — Communication et commercialisation';

        $lot = fn (string $code, string $name, string $phase, int $min, int $max, string $content, bool $risk = false, ?string $note = null) => [
            'code' => $code, 'name' => $name, 'phase' => $phase,
            'days_min' => $min, 'days_max' => $max, 'content' => $content,
            'is_option' => false, 'is_at_risk' => $risk, 'risk_note' => $note,
        ];

        return [
            $lot('0', 'Cadrage et socle', $p1, 2, 3,
                "PostgreSQL et extensions, environnement, migrations de socle, intégration continue, "
                ."conventions, dépendances, stratégie multi-organisation, arbitrage de l'option d'architecture."),

            $lot('1', 'Découpage des vues et extraction des ressources', $p1, 4, 6,
                "Gabarit, sidebar, header, 9 écrans, ~20 modales, 4 portails, ~20 composants, styles intégrés "
                ."au build, JavaScript modularisé. **Livrable : rendu identique au pixel, données de "
                ."démonstration.** Prérequis strict de tous les lots suivants."),

            $lot('2', 'Authentification, organisations, rôles', $p1, 3, 4,
                "Connexion, déconnexion, réinitialisation, 4 rôles et règles d'accès, invitations par e-mail, "
                ."écran Équipe fonctionnel, application des quotas."),

            $lot('3', 'Logements et référentiel', $p1, 4, 5,
                "Gestion des logements et propriétaires, photos, éditeur de checklist, accès et wifi chiffrés, "
                ."liens d'avis, tarification et carrousel, parcours d'intégration en 4 étapes, fiche et suppression."),

            $lot('4', 'Réservations et calendrier', $p1, 5, 7,
                "Gestion complète, statuts et transitions, **contrainte anti-conflit**, filtres et pagination "
                ."serveur, calendrier, fiche, historique, corbeille et restauration, arrivées et départs, vue "
                ."du jour, fenêtres de rotation calculées."),

            $lot('5', 'Ménages et équipe', $p1, 5, 7,
                "Kanban et transitions, assignation multi-agents, disponibilités et congés, clôture avec "
                ."notation, **création automatique des ménages à partir des départs**, alerte de rotation "
                ."serrée, checklists d'exécution et photos."),

            $lot('6', 'Prestataires', $p1, 2, 3,
                "Gestion, planification, statuts, transformation des signalements en interventions."),

            $lot('7', 'Portails publics', $p1, 4, 6,
                "Quatre portails, liens signés et expirants, envoi par SMS et e-mail, actions en un clic, "
                ."envoi de photos, formulaires de signalement, conception mobile."),

            $lot('8', 'Messagerie unifiée', $p3, 6, 10,
                "Conversations et messages, boîte de réception, fil, envoi, filtres, non-lus, temps réel.",
                true,
                "Dépend d'accords avec des tiers : statut de partenaire Airbnb et Booking, validation Meta "
                ."pour WhatsApp. Délais de plusieurs semaines à plusieurs mois, sans garantie."),

            $lot('9', 'Reversements et finance', $p2, 4, 6,
                "Relevés mensuels, commissions, charges, statuts de versement, exports, PDF, portail "
                ."propriétaire, relances."),

            $lot('10', 'Analytics', $p2, 3, 4,
                "Occupation, prix moyen, revenu par logement, chiffre d'affaires, comparatif, agrégats en "
                ."tâches de fond, comparaison au mois précédent, graphiques."),

            $lot('11', 'Notifications automatiques', $p2, 3, 5,
                "Moteur de règles (confirmation, veille d'arrivée, code d'accès deux heures avant, "
                ."assignation de ménage), modèles éditables, e-mail, SMS et WhatsApp, files d'attente et "
                ."planificateur, compteur de consommation, journal d'envoi."),

            $lot('12', 'Synchronisation des calendriers', $p1, 4, 7,
                "Import et export par logement, tâches planifiées, rapprochement, dédoublonnage, gestion des "
                ."échecs et alertes, calendrier Google, synchronisation forcée."),

            $lot('13', 'Abonnements', $p3, 0, 5,
                "Facturation récurrente, trois formules, espace client, événements de paiement, application "
                ."des quotas, changement de formule. **Nul si la plateforme sert un client unique** — c'est "
                ."le premier arbitrage à rendre."),

            $lot('14', 'Recherche et notifications internes', $p2, 2, 3,
                "Recherche plein texte, panneau de notifications, journal d'activité."),

            $lot('15', 'Tests, sécurité, RGPD, performance', $p1, 4, 6,
                "Tests automatisés sur les parcours critiques, audit des règles d'accès, chiffrement, "
                ."limitation de débit, conformité RGPD, optimisation des requêtes, index, cache."),

            $lot('16', 'Déploiement et exploitation', $p1, 3, 4,
                "Serveur, base managée, sauvegardes avec **restauration testée**, supervision des tâches de "
                ."fond, remontée d'erreurs, environnement de recette, domaine et certificat, manuel d'exploitation."),

            $lot('17', 'Recette, formation, documentation', $p1, 2, 3,
                "Recette accompagnée, formation des utilisateurs, documentation fonctionnelle et technique."),

            // --- Options, hors enveloppe de base ---
            [
                'code' => 'O1',
                'name' => 'Application mobile native pour les équipes de ménage',
                'phase' => 'Options',
                'content' => "En remplacement du portail web. Implique le passage à une API JSON.",
                'days_min' => 15, 'days_max' => 25,
                'is_option' => true, 'is_at_risk' => false, 'risk_note' => null,
            ],
            [
                'code' => 'O2',
                'name' => 'Virements automatiques aux propriétaires',
                'phase' => 'Options',
                'content' => "Au-delà du calcul, de l'export et du PDF compris dans le lot 9.",
                'days_min' => 5, 'days_max' => 8,
                'is_option' => true, 'is_at_risk' => false, 'risk_note' => null,
            ],
            [
                'code' => 'O3',
                'name' => 'Back-office adapté aux tablettes et mobiles',
                'phase' => 'Options',
                'content' => "Modifie le front existant, contrairement au reste du chantier.",
                'days_min' => 5, 'days_max' => 8,
                'is_option' => true, 'is_at_risk' => false, 'risk_note' => null,
            ],
            [
                'code' => 'O4',
                'name' => 'Contrats, rapports, double authentification',
                'phase' => 'Options',
                'content' => "Sections désactivées dans la maquette. À chiffrer une fois le périmètre tranché.",
                'days_min' => 0, 'days_max' => 0,
                'is_option' => true, 'is_at_risk' => false, 'risk_note' => null,
            ],
            [
                'code' => 'O5',
                'name' => 'Reprise des données existantes',
                'phase' => 'Options',
                'content' => "À chiffrer une fois les sources et volumes connus.",
                'days_min' => 0, 'days_max' => 0,
                'is_option' => true, 'is_at_risk' => false, 'risk_note' => null,
            ],
        ];
    }

    private function sections(): array
    {
        return SpecificationSections::all();
    }
}
