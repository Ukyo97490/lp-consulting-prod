<?php
declare(strict_types=1);

namespace App\Controllers\Admin\Api;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Response;
use App\Services\ImageOptimizer;
use App\Services\Minifier;
use RuntimeException;

/** Outils de maintenance du tableau de bord (réservés aux administrateurs connectés). */
final class ToolController extends Controller
{
    public function minify(): Response
    {
        try {
            return $this->json(['ok' => true, 'files' => Minifier::run()]);
        } catch (RuntimeException $e) {
            throw new HttpException(500, $e->getMessage() . ' (droits des dossiers css et js).');
        }
    }

    public function images(): Response
    {
        if (!ImageOptimizer::supported()) throw new HttpException(422, 'WebP indisponible : la fonction imagewebp manque dans PHP.');
        @set_time_limit(180);
        return $this->json(['ok' => true] + ImageOptimizer::convertAll());
    }
}
