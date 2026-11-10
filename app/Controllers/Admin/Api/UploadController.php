<?php
declare(strict_types=1);

namespace App\Controllers\Admin\Api;

use App\Core\Controller;
use App\Core\Response;
use App\Services\ImageLibrary;
use App\Services\ImageOptimizer;

final class UploadController extends Controller
{
    public function store(): Response
    {
        $path = ImageLibrary::store($this->request->file('image'));
        ImageOptimizer::process($path);
        return $this->json(['ok' => true, 'path' => $path, 'library' => ImageLibrary::all()]);
    }
}
