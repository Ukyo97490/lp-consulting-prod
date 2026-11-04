<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\SiteContent;

/** Base des pages publiques : contenus communs (en-tête, pied de page) et métadonnées SEO. */
abstract class SiteController extends Controller
{
    /**
     * @param array $meta title, description, path (canonique), nav (menu actif), noindex, jsonld
     */
    protected function page(string $template, array $meta, array $data = [], int $status = 200): Response
    {
        $content = SiteContent::content();
        $meta += ['description' => '', 'path' => '', 'nav' => null, 'noindex' => false, 'jsonld' => false];
        return $this->view('pages/' . $template, $data + ['c' => $content, 'meta' => $meta], 'layouts/site', $status);
    }
}
