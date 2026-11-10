<?php
/* LP CONSULTING : génère css/style.min.css, css/critical.min.css et js/main.min.js.
   Usage : php outils/minify.php   (à relancer après chaque modification de style.css ou main.js)
   Même traitement que le bouton Maintenance du tableau de bord de l'administration. */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
require dirname(__DIR__) . '/app/bootstrap.php';

foreach (App\Services\Minifier::run() as $file => $size) {
    printf("- %s : %d -> %d octets\n", $file, $size['before'], $size['after']);
}
