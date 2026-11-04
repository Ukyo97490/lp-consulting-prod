<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Session;
use App\Services\Auth;

/** Interface de l'administration (application JavaScript alimentée par l'API). */
final class DashboardController extends Controller
{
    public function index(): Response
    {
        return $this->view('admin/app', ['user' => Auth::user(), 'csrf' => Session::csrfToken()], 'layouts/admin');
    }
}
