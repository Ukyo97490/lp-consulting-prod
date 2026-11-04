<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Response;
use App\Services\Auth;
use App\Services\Installer;
use InvalidArgumentException;

/**
 * Installation par le navigateur (/install?token=…), pour les hébergements sans accès SSH.
 * Désactivée par défaut ; refusée dès qu'un compte administrateur existe.
 */
final class InstallController extends Controller
{
    public function index(): Response
    {
        $token = (string) Config::get('install.token', '');
        $given = (string) ($this->request->query('token') ?? $this->request->input('token', ''));
        if (!Config::get('install.enabled') || strlen($token) < 24 || !hash_equals($token, $given)) {
            // Réponse neutre, sans dépendre de la base (qui n'existe peut-être pas encore).
            return Response::html('Not found', 404);
        }

        if (Installer::adminCount() > 0) return $this->view('admin/install', ['state' => 'done'], 'layouts/admin-guest');

        $error = '';
        if ($this->request->isPost()) {
            try {
                $log = Installer::run();
                Auth::createAdmin((string) $this->request->input('email'), (string) $this->request->input('name'), (string) $this->request->input('password'));
                return $this->view('admin/install', ['state' => 'installed', 'log' => $log], 'layouts/admin-guest');
            } catch (InvalidArgumentException $e) {
                $error = $e->getMessage();
            }
        }
        return $this->view('admin/install', [
            'state' => 'form',
            'error' => $error,
            'token' => $given,
            'name'  => (string) $this->request->input('name', ''),
            'email' => (string) $this->request->input('email', ''),
        ], 'layouts/admin-guest');
    }
}
