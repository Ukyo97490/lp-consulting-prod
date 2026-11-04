<?php
declare(strict_types=1);

namespace App\Controllers\Admin\Api;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Response;
use App\Models\Revision;
use App\Services\Auth;
use App\Services\SiteContent;

/** Historique des versions et restauration. */
final class RevisionController extends Controller
{
    public function index(): Response
    {
        return $this->json(['revisions' => Revision::latest(SiteContent::MAX_REVISIONS)]);
    }

    public function restore(string $id): Response
    {
        if (!SiteContent::restore((int) $id, Auth::id())) throw new HttpException(404, 'Version introuvable.');
        return $this->json(['ok' => true, 'content' => SiteContent::content(), 'projects' => SiteContent::projects()]);
    }
}
