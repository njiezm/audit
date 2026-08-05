<?php

namespace Database\Seeders\Citadel;

/**
 * Corps rédactionnel du cahier des charges CITADEL.
 *
 * Séparé du seeder : le contenu d'un document de cette taille n'a pas à
 * cohabiter avec la mécanique d'insertion. Chaque entrée devient une
 * section du cahier, rendue à l'écran comme dans le PDF.
 *
 * Le balisage disponible est celui de App\Support\RichText : *gras*,
 * `code`, puces, blocs préformatés entre triples accents graves, et
 * tableaux à barres verticales.
 */
class SpecificationSections
{
    /** @return array<int, array{title: string, body: string, page_break_before: bool}> */
    public static function all(): array
    {
        return [
            self::section('Comment lire les charges annoncées', self::effortUnit(), true),
            self::section('Socle technique retenu', self::stack()),
            self::section('Architecture applicative', self::architecture()),
            self::section('Conventions de développement', self::conventions()),
            self::section('Modèle de données — socle et sécurité', self::dataPlatform(), true),
            self::section('Modèle de données — cœur métier', self::dataCore()),
            self::section('Modèle de données — messagerie, finance, intégrations', self::dataSupport()),
            self::section('Règles métier structurantes', self::businessRules(), true),
            self::section('Rôles et matrice des permissions', self::permissions()),
            self::section('Exigences fonctionnelles par écran', self::screens(), true),
            self::section('Portails publics', self::portals()),
            self::section('Notifications automatiques', self::notifications()),
            self::section('Exigences non fonctionnelles', self::nonFunctional(), true),
            self::section('Stratégie de test', self::testing()),
            self::section('Environnements et déploiement', self::deployment()),
            self::section('Phasage recommandé', self::phasing(), true),
            self::section('Arbitrages à rendre avant engagement', self::decisions()),
            self::section('Hypothèses retenues', self::assumptions()),
            self::section("Critères d'acceptation", self::acceptance()),
            self::section('Livrables', self::deliverables()),
            self::section('Coûts récurrents à provisionner par le client', self::runningCosts()),
            self::section('Glossaire', self::glossary()),
        ];
    }

    private static function section(string $title, string $body, bool $pageBreak = false): array
    {
        return ['title' => $title, 'body' => $body, 'page_break_before' => $pageBreak];
    }

    // ------------------------------------------------------------------

    private static function effortUnit(): string
    {
        return <<<'TXT'
            Les charges de ce document sont exprimées en **jours-homme**. C'est une
            unité d'*effort*, et non une durée de calendrier. La distinction est
            essentielle pour lire le planning correctement.

            **Un jour-homme = une personne travaillant une journée pleine sur ce
            projet, et sur rien d'autre.**

            Soixante jours-homme ne signifient donc pas soixante jours de calendrier,
            ni deux mois. Entre les deux s'intercale tout ce qui compose une vie
            professionnelle réelle : les autres clients en cours, les week-ends et
            les congés, les échanges et réunions de cadrage, les allers-retours de
            validation, les imprévus.

            **La conversion dépend de la cadence** — c'est-à-dire du nombre de jours
            par semaine réellement consacrés à ce projet. Pour l'enveloppe de
            65 à 100 jours-homme :

            | Cadence dédiée | Durée calendaire indicative |
            | --- | --- |
            | 5 jours par semaine (temps plein) | 13 à 20 semaines, soit 3 à 5 mois |
            | 3 jours par semaine | 22 à 34 semaines, soit 5 à 8 mois |
            | 2 jours par semaine | 33 à 50 semaines, soit 8 mois à 1 an |

            **Deux facteurs allongent le calendrier sans consommer d'effort :**

            · **Les attentes de validation.** Chaque décision laissée en suspens
            arrête l'avancement sans consommer un seul jour-homme. Les dix arbitrages
            listés plus loin sont, à ce titre, le principal levier de délai dont
            dispose le client.
            · **Les délais de tiers.** L'obtention d'un statut de partenaire auprès
            d'une plateforme de réservation prend de plusieurs semaines à plusieurs
            mois. Ce temps ne se paie pas, mais il se subit — d'où le report
            volontaire de ces lots en phase 3.

            **En conséquence**, la charge et le délai sont deux engagements
            distincts. Le présent document chiffre la charge. La durée calendaire
            sera fixée au contrat, sur la base d'une cadence convenue et d'un délai
            de réponse du client aux demandes de validation.
            TXT;
    }

