<?php
declare(strict_types=1);

namespace App\Controllers\Admin\Api;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Session;
use App\Services\Auth;

final class AccountController extends Controller
{
    public function profile(): Response
    {
        $in = $this->request->json();
        $error = Auth::updateProfile((int) Auth::id(), (string) ($in['name'] ?? ''), (string) ($in['email'] ?? ''), (string) ($in['current'] ?? ''));
        if ($error !== null) throw new HttpException(422, $error);
        // La session est régénérée si l'e-mail change : l'interface reçoit le jeton CSRF à jour.
        return $this->json(['ok' => true, 'user' => Auth::user(), 'csrf' => Session::csrfToken()]);
    }

    public function password(): Response
    {
        $in = $this->request->json();
        $error = Auth::changePassword((int) Auth::id(), (string) ($in['current'] ?? ''), (string) ($in['password'] ?? ''));
        if ($error !== null) throw new HttpException(422, $error);
        // La session est régénérée : l'interface reçoit le nouveau jeton CSRF.
        return $this->json(['ok' => true, 'csrf' => Session::csrfToken()]);
    }
}
