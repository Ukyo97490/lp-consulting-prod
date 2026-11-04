# LP CONSULTING : version PHP / MySQL (production)

Même site que la version statique, avec :
- des pages générées par le serveur, donc mieux référencées ;
- des adresses propres, sans extension : `/references`, `/etudes-techniques`, `/admin`… Les anciennes adresses `.html` redirigent en 301 ;
- une base MySQL / MariaDB pour les textes, les projets, l'historique et les demandes de contact ;
- une administration protégée par identifiant. Chaque enregistrement est publié immédiatement et un historique des 100 dernières versions permet de revenir en arrière en un clic ;
- un formulaire de contact qui envoie vraiment l'e-mail (SMTP) et enregistre chaque demande en base, consultable dans l'administration.

Le code suit une architecture **MVC** (modèle, vue, contrôleur) avec un petit noyau maison, sans dépendance externe. Il fonctionne sur un hébergement mutualisé, sans Composer.

## Architecture

```
prod/
├── app/
│   ├── Core/            Noyau : App, Router, Request, Response, Controller, View, Model,
│   │                    Database (PDO), Session, Config, Url, ErrorHandler, Logger
│   ├── Controllers/     Contrôleurs (une classe par écran)
│   │   ├── HomeController, MissionController, ReferenceController, LegalController,
│   │   │   ContactController, SitemapController, ErrorController, InstallController
│   │   └── Admin/       AuthController, DashboardController
│   │       └── Api/     API JSON de l'administration (contenus, projets, images, messages…)
│   ├── Models/          Accès SQL, une classe par table : ContentSection, Project, Revision,
│   │                    Message, AdminUser, LoginAttempt
│   ├── Services/        Logique métier : SiteContent (lecture/publication/historique),
│   │                    ContentSchema (normalisation), Auth, ContactForm, Mailer (SMTP),
│   │                    ImageLibrary (envoi d'images), Installer
│   ├── Middleware/      SecurityHeaders, CanonicalUrl, Authenticate, AuthenticateApi, VerifyCsrf
│   ├── Views/           Gabarits PHP : layouts/, partials/, pages/, admin/
│   ├── bootstrap.php    Chargement automatique des classes et démarrage
│   └── helpers.php      Fonctions des vues : e(), url(), asset(), icon(), paras()…
├── config/
│   ├── config.sample.php   Modèle de configuration (à copier en config.php)
│   └── routes.php          Table des routes
├── database/
│   ├── schema.sql          Structure de la base
│   └── seeds/              Contenus initiaux (textes et projets actuels du site)
├── bin/
│   ├── install.php         Installation en ligne de commande
│   └── dev-router.php      Serveur de développement local
├── public/               SEUL dossier exposé au web
│   ├── index.php           Contrôleur frontal (toutes les adresses passent par lui)
│   ├── css/ js/ fonts/ img/ docs/
│   ├── assets/admin/       Interface de l'administration (JS/CSS)
│   └── uploads/            Images envoyées depuis l'administration
└── storage/logs/         Journal des erreurs (app.log)
```

**Parcours d'une requête :** `public/index.php` → `App::run()` → `Router` → middlewares globaux (en-têtes de sécurité, adresses canoniques) → middlewares de la route (connexion, CSRF) → contrôleur → services et modèles → vue → `Response`.

**Ajouter une page :** une route dans `config/routes.php`, une méthode de contrôleur, un gabarit dans `app/Views/pages/`.

## Tester en local (sans WAMP ni Docker)

Il faut PHP 8.1 ou plus en ligne de commande, avec les extensions `pdo_mysql` et `mbstring`, et une base MySQL/MariaDB.

```bash
cd prod
cp config/config.sample.php config/config.php   # puis renseigner la base, debug => true, url => http://localhost:8000
php bin/install.php --admin=vous@exemple.re --name="Votre nom"
php -S localhost:8000 -t public bin/dev-router.php
```

Ouvrir ensuite http://localhost:8000 pour le site et http://localhost:8000/admin pour l'administration.

## Mise en production (o2switch)

Le site est hébergé chez **o2switch** (cPanel). Les étapes ci-dessous utilisent les menus de cPanel.

### 1. Prérequis
- PHP 8.1+ avec `pdo_mysql` et `mbstring`. Recommandées : `gd` (réencodage des images), `intl` et `exif`. Chez o2switch, la version se règle dans cPanel > *Sélectionner une version de PHP* (choisir 8.3) ; ces extensions y sont cochées par défaut.
- MySQL 5.7+ ou MariaDB 10.3+ (fournie par o2switch).
- Apache avec `mod_rewrite` (actif chez o2switch).

### 2. Fichiers
Seul le dossier `public/` doit être accessible depuis le web. Le code, la configuration et ses mots de passe restent au-dessus.

```
/home/<compte>/
├── lp-consulting/        ← tout le contenu de prod/
│   ├── app/  config/  database/  bin/  storage/
│   └── public/           ← racine du domaine
└── public_html/
```

