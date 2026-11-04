<?php /** @var string[] $paths  @var string $lastmod */ ?>
<?= '<?xml version="1.0" encoding="UTF-8"?>' ?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($paths as $path): ?>
  <url><loc><?= e(abs_url($path)) ?></loc><lastmod><?= e($lastmod) ?></lastmod></url>
<?php endforeach ?>
</urlset>
