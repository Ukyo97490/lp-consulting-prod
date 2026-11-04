<?php
/* LP CONSULTING : configuration.
   Copier ce fichier en config/config.php puis renseigner les valeurs.
   config/config.php ne doit jamais être versionné ni partagé (voir .gitignore). */
return [
    'app' => [
        // Adresse publique du site, sans barre finale (liens canoniques, sitemap, e-mails).
        'url' => 'https://www.lp-consulting.re',
        // Chemin d'installation : '' à la racine du domaine, '/sous-dossier' sinon. null = détection automatique.
        'base_path' => null,
        // Ne jamais activer en production : affiche le détail des erreurs.
        'debug' => false,
        // Clé secrète (signature des jetons du formulaire). Générer : php -r "echo bin2hex(random_bytes(32));"
        'key' => 'A_REMPLACER',
    ],

    'db' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'lp_consulting',
        'user'     => 'lp_consulting',
        'password' => 'A_REMPLACER',
        'charset'  => 'utf8mb4',
    ],

    // Envoi des demandes du formulaire de contact. transport : 'smtp' (recommandé) ou 'mail' (fonction mail() de PHP).
    // Les demandes sont dans tous les cas enregistrées en base et consultables dans l'administration.
    'mail' => [
        'transport'  => 'smtp',
        'host'       => 'mail.lp-consulting.re', // o2switch : cPanel > Comptes de messagerie > Connecter les appareils
        'port'       => 465,
        'encryption' => 'ssl',      // 'ssl' (port 465), 'tls' (port 587, STARTTLS) ou '' (déconseillé)
        'username'   => 'contact@lp-consulting.re',
        'password'   => 'A_REMPLACER',
        'from'       => 'contact@lp-consulting.re',
        'from_name'  => 'Site LP CONSULTING',
        'to'         => null,       // null = adresse saisie dans l'administration (Coordonnées)
        'timeout'    => 15,
    ],

    // Installation par le navigateur (/install?token=…) pour les hébergements sans SSH.
    // Activer le temps de l'installation avec un jeton long et aléatoire (24 caractères minimum), puis remettre à false.
    'install' => [
        'enabled' => false,
        'token'   => '',
    ],
];
