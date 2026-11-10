<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;

/** En-têtes de sécurité et de cache ajoutés à toutes les réponses. */
final class SecurityHeaders implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);
        $isAdmin = $request->path === '/admin' || str_starts_with($request->path, '/admin/') || $request->path === '/install';

        $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->withHeader('Content-Security-Policy', "default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline'; script-src 'self' 'sha256-Du+OJKJSbdUgz5nrHeWWINvez6XKDDU/tyj/5c2uvwo=' 'sha256-OXOVWKWWlPMXGi878Q70ZWIIeXRZ4/qha+u8zihY4OU='; "
                . "font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
        if ($request->isSecure()) $response->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        if ($isAdmin) {
            $response->withHeader('Cache-Control', 'no-store')->withHeader('X-Robots-Tag', 'noindex, nofollow');
        } elseif (!$response->hasHeader('Cache-Control')) {
            $response->withHeader('Cache-Control', $request->method === 'GET' && $response->status() === 200 ? 'public, max-age=300' : 'no-store');
        }
        return $response;
    }
}
