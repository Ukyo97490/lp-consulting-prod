<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Services\SiteContent;

final class ReferenceController extends SiteController
{
    public function index(): Response
    {
        $r = SiteContent::content()['references'];
        $projects = SiteContent::projects(true);
        $groups = [];
        foreach (['etudes' => $r['group_etudes'], 'travaux' => $r['group_travaux']] as $category => $title) {
            $list = array_values(array_filter($projects, fn($p) => $p['category'] === $category));
            if ($list) $groups[] = ['title' => $title, 'projects' => $list];
        }

        return $this->page('references', [
            'title'       => $r['seo_title'],
            'description' => $r['seo_description'],
            'path'        => 'references',
            'nav'         => 'references',
        ], ['groups' => $groups]);
    }
}
