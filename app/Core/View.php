<?php
declare(strict_types=1);

namespace App\Core;

/** Moteur de gabarits PHP : app/Views/{template}.php, avec mise en page facultative ($content). */
final class View
{
    public static function render(string $template, array $data = [], ?string $layout = null): string
    {
        $content = self::capture($template, $data);
        return $layout === null ? $content : self::capture($layout, $data + ['content' => $content]);
    }

    public static function capture(string $template, array $data = []): string
    {
        $file = APP_PATH . '/Views/' . $template . '.php';
        if (!is_file($file)) throw new \RuntimeException("Gabarit introuvable : $template");
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
}
