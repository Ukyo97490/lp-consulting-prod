<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Url;

/**
 * Adresses propres, sans extension : redirection permanente (301) des anciennes adresses
 * (/references.html, /index.php, /etudes-techniques/…) vers leur forme canonique.
 */
final class CanonicalUrl implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (in_array($request->method, ['GET', 'HEAD'], true)) {
            $path = trim($request->path, '/');
            $qs = $request->queryString() !== '' ? '?' . $request->queryString() : '';

            if (in_array($path, ['index', 'index.html', 'index.php'], true)) {
                return Response::redirect(Url::to('') . $qs, 301);
            }
            if (preg_match('#^([a-z0-9/-]+)\.(html|php)$#', $path, $m)) {
                return Response::redirect(Url::to($m[1]) . $qs, 301);
            }
            if ($path !== '' && str_ends_with($request->uri, '/')) {
                return Response::redirect(Url::to($path) . $qs, 301);
            }
        }
        return $next($request);
    }
}
