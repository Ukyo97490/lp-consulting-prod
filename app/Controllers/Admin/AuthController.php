<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Session;
use App\Services\Auth;

final class AuthController extends Controller
{
    public function showLogin(): Response
    {
        if (Auth::user()) return $this->redirect('admin');
        $error = $this->request->query('erreur') === 'session' ? 'Session expirée, merci de réessayer.' : '';
        return $this->form($error);
    }

    public function login(): Response
    {
        $email = (string) $this->request->input('email', '');
        $error = Auth::attempt($email, (string) $this->request->input('password', ''), $this->request->ip());
        if ($error === null) return $this->redirect('admin', 303);
        return $this->form($error, $email, 401);
    }

    public function logout(): Response
    {
        Auth::logout();
        return $this->redirect('admin/login', 303);
    }

    private function form(string $error, string $email = '', int $status = 200): Response
    {
        return $this->view('admin/login', ['error' => $error, 'email' => $email, 'csrf' => Session::csrfToken()], 'layouts/admin-guest', $status);
    }
}
