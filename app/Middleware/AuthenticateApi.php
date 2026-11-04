<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Url;
use App\Services\Auth;

/** API de l'administration : connexion obligatoire (sinon erreur 401 en JSON). */
final class AuthenticateApi implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::user()) return Response::json(['error' => 'Session expirée : reconnectez-vous.', 'login' => Url::to('admin/login')], 401);
        return $next($request);
    }
}
