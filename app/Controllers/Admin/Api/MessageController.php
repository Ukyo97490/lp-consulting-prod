<?php
declare(strict_types=1);

namespace App\Controllers\Admin\Api;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Message;

/** Demandes reçues par le formulaire de contact. */
final class MessageController extends Controller
{
    public function index(): Response
    {
        return $this->json(['messages' => Message::latest(), 'unread' => Message::unreadCount()]);
    }

    public function read(string $id): Response
    {
        Message::markRead((int) $id, !empty($this->request->json()['read']));
        return $this->json(['ok' => true, 'unread' => Message::unreadCount()]);
    }

    public function destroy(string $id): Response
    {
        Message::delete((int) $id);
        return $this->json(['ok' => true, 'unread' => Message::unreadCount()]);
    }
}
