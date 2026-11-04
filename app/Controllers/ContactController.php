<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Url;
use App\Services\ContactForm;

/** Réception du formulaire : réponse JSON (envoi en JavaScript) ou redirection (sans JavaScript). */
final class ContactController extends Controller
{
    public function send(): Response
    {
        $result = ContactForm::submit($this->request->all(), $this->request->ip(), $this->request->userAgent());

        if ($this->request->wantsJson()) {
            $status = match ($result['status']) { 'ok' => 200, 'limite' => 429, default => 422 };
            return $this->json($result, $status);
        }
        return Response::redirect(Url::to('') . '?contact=' . $result['status'] . '#contact', 303);
    }

    public function show(): Response
    {
        return Response::redirect(Url::to('') . '#contact');
    }
}
