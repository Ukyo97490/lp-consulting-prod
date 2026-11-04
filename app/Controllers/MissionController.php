<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Services\ContentSchema;
use App\Services\SiteContent;

final class MissionController extends SiteController
{
    public function show(string $slug): Response
    {
        $c = SiteContent::content();
        $mission = $c['missions'][$slug];

        return $this->page('mission', [
            'title'       => $mission['seo_title'] . ' | ' . $c['site']['name'],
            'description' => $mission['seo_description'],
            'path'        => $slug,
            'nav'         => $slug,
        ], [
            'slug'    => $slug,
            'mission' => $mission,
            'others'  => array_values(array_diff(ContentSchema::MISSIONS, [$slug])),
        ]);
    }
}
