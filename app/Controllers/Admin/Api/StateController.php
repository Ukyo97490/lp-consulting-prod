<?php
declare(strict_types=1);

namespace App\Controllers\Admin\Api;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Message;
use App\Services\Auth;
use App\Services\ImageLibrary;
use App\Services\SiteContent;

/** Données chargées au démarrage de l'administration. */
final class StateController extends Controller
{
    public function show(): Response
    {
        return $this->json([
            'content'  => SiteContent::content(),
            'projects' => SiteContent::projects(),
            'library'  => ImageLibrary::all(),
            'unread'   => Message::unreadCount(),
            'user'     => Auth::user(),
        ]);
    }
}
