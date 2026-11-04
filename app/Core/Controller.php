<?php
declare(strict_types=1);

namespace App\Core;

/** Contrôleur de base : accès à la requête et fabrication des réponses. */
abstract class Controller
{
    public function __construct(protected Request $request)
    {
    }

    protected function view(string $template, array $data = [], ?string $layout = 'layouts/site', int $status = 200): Response
    {
        return Response::html(View::render($template, $data, $layout), $status);
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $path, int $status = 302): Response
    {
        return Response::redirect(preg_match('#^https?://#', $path) ? $path : Url::to($path), $status);
    }
}
