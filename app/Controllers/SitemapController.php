<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\View;
use App\Models\Revision;
use App\Services\ContentSchema;

final class SitemapController extends Controller
{
    public function index(): Response
    {
        $last = Revision::lastDate();
        return Response::xml(View::render('pages/sitemap', [
            'paths'   => array_merge([''], ContentSchema::MISSIONS, ['references']),
            'lastmod' => date('Y-m-d', $last ? strtotime($last) : time()),
        ]));
    }
}
