<?php
/* LP CONSULTING : crée les copies WebP (pleine taille et variantes 320 à 1600 px pour srcset)
   et enregistre les dimensions des images déjà en ligne.
   Usage : php outils/images.php            (seulement les fichiers manquants ou périmés)
           php outils/images.php --force    (régénère tout)
   Même traitement que le bouton Maintenance du tableau de bord ; les images envoyées depuis
   l'administration sont traitées automatiquement. */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
require dirname(__DIR__) . '/app/bootstrap.php';

$result = App\Services\ImageOptimizer::convertAll(in_array('--force', $argv, true));
echo "- {$result['images']} images traitées, {$result['webp']} avec au moins un fichier WebP créé.\n";

// Icône d'onglet 48 x 48 tirée du logo (logo-mark.png pèse 47 Ko, trop lourd pour une icône).
$src = PUBLIC_PATH . '/img/logo-mark.png';
$dst = PUBLIC_PATH . '/img/logo-favicon.png';
if (is_file($src) && (!is_file($dst) || filemtime($dst) < filemtime($src) || in_array('--force', $argv, true))) {
    $img = imagecreatefrompng($src);
    [$w, $h] = [imagesx($img), imagesy($img)];
    $scale = 48 / max($w, $h);
    [$nw, $nh] = [(int) round($w * $scale), (int) round($h * $scale)];
    $out = imagecreatetruecolor(48, 48);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
    imagecopyresampled($out, $img, (int) ((48 - $nw) / 2), (int) ((48 - $nh) / 2), 0, 0, $nw, $nh, $w, $h);
    imagepng($out, $dst, 9);
    echo '- img/logo-favicon.png : ' . filesize($dst) . " octets\n";
}
