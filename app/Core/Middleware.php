<?php
declare(strict_types=1);

namespace App\Core;

/** Traitement exécuté avant / après une action ($next transmet la requête à la suite de la chaîne). */
interface Middleware
{
    public function handle(Request $request, callable $next): Response;
}
