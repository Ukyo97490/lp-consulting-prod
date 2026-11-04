<?php
declare(strict_types=1);

namespace App\Controllers\Admin\Api;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Response;
use App\Services\Auth;
use App\Services\ContentSchema;
use App\Services\SiteContent;

final class ProjectController extends Controller
{
    /** Remplace la liste complète des projets (ajout, modification, ordre, suppression). */
    public function update(): Response
    {
        $in = $this->request->json();
        if (!isset($in['projects']) || !is_array($in['projects'])) throw new HttpException(422, 'Liste de projets manquante.');
        $label = ContentSchema::cleanText($in['label'] ?? '', 120) ?: 'Projets modifiés';
        return $this->json(['ok' => true, 'projects' => SiteContent::saveProjects($in['projects'], Auth::id(), $label)]);
    }
}
