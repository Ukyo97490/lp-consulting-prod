<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Services\SiteContent;

final class LegalController extends SiteController
{
    public function index(): Response
    {
        $name = SiteContent::content()['site']['name'];

        return $this->page('legal', [
            'title'       => 'Mentions légales | ' . $name,
            'description' => 'Mentions légales et politique de confidentialité du site ' . $name . '.',
            'path'        => 'mentions-legales',
            'noindex'     => true,
        ]);
    }
}
