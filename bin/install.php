<?php
/* LP CONSULTING : installation en ligne de commande (sans effet sur une base déjà installée).
   Usage : php bin/install.php
           php bin/install.php --admin=prenom@lp-consulting.re --name="Prénom Nom"   (le mot de passe est demandé)
   Le mot de passe peut aussi être fourni par la variable d'environnement LP_ADMIN_PASSWORD. */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\Auth;
use App\Services\Installer;

$opt = getopt('', ['admin:', 'name:']);
foreach (Installer::run() as $line) echo "- $line\n";

if (!empty($opt['admin'])) {
    $password = (string) getenv('LP_ADMIN_PASSWORD');
    if ($password === '') {
        echo 'Mot de passe (12 caractères minimum, lettres et chiffres) : ';
        $unix = DIRECTORY_SEPARATOR === '/';
        if ($unix) system('stty -echo');
        $password = trim((string) fgets(STDIN));
        if ($unix) system('stty echo');
        echo "\n";
    }
    try {
        Auth::createAdmin((string) $opt['admin'], (string) ($opt['name'] ?? ''), $password);
        echo "- Compte administrateur {$opt['admin']} créé (ou mot de passe mis à jour).\n";
    } catch (InvalidArgumentException $e) {
        fwrite(STDERR, 'Erreur : ' . $e->getMessage() . "\n");
        exit(1);
    }
}
echo "Installation terminée.\n";