1. Envoyer le **contenu** de `prod/` dans `/home/<compte>/lp-consulting/` : cPanel > *Gestionnaire de fichiers* (envoyer un `.zip` puis l'extraire), FTP ou SSH. Ne pas envoyer `config/config.php` local ni `storage/logs/app.log`.
2. Faire pointer le domaine sur `lp-consulting/public` : cPanel > *Domaines* > *Gérer* > *Racine du document*. Pour le domaine principal du compte, si cPanel impose `public_html`, demander au support o2switch de changer la racine du document.
3. Si la racine ne peut vraiment pas être changée, solution de repli : mettre le contenu de `prod/` directement dans `public_html/`. Le `.htaccess` racine envoie tout vers `public/` et bloque l'accès au reste.

PHP doit pouvoir écrire dans `public/uploads/` et `storage/logs/` (droits 755 sur les dossiers, c'est le cas par défaut chez o2switch).

### 3. Base de données, e-mail et configuration
1. Créer la base : cPanel > *Assistant Bases de données MySQL*. Créer une base, un utilisateur avec un mot de passe fort, et lui donner **tous les privilèges** sur la base. cPanel préfixe les noms par le nom du compte (`<compte>_lpconsulting`).
2. Créer l'adresse `contact@lp-consulting.re` : cPanel > *Comptes de messagerie*. Les réglages SMTP sont dans *Connecter les appareils* : en général serveur `mail.lp-consulting.re`, port **465**, SSL.
3. Copier `config/config.sample.php` en `config/config.php` (sur le serveur) puis renseigner :
   - `app.url` : `https://www.lp-consulting.re` ;
   - `app.key`, à générer avec `php -r "echo bin2hex(random_bytes(32));"` (ou toute chaîne aléatoire de 64 caractères) ;
   - `db` : `host` = `localhost`, nom de la base et utilisateur **avec le préfixe** cPanel ;
   - `mail` : serveur, port, identifiant (`contact@lp-consulting.re`) et mot de passe de la boîte.

### 4. Installation
- **Avec SSH** (activable dans cPanel > *Accès SSH*, en autorisant son adresse IP dans *Autorisation SSH*) : `cd ~/lp-consulting && php bin/install.php --admin=prenom@lp-consulting.re --name="Prénom Nom"`. Le mot de passe est demandé.
- **Sans SSH** :
  1. dans `config/config.php`, mettre `install.enabled` à `true` et `install.token` à une chaîne aléatoire d'au moins 24 caractères ;
  2. ouvrir `https://www.lp-consulting.re/install?token=…` et créer le compte ;
  3. **remettre `install.enabled` à `false`**.

L'installation crée les tables et importe les textes et projets actuels. Relancée, elle ne modifie pas une base déjà remplie.

### 5. Vérifications finales
- Activer HTTPS : cPanel > *Let's Encrypt SSL* (ou *SSL/TLS Status*), certificat pour `lp-consulting.re` et `www.lp-consulting.re`. Décommenter ensuite la redirection HTTPS/www dans `public/.htaccess`.
- Envoyer une demande de test avec le formulaire. Vérifier que l'e-mail arrive et que la demande apparaît dans *Administration > Demandes de contact*.
- Compléter les mentions légales (valeurs entre crochets) dans *Administration > Coordonnées et mentions*.
- Déclarer `https://www.lp-consulting.re/sitemap.xml` dans Google Search Console.
- Sauvegardes : o2switch sauvegarde automatiquement fichiers et bases (restauration dans cPanel > *JetBackup*). Garder en plus, de temps en temps, un export de la base (cPanel > *phpMyAdmin* > *Exporter*) et une copie de `public/uploads/`.

## Sécurité
- **Base de données** : requêtes SQL préparées (PDO) et échappement systématique à l'affichage. Les contenus reçus sont ramenés à la structure attendue.
- **Administration** :
  - mots de passe hachés ;
  - cookie de session limité à `/admin` (`HttpOnly`, `SameSite=Strict`, `Secure` en HTTPS) ;
  - jeton CSRF sur toutes les actions ;
  - blocage après 5 échecs de connexion en 15 minutes ;
  - déconnexion après 2 h d'inactivité.
- **Images envoyées** : le type réel est vérifié, puis l'image est redimensionnée et réencodée (métadonnées supprimées). Aucun script n'est exécutable dans `uploads/`.
- **Formulaire** :
  - aucun cookie sur les pages publiques ;
  - jeton signé et champ piège contre les robots ;
  - 5 demandes par heure et par adresse IP.
- **En-têtes et erreurs** :
  - politique de sécurité du contenu (CSP) stricte : aucun script externe ;
  - `X-Frame-Options`, `nosniff`, HSTS en HTTPS ;
  - erreurs journalisées dans `storage/logs/app.log`, jamais affichées en production.

Conception et réalisation : DG-Web (dg-web.re).
