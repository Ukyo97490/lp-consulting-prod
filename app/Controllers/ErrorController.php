<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Response;

final class ErrorController extends SiteController
{
    public function notFound(): Response
    {
        if ($this->request->wantsJson()) return $this->json(['error' => 'Ressource introuvable.'], 404);
        $name = \App\Services\SiteContent::content()['site']['name'];
        return $this->page('404', ['title' => 'Page introuvable | ' . $name, 'path' => ltrim($this->request->path, '/'), 'noindex' => true], [], 404);
    }

    public function methodNotAllowed(): Response
    {
        if ($this->request->wantsJson()) return $this->json(['error' => 'Méthode non autorisée.'], 405);
        return $this->redirect(ltrim($this->request->path, '/'), 303);
    }

    public function fromException(HttpException $e): Response
    {
        if ($e->status() === 404) return $this->notFound();
        return $this->view('pages/error', ['detail' => null, 'message' => $e->getMessage()], null, $e->status());
    }
}
