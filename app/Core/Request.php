<?php
declare(strict_types=1);

namespace App\Core;

/** Requête HTTP en cours (chemin relatif au dossier d'installation, données, fichiers). */
final class Request
{
    private ?array $json = null;
    private array $attributes = [];

    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly string $uri,
        private array $query,
        private array $post,
        private array $files,
        private array $server,
    ) {
    }

    public static function capture(): self
    {
        $uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $base = Url::detectBase($uri);
        $path = $base !== '' && str_starts_with($uri, $base) ? substr($uri, strlen($base)) : $uri;
        return new self(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), '/' . trim($path, '/'), $uri, $_GET, $_POST, $_FILES, $_SERVER);
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function queryString(): string
    {
        return (string) ($this->server['QUERY_STRING'] ?? '');
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->post;
    }

    /** Corps JSON (5 Mo max). */
    public function json(): array
    {
        if ($this->json === null) {
            $raw = file_get_contents('php://input', false, null, 0, 5 * 1024 * 1024);
            $data = json_decode((string) $raw, true);
            if (!is_array($data)) throw new HttpException(400, 'Requête invalide.');
            $this->json = $data;
        }
        return $this->json;
    }

    public function file(string $key): ?array
    {
        return isset($this->files[$key]) && is_array($this->files[$key]) ? $this->files[$key] : null;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return isset($this->server[$key]) ? (string) $this->server[$key] : null;
    }

    public function ip(): string
    {
        $ip = $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    public function userAgent(): string
    {
        return mb_substr((string) ($this->server['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function wantsJson(): bool
    {
        return str_contains($this->header('Accept') ?? '', 'application/json') || str_starts_with($this->path, '/admin/api/');
    }

    public function isSecure(): bool
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off')
            || ($this->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
