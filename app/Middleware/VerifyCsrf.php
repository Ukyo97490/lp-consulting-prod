<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;

/** Protection CSRF des actions de l'administration (en-tête X-CSRF-Token ou champ « csrf »). */
final class VerifyCsrf implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if ($request->isPost() && !Session::csrfValid($request->header('X-CSRF-Token') ?? $request->input('csrf'))) {
            return $request->wantsJson()
                ? Response::json(['error' => 'Jeton de sécurité invalide : rechargez la page.'], 419)
                : Response::redirect(Url::to('admin/login') . '?erreur=session', 303);
        }
        return $next($request);
    }
}
