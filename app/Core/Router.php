<?php
declare(strict_types=1);

namespace App\Core;

use App\Controllers\ErrorController;

/**
 * Routeur : associe une méthode HTTP et un chemin à une action de contrôleur.
 * Paramètres : /messages/{id:\d+}/read  (expression régulière facultative après « : »).
 * Les middlewares globaux s'exécutent pour toute requête, ceux d'une route uniquement pour elle.
 */
final class Router
{
    private array $routes = [];
    private array $global = [];
    private array $aliases = [];

    public function aliases(array $aliases): void
    {
        $this->aliases = $aliases;
    }

    public function middleware(array $middleware): void
    {
        $this->global = $middleware;
    }

    public function get(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add(['GET', 'HEAD'], $pattern, $handler, $middleware);
    }

    public function post(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add(['POST'], $pattern, $handler, $middleware);
    }

    public function add(array $methods, string $pattern, array $handler, array $middleware = []): void
    {
        $regex = preg_replace_callback('#\{(\w+)(?::([^}]+))?\}#', fn($m) => '(?P<' . $m[1] . '>' . ($m[2] ?? '[^/]+') . ')', $pattern);
        $this->routes[] = ['methods' => $methods, 'regex' => '#^' . $regex . '$#', 'handler' => $handler, 'middleware' => $middleware];
    }

    public function dispatch(Request $request): Response
    {
        return $this->pipeline($this->global, $request, fn(Request $r) => $this->match($r));
    }

    private function match(Request $request): Response
    {
        $allowed = false;
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path, $m)) continue;
            if (!in_array($request->method, $route['methods'], true)) {
                $allowed = true;
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            return $this->pipeline($route['middleware'], $request, fn(Request $r) => $this->call($route['handler'], $r, $params));
        }
        if ($allowed) return $this->call([ErrorController::class, 'methodNotAllowed'], $request, []);
        return $this->call([ErrorController::class, 'notFound'], $request, []);
    }

    private function call(array $handler, Request $request, array $params): Response
    {
        [$class, $method] = $handler;
        try {
            return (new $class($request))->$method(...array_values($params));
        } catch (HttpException $e) {
            if ($request->wantsJson()) return Response::json(['error' => $e->getMessage()] + $e->payload(), $e->status());
            return (new ErrorController($request))->fromException($e);
        }
    }

    private function pipeline(array $middleware, Request $request, callable $core): Response
    {
        $next = $core;
        foreach (array_reverse($middleware) as $name) {
            $class = $this->aliases[$name] ?? $name;
            $next = fn(Request $r) => (new $class())->handle($r, $next);
        }
        return $next($request);
    }
}
