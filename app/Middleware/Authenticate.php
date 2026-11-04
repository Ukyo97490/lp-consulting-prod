<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Url;
use App\Services\Auth;

/** Pages de l'administration : connexion obligatoire (sinon renvoi vers le formulaire). */
final class Authenticate implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::user()) return Response::redirect(Url::to('admin/login'));
        return $next($request);
    }
}
