# Audit Master — NJIEZM.FR

Plateforme d'audit : rédaction de rapports notés, signature électronique avec
empreinte d'intégrité, export PDF et vérification publique par le client.

---

## Installation

**Prérequis** — PHP 8.2+, PostgreSQL 13+, Node 20+, Composer.

PostgreSQL n'est pas interchangeable : le schéma s'appuie sur `ILIKE` et
`to_char()`.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Renseignez dans `.env` la connexion à la base et le compte administrateur
initial :

```dotenv
DB_DATABASE=audit
DB_USERNAME=postgres
DB_PASSWORD=…

ADMIN_EMAIL=vous@exemple.fr
ADMIN_PASSWORD=un-mot-de-passe-solide
```

Puis :

```bash
php artisan migrate
php artisan db:seed          # compte admin + 3 modèles d'audit
php artisan storage:link     # logos clients et signatures
npm install && npm run build
```

Si `ADMIN_PASSWORD` est absent, le seeder en génère un et l'affiche dans la
console. Changez-le à la première connexion (Profil → Mot de passe).

### Jeux de données optionnels

```bash
php artisan db:seed --class=DemoSeeder     # 3 clients de démonstration, 4 audits
php artisan demo:purge                     # les efface définitivement

php artisan db:seed --class=CitadelSeeder  # diagnostic CITADEL, version offerte
```

---

## Ce que fait la plateforme

**Rédaction.** Éditeur en deux volets : formulaire à gauche, aperçu A4 paginé à
droite, mis à jour en direct. Catégories réordonnables au glisser-déposer ou au
clavier, notation sur un barème documenté de 1 à 5, pondération ×1 à ×5,
criticité / échéance / responsable sur chaque recommandation. Modèles d'audit
réutilisables et bibliothèque de catégories en autocomplétion.

Les champs libres acceptent un balisage léger : `*gras*`, `` `code` ``, et les
lignes commençant par `·`, `-` ou `1.` deviennent des listes à puces. Le même
rendu s'applique à l'écran et dans le PDF.

**Notation.** Score global pondéré, radar par catégorie, encart des trois
risques majeurs, plan d'action trié par criticité, comparaison automatique avec
l'audit précédent du même client.

**Signature.** Signer fige le contenu : l'audit devient non modifiable et non
supprimable. Une empreinte SHA-256 est calculée et un instantané du contenu est
archivé. Le client reçoit un code de vérification.

**Vérification publique.** `/verifier` — le destinataire saisit le code imprimé
sur le PDF ; la plateforme confirme l'existence du rapport et l'intégrité de son
contenu, sans jamais divulguer les constats.

**Diffusion.** PDF avec page de garde, barème, plan d'action et signature.
Aperçu dans le navigateur ou téléchargement, filigrane optionnel
(« BROUILLON », « CONFIDENTIEL », « DIAGNOSTIC GRATUIT »…), envoi par e-mail
avec le PDF en pièce jointe.

**Pilotage.** Tableau de bord (volume, score moyen, points faibles récurrents,
suivis à programmer), fiches clients avec courbe d'évolution, recherche,
filtres, tri, export CSV, actions groupées, corbeille, journal d'activité.

**Comptes.** Trois rôles : administrateur (tout le portefeuille), auditeur (ses
missions), lecture seule. Réinitialisation de mot de passe, signature manuscrite
par utilisateur.

---

## Développement

```bash
composer dev     # serveur + file d'attente + logs + Vite
php artisan test
```

### Base de test

Les tests utilisent une base PostgreSQL dédiée, à créer une fois :

```sql
CREATE DATABASE audit_test;
```

### Identité visuelle

Le sceau (favicon, icônes, logos du PDF et bannière) est généré depuis sa
définition géométrique :

```bash
php artisan brand:assets
```

Le fichier source est `public/favicon.svg` ; toute modification doit être
reportée à l'identique dans `App\Console\Commands\GenerateBrandAssets` et dans
le composant `resources/views/components/logo.blade.php`.

---

## Déploiement

`public/build` est **versionné** : le serveur de production n'a besoin ni de
Node ni de `npm run build`.

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

À vérifier avant mise en ligne :

- `APP_DEBUG=false` et `APP_ENV=production` ;
- `MAIL_MAILER` configuré sur un vrai transport (`log` n'envoie rien) ;
- `php artisan storage:link` exécuté ;
- droits d'écriture sur `storage/` et `bootstrap/cache/`.

---

## Points d'attention pour les contributeurs

**Ne jamais utiliser la forme PHP en ligne dans un gabarit Blade.** Le
compilateur la confond avec l'ouverture d'un bloc et absorbe tout le balisage
jusqu'à la fermeture suivante, ce qui produit des erreurs très éloignées de leur
cause. Utilisez systématiquement la forme bloc, regroupée en tête de vue.

**Le PDF est rendu par DomPDF**, qui ne gère ni flexbox ni grid. Toute mise en
page du gabarit `audits/pdf.blade.php` doit passer par des `<table>`.

**Ne pas remplacer les options DomPDF en bloc.** `setOptions()` écrase la
configuration entière, y compris le chemin des polices compilées. Utilisez
`setOption()`, qui modifie l'objet existant.

**Le barème de notation est déclaré à trois endroits** qui doivent rester
alignés : `App\Support\ScoreScale`, `resources/js/lib/format.js` et le tableau
du gabarit PDF.
