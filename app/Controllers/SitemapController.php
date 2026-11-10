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
        // Date la plus récente entre le dernier enregistrement du contenu et la dernière mise en ligne des gabarits ou du style.
        $last = Revision::lastDate();
        $times = [$last ? (int) strtotime($last) : 0];
        $base = dirname(__DIR__, 2);
        foreach (array_merge(glob($base . '/app/Views/*/*.php') ?: [], [$base . '/public/css/style.css']) as $file) {
            $times[] = (int) @filemtime($file);
        }
        $lastmod = max($times);
        return Response::xml(View::render('pages/sitemap', [
            'paths'   => array_merge([''], ContentSchema::MISSIONS, ['references', 'mentions-legales']),
            'lastmod' => date('Y-m-d', $lastmod ?: time()),
        ]));
    }
}
