<?php
/* LP CONSULTING : amorçage commun (site web et scripts en ligne de commande). */
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('DATABASE_PATH', ROOT_PATH . '/database');

// Chargement automatique PSR-4 : App\Foo\Bar -> app/Foo/Bar.php (aucune dépendance externe requise).
spl_autoload_register(function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) return;
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) require $file;
});

require APP_PATH . '/helpers.php';

App\Core\App::boot();
