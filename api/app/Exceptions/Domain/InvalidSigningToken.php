<?php

namespace App\Exceptions\Domain;

class InvalidSigningToken extends DomainException
{
    public function __construct(string $detail = 'El enlace de firma no existe o fue mal copiado.')
    {
        parent::__construct($detail);
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'invalid_token';
    }

    public function title(): string
    {
        return 'Enlace de firma inválido';
    }
}