    private static function stack(): string
    {
        return <<<'TXT'
            La pile est imposée par le cadrage et n'est pas rediscutée : elle prolonge
            l'installation existante et conserve le front tel quel.

            | Couche | Technologie | Rôle |
            | --- | --- | --- |
            | Serveur applicatif | PHP 8.2+ / Laravel 13 | Routage, logique métier, sécurité, files d'attente |
            | Gabarits | Blade | Production du HTML côté serveur, composants réutilisables |
            | Feuilles de style | Bootstrap 5 + CSS de la maquette | Grille et composants, thème sombre existant conservé |
            | Interactivité | JavaScript natif, modules ES | Aucun framework front, conformément au cadrage |
            | Build | Vite | Compilation des styles et des modules, empreintes de cache |
            | Base de données | PostgreSQL 15+ | Source de vérité, contraintes d'intégrité, recherche plein texte |
            | Files d'attente | Laravel Queue (base de données) | Notifications, synchronisations, agrégats analytics |
            | Tâches planifiées | Laravel Scheduler | Synchronisation des calendriers, relances, agrégats |
            | PDF | DomPDF | Relevés de reversement, exports propriétaires |

            **Extensions PostgreSQL requises** : `btree_gist` pour la contrainte
            anti-chevauchement, `citext` pour l'unicité des e-mails insensible à la
            casse, `pgcrypto` pour le chiffrement au repos, `unaccent` pour la
            recherche plein texte en français.

            **Ce qui n'est pas retenu** : aucun framework JavaScript front, aucune API
            REST publique en périmètre de base, aucun service tiers de rendu.
            L'option d'architecture A — vues serveur enrichies — est celle qui est
            chiffrée.
            TXT;
    }

    private static function architecture(): string
    {
        return <<<'TXT'
            Le fichier unique de 5 416 lignes est éclaté selon l'arborescence
            ci-dessous. Le découpage se réalise **à rendu constant** : à la fin du
            lot 1, l'application affiche exactement la même chose qu'aujourd'hui.

            ```
            app/
              Models/              organisations, propriétés, réservations, ménages…
              Http/
                Controllers/       un contrôleur par écran
                Requests/          validation, une classe par formulaire
                Middleware/        organisation courante, rôle, quotas
              Policies/            règles d'accès, une par modèle
              Services/            réservations, ménages, reversements, synchronisation
              Jobs/                notifications, imports de calendrier, agrégats
              Enums/               statuts de réservation, de ménage, rôles, canaux
              Support/             formats monétaires, plages de dates, jetons signés

            resources/
              css/
                citadel.css        ~1 020 lignes de style extraites, servies par Vite
              js/citadel/
                app.js             amorçage, navigation entre écrans
                api.js             appels serveur, protection CSRF
                screens/           jour, messages, logements, réservations, ménages,
                                   prestataires, propriétaires, analytics, paramètres
                modals/            réservation, ménage, logement, accès, checklist,
                                   tarifs, invitation, calendrier…
                portals/           voyageur, ménage, prestataire, propriétaire
              views/
                layouts/app.blade.php
                partials/          sidebar, header, notifications, recherche, toast
                screens/           9 écrans
                components/        ~20 composants : carte d'indicateur, pastille de
                                   statut, étiquette de logement, cellule voyageur,
                                   carte de ménage, ligne de reversement, tableau…
                modals/            ~20 modales
                portals/           4 pages publiques, accès par lien signé

            database/
              migrations/          ~30 tables
              seeders/             jeu de démonstration
            ```

            **Volumétrie estimée** : 60 à 70 fichiers de vues, environ 25 modules
            JavaScript.

            **Règle de conversion** — tout bloc HTML aujourd'hui fabriqué par
            assemblage de texte dans le JavaScript devient un composant Blade rendu
            côté serveur : panneau de l'équipe ménage, liste des propriétés,
            comparatif analytics, fiche et historique de réservation, blocs
            d'assignation, création de réservation, de ménage, de logement ou de
            membre d'équipe, éditeur de checklist, liens d'avis, carrousel de
            tarification.
            TXT;
    }

