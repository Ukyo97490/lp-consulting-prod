<?php
declare(strict_types=1);

namespace App\Controllers\Admin\Api;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Response;
use App\Services\Auth;
use App\Services\ContentSchema;
use App\Services\SiteContent;

final class ContentController extends Controller
{
    public function update(): Response
    {
        $in = $this->request->json();
        if (!isset($in['content']) || !is_array($in['content'])) throw new HttpException(422, 'Contenu manquant.');
        $label = ContentSchema::cleanText($in['label'] ?? '', 120) ?: 'Textes modifiés';
        return $this->json(['ok' => true, 'content' => SiteContent::saveContent($in['content'], Auth::id(), $label)]);
    }
}
