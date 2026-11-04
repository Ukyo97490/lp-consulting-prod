<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/** Erreur destinée au visiteur (message affichable) avec son code HTTP. */
class HttpException extends RuntimeException
{
    public function __construct(private int $status, string $message = '', private array $payload = [])
    {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
