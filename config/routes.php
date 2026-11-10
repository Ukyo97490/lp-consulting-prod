<?php
/* LP CONSULTING : table des routes (adresses sans extension). */
declare(strict_types=1);

use App\Controllers\Admin;
use App\Controllers\Admin\Api;
use App\Controllers\ContactController;
use App\Controllers\HomeController;
use App\Controllers\InstallController;
use App\Controllers\LegalController;
use App\Controllers\MissionController;
use App\Controllers\ReferenceController;
use App\Controllers\SitemapController;
use App\Core\Router;
use App\Middleware;

return function (Router $r): void {
    $r->aliases([
        'auth'     => Middleware\Authenticate::class,
        'auth.api' => Middleware\AuthenticateApi::class,
        'csrf'     => Middleware\VerifyCsrf::class,
    ]);
    // Exécutés pour toutes les requêtes (du premier au dernier).
    $r->middleware([Middleware\SecurityHeaders::class, Middleware\CanonicalUrl::class]);

    /* ---------- Site public ---------- */
    $r->get('/', [HomeController::class, 'index']);
    $r->get('/{slug:etudes-techniques|preparation-chantier|direction-travaux}', [MissionController::class, 'show']);
    $r->get('/references', [ReferenceController::class, 'index']);
    $r->get('/mentions-legales', [LegalController::class, 'index']);
    $r->get('/contact', [ContactController::class, 'show']);
    $r->post('/contact', [ContactController::class, 'send']);
    $r->get('/sitemap.xml', [SitemapController::class, 'index']);

    /* ---------- Administration ---------- */
    $r->get('/admin/login', [Admin\AuthController::class, 'showLogin']);
    $r->post('/admin/login', [Admin\AuthController::class, 'login'], ['csrf']);
    $r->post('/admin/logout', [Admin\AuthController::class, 'logout'], ['csrf']);
    $r->get('/admin', [Admin\DashboardController::class, 'index'], ['auth']);

    /* ---------- API de l'administration (JSON) ---------- */
    $api = ['auth.api', 'csrf'];
    $r->get('/admin/api/state', [Api\StateController::class, 'show'], $api);
    $r->post('/admin/api/content', [Api\ContentController::class, 'update'], $api);
    $r->post('/admin/api/projects', [Api\ProjectController::class, 'update'], $api);
    $r->post('/admin/api/upload', [Api\UploadController::class, 'store'], $api);
    $r->post('/admin/api/tools/minify', [Api\ToolController::class, 'minify'], $api);
    $r->post('/admin/api/tools/images', [Api\ToolController::class, 'images'], $api);
    $r->get('/admin/api/messages', [Api\MessageController::class, 'index'], $api);
    $r->post('/admin/api/messages/{id:\d+}/read', [Api\MessageController::class, 'read'], $api);
    $r->post('/admin/api/messages/{id:\d+}/delete', [Api\MessageController::class, 'destroy'], $api);
    $r->get('/admin/api/revisions', [Api\RevisionController::class, 'index'], $api);
    $r->post('/admin/api/revisions/{id:\d+}/restore', [Api\RevisionController::class, 'restore'], $api);
    $r->post('/admin/api/account/profile', [Api\AccountController::class, 'profile'], $api);
    $r->post('/admin/api/account/password', [Api\AccountController::class, 'password'], $api);

    /* ---------- Installation (désactivée par défaut) ---------- */
    $r->add(['GET', 'POST'], '/install', [InstallController::class, 'index']);
};
