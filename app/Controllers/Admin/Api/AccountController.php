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
    public function password(): Response
    {
        $in = $this->request->json();
        $error = Auth::changePassword((int) Auth::id(), (string) ($in['current'] ?? ''), (string) ($in['password'] ?? ''));
        if ($error !== null) throw new HttpException(422, $error);
        // La session est régénérée : l'interface reçoit le nouveau jeton CSRF.
        return $this->json(['ok' => true, 'csrf' => Session::csrfToken()]);
    }
}
