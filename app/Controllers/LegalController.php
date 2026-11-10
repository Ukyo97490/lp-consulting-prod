<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Services\SiteContent;

final class LegalController extends SiteController
{
    public function index(): Response
    {
        $legal = SiteContent::content()['legal'];

        return $this->page('legal', [
            'title'       => $legal['seo_title'],
            'description' => $legal['seo_description'],
            'excerpt'     => 'Mentions légales et politique de confidentialité du site ' . $legal['company'] . '.',
            'path'        => 'mentions-legales',
        ]);
    }
}
