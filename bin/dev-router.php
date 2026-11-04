<?php
/* LP CONSULTING : routeur du serveur PHP intégré, pour le développement local uniquement.
   Usage (depuis le dossier prod) : php -S localhost:8000 -t public bin/dev-router.php
   Les fichiers statiques de public/ sont servis tels quels ; tout le reste passe par public/index.php. */
$public = dirname(__DIR__) . '/public';
$file = realpath($public . rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));

if ($file !== false && is_file($file) && str_starts_with($file, realpath($public)) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $public . '/index.php';