    private static function conventions(): string
    {
        return <<<'TXT'
            · **Montants en centimes**, jamais en flottants ni en chaînes formatées.
            Le formatage est du ressort de l'affichage, jamais du stockage.
            · **Dates et heures horodatées** avec fuseau explicite. Aucun numéro de
            jour isolé, contrairement à la maquette.
            · **Identifiants publics** distincts des clés primaires pour tout objet
            exposé dans une URL de portail.
            · **Aucun assemblage de HTML en JavaScript.** Le serveur produit le
            balisage ; le JavaScript manipule des données et des états.
            · **Échappement systématique** : toute valeur issue de la base passe par
            l'échappement Blade. La faille d'injection relevée à l'audit est corrigée
            à ce niveau, structurellement.
            · **Validation par classe de requête**, jamais dans le contrôleur.
            · **Règles d'accès en Policy**, jamais en condition dans une vue.
            · **Migrations rejouables** depuis une base vide.
            · Nommage des tables et colonnes en anglais, libellés d'interface en
            français.
            TXT;
    }

    private static function dataPlatform(): string
    {
        return <<<'TXT'
            Environ 30 tables. La maquette révèle une plateforme multi-conciergerie
            — formules d'abonnement, quotas, rôles, facturation. **Hypothèse à
            confirmer avant la première migration** : elle structure tout le reste.

            | Table | Champs structurants |
            | --- | --- |
            | `organisations` | raison sociale, SIRET, adresse, e-mail de contact, formule, références de facturation |
            | `users` | organisation, identité, authentification, rôle, couleur d'avatar **persistée**, téléphone |
            | `roles` / `permissions` | administrateur, gérant, ménage, prestataire, propriétaire |
            | `invitations` | e-mail, rôle attribué, jeton, expiration, date d'acceptation |
            | `usage_quotas` | logements, utilisateurs et SMS autorisés ; alimente les barres de consommation |
            | `activity_logs` | traçabilité des actions, acteur, objet, horodatage |
            | `notifications` | notifications internes à l'application |

            La couleur d'avatar est une colonne, pas un tirage aléatoire à
            l'affichage comme dans la maquette.
            TXT;
    }

    private static function dataCore(): string
    {
        return <<<'TXT'
            | Table | Champs structurants |
            | --- | --- |
            | `owners` | identité, coordonnées, **IBAN chiffré**, taux de commission par défaut |
            | `properties` | organisation, propriétaire, nom, adresse, ville, capacité, couleur, statut |
            | `property_rates` | semaine, week-end, haute saison, durée minimale, frais de ménage |
            | `rate_seasons` | tarifs par période datée, si des saisons réelles sont attendues |
            | `property_accesses` | code, politique fixe ou rotative, **réseau et mot de passe wifi chiffrés**, consignes |
            | `review_links` | plateforme et URL de dépôt d'avis |
            | `checklist_templates` | trame de ménage propre à chaque logement |
            | `guests` | identité, coordonnées, plateforme d'origine |
            | `reservations` | logement, voyageur, plateforme, référence externe, **arrivée et départ horodatées**, nombre de nuits calculé, personnes, **montant en centimes**, devise, statut, annulation, identifiant de synchronisation, notes |
            | `reservation_events` | piste d'audit alimentant l'historique affiché |
            | `cleanings` | logement, réservation liée, horaire prévu, durée estimée, priorité, statut, fenêtre de rotation, début et fin réels, respect de l'horaire, checklist complète, note qualité |
            | `cleaning_assignments` | plusieurs agents par intervention — la maquette en prévoit jusqu'à six |
            | `cleaning_checklists` | copie du modèle au moment de la création, avec horodatage de validation |
            | `cleaning_photos` | justificatifs envoyés depuis le portail |
            | `staff_availabilities` | disponibilité par agent et congés jour par jour |
            | `contractors` | identité, métier, coordonnées, activité |
            | `interventions` | prestataire, logement, nature, statut, planification, réalisation, coût, notes |
            | `incident_reports` | remontées des portails voyageur, ménage et prestataire : nature, description, photos, statut |
            TXT;
    }

