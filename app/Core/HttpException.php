<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class HttpException extends RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message);
    }

    public static function notFound(string $message = 'Página não encontrada.'): self
    {
        return new self(404, $message);
    }

    public static function forbidden(string $message = 'Você não tem permissão para acessar este conteúdo.'): self
    {
        return new self(403, $message);
    }

    public static function tooMany(string $message = 'Muitas tentativas. Aguarde alguns minutos.'): self
    {
        return new self(429, $message);
    }
}
