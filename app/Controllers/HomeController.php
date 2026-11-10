<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Services\ContactForm;
use App\Services\SiteContent;

final class HomeController extends SiteController
{
    public function index(): Response
    {
        $home = SiteContent::content()['home'];
        $projects = SiteContent::projects(true);
        $status = $this->request->query('contact');

        return $this->page('home', [
            'title'       => $home['seo_title'],
            'description' => $home['seo_description'],
            'excerpt'     => $home['hero_lead'],
            'path'        => '',
            'nav'         => '',
            'jsonld'      => true,
        ], [
            'featured'      => array_slice(array_values(array_filter($projects, fn($p) => $p['featured'])), 0, 4),
            'contactStatus' => in_array($status, ['ok', 'erreur', 'limite'], true) ? $status : null,
            'contactToken'  => ContactForm::token(),
            'missions'      => ContactForm::missions(),
        ]);
    }
}