    private static function dataSupport(): string
    {
        return <<<'TXT'
            | Domaine | Tables |
            | --- | --- |
            | Messagerie | `conversations` (canal, interlocuteur, logement, fil externe, dernier message, non-lus), `messages` (sens, contenu, horodatage, identifiant externe, lecture), `message_attachments`, `message_templates` |
            | Finance | `payout_statements` (propriétaire, logement, période, brut, taux et montant de commission, charges, net, statut, date de versement, PDF), `payout_charges`, `commission_rules` |
            | Intégrations | `connectors` (identifiants chiffrés, statut, dernière synchronisation, dernière erreur), `calendar_feeds` (flux par logement), `sync_logs` (alimente les alertes d'échec), `api_tokens` |
            | Portails publics | `portal_links` (type, jeton, cible, expiration, révocation, dernière ouverture) |

            **Chiffrement au repos** : IBAN, codes d'accès de logement et mots de
            passe wifi, identifiants de connecteurs. Le chiffrement est appliqué au
            niveau du modèle, de façon transparente pour le reste du code.
            TXT;
    }

    private static function businessRules(): string
    {
        return <<<'TXT'
            **1. Zéro double réservation, garanti par la base elle-même.**

            Une contrainte d'exclusion interdit physiquement deux séjours qui se
            chevauchent sur un même logement. La détection de conflit cesse de
            dépendre d'une expression régulière côté navigateur : elle devient une
            garantie du moteur, y compris pour les réservations arrivant par
            synchronisation automatique.

            ```
            CREATE EXTENSION IF NOT EXISTS btree_gist;

            ALTER TABLE reservations ADD CONSTRAINT no_overlap
              EXCLUDE USING gist (
                property_id WITH =,
                daterange(check_in::date, check_out::date, '[)') WITH &&
              ) WHERE (status <> 'cancelled');
            ```

            **2. Nombre de nuits en colonne calculée** — toujours cohérent, jamais
            recalculé à la main.

            **3. Unicité des e-mails insensible à la casse**, portée par le type
            `citext` et non par du code applicatif.

            **4. Création automatique des ménages à partir des départs.** Chaque
            départ engendre une intervention de ménage ; la fenêtre de rotation est
            calculée entre le départ et l'arrivée suivante, et déclenche une alerte
            lorsqu'elle passe sous le seuil paramétré.

            **5. Liens de portails signés et expirants.** Chaque lien porte un jeton,
            une cible, une expiration et une possibilité de révocation. À défaut, un
            code d'accès de logement resterait valable indéfiniment.

            **6. Reversements.** Brut, commission, charges justifiées, net. Les
            montants sont calculés en centimes ; le relevé PDF est un instantané figé
            à la date d'émission.

            **7. Agrégats analytics matérialisés**, rafraîchis en tâche de fond : les
            tableaux de bord restent instantanés quel que soit le volume.

            **8. Recherche plein texte intégrée** sur logements, voyageurs,
            réservations et messages — aujourd'hui réalisée par un parcours de la
            page affichée.
            TXT;
    }

    private static function permissions(): string
    {
        return <<<'TXT'
            Quatre rôles opérationnels plus l'administrateur. La lecture d'une ligne
            est toujours restreinte à l'organisation courante.

            | Domaine | Administrateur | Gérant | Ménage | Prestataire | Propriétaire |
            | --- | --- | --- | --- | --- | --- |
            | Logements | complet | complet | lecture assignée | lecture assignée | lecture des siens |
            | Réservations | complet | complet | lecture assignée | — | lecture des siennes |
            | Ménages | complet | complet | ses interventions | — | — |
            | Prestataires | complet | complet | — | ses interventions | — |
            | Messagerie | complet | complet | — | — | — |
            | Reversements | complet | complet | — | — | lecture des siens |
            | Analytics | complet | complet | — | — | lecture des siens |
            | Équipe et invitations | complet | lecture | — | — | — |
            | Paramètres et abonnement | complet | — | — | — | — |
            | Journal d'activité | complet | lecture | — | — | — |

            Les rôles ménage, prestataire et propriétaire accèdent en priorité par
            les portails publics ; leur compte applicatif reste facultatif.
            TXT;
    }

    private static function screens(): string
    {
        return <<<'TXT'
            Neuf écrans, plus l'écran de check-in laissé vide dans la maquette. Le
            rendu visuel est conservé à l'identique ; seule la provenance des données
            change.

            | Écran | Fonctions attendues |
            | --- | --- |
            | Vue du jour | Bandeau d'actions requises, timeline horaire des événements, fenêtres de rotation entre départ et arrivée avec niveaux de criticité |
            | Messages | Boîte de réception unifiée, liste de conversations, fil de discussion, réponse, filtres lus / non lus, envoi de liens de portails |
            | Logements | Quatre indicateurs clés, contexte du jour, cartes logements, alertes, bloc de reversements propriétaires |
            | Réservations | Tableau, filtres par statut, export CSV, calendrier mensuel, fiche, historique, suppression et restauration, détection de conflit |
            | Ménages | Panneau équipe (disponibilité, congés, qualité), Kanban à trois colonnes, assignation multi-agents, clôture avec notation |
            | Prestataires | Grille des intervenants, statuts, planification d'intervention |
            | Propriétaires | Reversements regroupés par propriétaire |
            | Analytics | Taux d'occupation, prix moyen, revenu par logement disponible, chiffre d'affaires, comparatif par logement |
            | Paramètres | Organisation, équipe et rôles, abonnement (trois formules et quotas), intégrations, notifications automatiques, journal d'activité |
            | Check-in | Écran vide dans la maquette — emplacement réservé, hors périmètre tant que le besoin n'est pas exprimé |

            **Sections explicitement désactivées** dans la maquette : contrats,
            rapports et double authentification portent la mention « Bientôt
            disponible ». Leur présence dans le périmètre doit être tranchée avant
            chiffrage ferme.
            TXT;
    }

    private static function portals(): string
    {
        return <<<'TXT'
            Quatre pages publiques, accessibles par lien signé et expirant, conçues
            pour mobile.

            | Portail | Contenu | Actions |
            | --- | --- | --- |
            | Voyageur | Code d'accès, wifi, consignes, liens d'avis | Confirmer l'arrivée, signaler un problème |
            | Ménage | Mission du jour, checklist, photos | Démarrer, clôturer, envoyer des photos, noter |
            | Prestataire | Intervention planifiée | Arrivée et départ en un clic, signalement |
            | Propriétaire | Revenus, réservations, reversements | Consulter et télécharger le relevé |

            **Exigences communes** : jeton à usage limité dans le temps, révocation
            possible à tout moment, journalisation de la dernière ouverture,
            limitation du débit de requêtes, aucune donnée d'un autre logement
            accessible depuis le lien.
            TXT;
    }

    private static function notifications(): string
    {
        return <<<'TXT'
            Moteur de règles paramétrable, modèles de message éditables, envoi par
            e-mail, SMS et WhatsApp, files d'attente et planificateur, compteur de
            consommation, journal d'envoi.

            | Déclencheur | Destinataire | Canal | Moment |
            | --- | --- | --- | --- |
            | Réservation confirmée | Voyageur | E-mail | Immédiat |
            | Veille d'arrivée | Voyageur | SMS ou WhatsApp | J-1 |
            | Code d'accès | Voyageur | SMS | 2 h avant l'arrivée |
            | Assignation de ménage | Agent | SMS ou WhatsApp | À l'assignation |
            | Rotation serrée | Gérant | Notification interne | Au calcul de la fenêtre |
            | Échec de synchronisation | Gérant | E-mail | À l'échec |
            | Relevé de reversement | Propriétaire | E-mail | À l'émission |

            Chaque règle est activable, son modèle de message est éditable, et son
            envoi est tracé. Le compteur de SMS alimente les quotas de la formule.
            TXT;
    }

    private static function nonFunctional(): string
    {
        return <<<'TXT'
            **Performance** — affichage des écrans de liste sous 500 ms pour
            1 000 réservations et 100 logements. Pagination et filtrage côté serveur,
            index sur les colonnes de tri et de filtre, agrégats analytics
            matérialisés et rafraîchis en tâche de fond.

            **Sécurité** — échappement systématique corrigeant la faille d'injection
            relevée à l'audit ; chiffrement au repos des IBAN, codes d'accès et mots
            de passe wifi ; liens de portails signés et expirants ; limitation du
            débit sur l'authentification et les portails publics ; journalisation des
            accès aux données sensibles ; en-têtes de sécurité HTTP ; protection CSRF
            sur toutes les écritures.

            **RGPD** — registre des traitements, durées de conservation appliquées
            automatiquement, export et suppression des données d'un voyageur sur
            demande, accord de traitement avec l'hébergeur, hébergement des polices
            en local (aujourd'hui chargées depuis un CDN externe), localisation des
            données à trancher.

            **Disponibilité et reprise** — sauvegarde quotidienne avec copie hors
            site, **restauration effectivement testée** et non simplement documentée,
            supervision des tâches de fond et des files d'attente, remontée
            centralisée des erreurs.

            **Compatibilité** — navigateurs à jour : Chrome, Firefox, Safari, Edge.
            Les portails publics sont conçus pour mobile. Le back-office reste prévu
            pour écran large ; son adaptation tablette est une option chiffrée à part
            car elle modifie le front.

            **Accessibilité** — contrastes conformes au niveau AA, navigation au
            clavier sur les parcours critiques, libellés de formulaire explicites,
            états d'erreur annoncés.

            **Journalisation** — toute action métier structurante est tracée :
            acteur, objet, horodatage, adresse IP.
            TXT;
    }

    private static function testing(): string
    {
        return <<<'TXT'
            Les tests portent sur les parcours dont une régression coûterait cher, et
            non sur une couverture chiffrée arbitraire.

            | Parcours | Nature | Ce qui est vérifié |
            | --- | --- | --- |
            | Création de réservation | Fonctionnel | Refus effectif d'un chevauchement, y compris par insertion directe |
            | Synchronisation de calendrier | Fonctionnel | Import, dédoublonnage, gestion d'un flux invalide |
            | Génération des ménages | Fonctionnel | Un départ engendre une intervention, fenêtre de rotation correcte |
            | Calcul de reversement | Unitaire | Brut, commission, charges, net, en centimes |
            | Règles d'accès | Sécurité | Chaque rôle sur chaque domaine, y compris les refus |
            | Liens de portails | Sécurité | Expiration, révocation, cloisonnement entre logements |
            | Injection | Sécurité | Champs alimentés par la base, rendus échappés |
            | Rendu du découpage | Visuel | Comparaison écran par écran avec la maquette d'origine |

            Les tests s'exécutent en intégration continue à chaque envoi de code.
            TXT;
    }

    private static function deployment(): string
    {
        return <<<'TXT'
            Trois environnements : développement local, recette, production.

            | Élément | Attendu |
            | --- | --- |
            | Serveur applicatif | PHP 8.2+, extensions requises, HTTPS avec certificat renouvelé automatiquement |
            | Base de données | PostgreSQL 15+ managé, extensions installées, sauvegarde quotidienne |
            | Files d'attente | Superviseur de processus, redémarrage automatique |
            | Tâches planifiées | Planificateur activé, supervision des exécutions |
            | Recette | Environnement isolé, données anonymisées |
            | Déploiement | Migrations, compilation des assets, mise en cache de la configuration |
            | Supervision | Remontée d'erreurs, alerte sur échec de tâche de fond |

            Les assets compilés sont versionnés : le serveur de production n'a besoin
            ni de Node ni d'une étape de compilation.
            TXT;
    }

    private static function phasing(): string
    {
        return <<<'TXT'
            **Phase 1 — Socle opérationnel (42 à 61 j).** Lots 0 à 7, 12, 15, 16 et
            17. Aboutit à une application réellement utilisable au quotidien :
            logements, réservations synchronisées, ménages, prestataires, portails
            voyageur et ménage, le tout en production, recette et documentation
            comprises.

            **Phase 2 — Pilotage et finance (12 à 18 j).** Lots 9, 10, 11 et 14.
            Reversements propriétaires, analytics, notifications automatiques,
            recherche.

            **Phase 3 — Communication et commercialisation (6 à 15 j).** Lots 8 et
            13. Messagerie unifiée et abonnements — les deux seuls lots dépendant de
            tiers, volontairement repoussés après validation sur le terrain.

            Ce découpage permet une mise en production dès la première phase et évite
            d'immobiliser le projet sur les deux lots dont la durée ne dépend pas du
            prestataire.

            La somme des lots s'établit à 60 – 94 jours. L'enveloppe annoncée de
            65 – 100 jours intègre une marge de cadrage de 5 à 6 jours, destinée aux
            aléas d'intégration et aux allers-retours de recette.
            TXT;
    }

    private static function decisions(): string
    {
        return <<<'TXT'
            Dix décisions conditionnent le chiffrage définitif. La première à elle
            seule peut faire varier le périmètre de plusieurs semaines.

            1. **Plateforme pour une seule conciergerie, ou service ouvert à
            plusieurs clients ?** Cette réponse structure le modèle de données et
            fait tomber ou non le lot 13. La maquette présente trois formules
            d'abonnement, des quotas et quatre rôles, ce qui suggère un service
            multi-clients — à confirmer.
            2. Architecture front : vues serveur enrichies, ou API et JavaScript
            réécrit ?
            3. Portail ménage : web mobile inclus au lot 7, ou application native en
            option ?
            4. Messagerie : connexions directes aux plateformes, passage par un
            intermédiaire spécialisé, ou SMS, WhatsApp et e-mail seulement ?
            5. Reversements : calcul, export et PDF, ou virements réellement
            automatisés ?
            6. Volumétrie attendue : combien de logements, de conciergeries et de
            réservations par an ?
            7. Hébergement : qui héberge, quel budget mensuel, et où sont localisées
            les données ?
            8. Propriété intellectuelle : clause de cession ou de licence, à
            expliciter au contrat.
            9. Gestion du temps : fuseau horaire, séjours à cheval sur deux mois,
            heures d'arrivée et de départ propres à chaque logement.
            10. Périmètre des sections désactivées : contrats, rapports et double
            authentification, dedans ou dehors ?
            TXT;
    }

    private static function assumptions(): string
    {
        return <<<'TXT'
            · L'option d'architecture A — vues serveur enrichies en Blade, JavaScript
            natif — est retenue. Les options B et C modifieraient l'enveloppe.
            · Le rendu visuel du back-office est conservé à l'identique ; aucune
            adaptation mobile n'est comprise.
            · Les charges sont exprimées en jours-homme de développement, hors taux
            journalier, hors coûts d'infrastructure et hors délais d'obtention des
            accès auprès de tiers.
            · La borne haute de chaque fourchette est celle qui tient si le périmètre
            évolue en cours de route.
            · La maquette livrée fait foi comme référence de rendu attendu.
            · PostgreSQL est disponible en version 15 ou supérieure, avec la
            possibilité d'installer les extensions listées.
            TXT;
    }

    private static function acceptance(): string
    {
        return <<<'TXT'
            | Lot | Critère vérifiable |
            | --- | --- |
            | 1 | Comparaison visuelle écran par écran entre la maquette d'origine et l'application découpée. Aucune différence admise. |
            | 2 | Chaque rôle accède exactement aux domaines de la matrice de permissions, refus compris. |
            | 3 | Codes d'accès et mots de passe wifi illisibles en base sans la clé applicative. |
            | 4 | Impossibilité démontrée de créer deux séjours chevauchants sur un même logement, y compris par insertion SQL directe. |
            | 5 | Un départ engendre automatiquement un ménage, avec fenêtre de rotation correcte et alerte au-delà du seuil. |
            | 7 | Un lien de portail expiré ou révoqué ne donne plus accès ; aucun autre logement n'est atteignable depuis un lien. |
            | 9 | Relevé de reversement recalculé à l'identique en centimes, PDF figé à la date d'émission. |
            | 12 | Import d'un flux calendrier réel, dédoublonnage effectif, échec d'un flux invalide remonté en alerte. |
            | 15 | Aucune faille d'injection sur les champs alimentés par la base ; tests automatisés verts. |
            | 16 | Restauration d'une sauvegarde effectivement réalisée et constatée, non simplement documentée. |

            **Critères transverses** — les montants sont stockés en centimes, les
            dates sont horodatées avec mois et année, aucun état métier ne subsiste
            uniquement en mémoire du navigateur, aucune information métier n'est
            déduite du texte affiché à l'écran.
            TXT;
    }

    private static function deliverables(): string
    {
        return <<<'TXT'
            · Code source complet, versionné, avec historique.
            · Migrations de base de données rejouables depuis zéro.
            · Jeu de données de démonstration.
            · Tests automatisés sur les parcours critiques.
            · Manuel d'exploitation : déploiement, sauvegardes, supervision,
            procédure de restauration.
            · Documentation fonctionnelle destinée aux utilisateurs.
            · Documentation technique : modèle de données, règles métier, points
            d'intégration.
            · Environnement de recette distinct de la production.
            TXT;
    }

    private static function runningCosts(): string
    {
        return <<<'TXT'
            Indépendants du développement et non compris dans les charges ci-dessus.
            À provisionner par le client dès le démarrage.

            | Poste | Nature |
            | --- | --- |
            | Hébergement applicatif | Mensuel, dimensionné selon la volumétrie |
            | Base de données managée | Mensuel, sauvegardes incluses |
            | SMS | À l'unité, indexé sur le nombre de réservations |
            | WhatsApp Business | Par conversation |
            | Intermédiaire de réservation | Abonnement, si l'option est retenue |
            | Remontée d'erreurs | Mensuel |
            | Sauvegardes externalisées | Mensuel |
            | Nom de domaine et certificat | Annuel |
            TXT;
    }

    private static function glossary(): string
    {
        return <<<'TXT'
            | Terme | Définition |
            | --- | --- |
            | Contrainte d'exclusion | Règle de base de données interdisant deux lignes dont des plages se recouvrent. Ici : deux séjours sur un même logement. |
            | Fenêtre de rotation | Intervalle entre le départ d'un voyageur et l'arrivée du suivant, pendant lequel le ménage doit être réalisé. |
            | Flux calendrier | Fichier standard échangé avec les plateformes de réservation. Transporte les disponibilités, ni les tarifs ni les messages. |
            | Intermédiaire de réservation | Service tiers déjà agréé auprès des plateformes, permettant d'accéder à leurs données sans devenir soi-même partenaire officiel. |
            | Jour-homme | Une journée de travail d'une personne. Unité de charge du présent document. |
            | Lien signé | URL comportant une signature cryptographique et une date d'expiration, ce qui la rend non falsifiable et temporaire. |
            | Lot | Ensemble cohérent de travaux, chiffré et recettable indépendamment. |
            | Marge de cadrage | Écart entre la somme des lots et l'enveloppe annoncée, destiné aux aléas. |
            | Rendu constant | Propriété d'un chantier technique qui ne modifie rien de ce que voit l'utilisateur. |
            TXT;
    }
}
