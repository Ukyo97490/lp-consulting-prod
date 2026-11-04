<?php
declare(strict_types=1);

namespace App\Core;

/** Point d'entrée de l'application : configuration, gestion des erreurs, routage. */
final class App
{
    public static function boot(): void
    {
        mb_internal_encoding('UTF-8');
        date_default_timezone_set('Indian/Reunion');

        // LP_CONFIG permet à un environnement local d'utiliser sa propre configuration.
        $file = getenv('LP_CONFIG') ?: ROOT_PATH . '/config/config.php';
        if (!is_file($file)) {
            http_response_code(503);
            exit("Configuration absente : copier config/config.sample.php en config/config.php.\n");
        }
        Config::load($file);
        ErrorHandler::register((bool) Config::get('app.debug', false));
    }

    public static function run(): void
    {
        $request = Request::capture();
        $router = new Router();
        (require ROOT_PATH . '/config/routes.php')($router);
        $router->dispatch($request)->send();
    }
}
