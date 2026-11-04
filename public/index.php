<?php
/* LP CONSULTING : contrôleur frontal. Toutes les adresses passent par ce fichier (voir config/routes.php). */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

App\Core\App::run();
