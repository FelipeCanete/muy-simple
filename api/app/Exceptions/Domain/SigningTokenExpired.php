<?php

namespace App\Exceptions\Domain;

class SigningTokenExpired extends DomainException
{
    public function __construct(string $detail = 'El enlace de firma expiró. Solicita uno nuevo a quien te envió el documento.')
    {
        parent::__construct($detail);
    }

    public function status(): int
    {
        return 410;
    }

    public function errorCode(): string
    {
        return 'token_expired';
    }

    public function title(): string
    {
        return 'Enlace de firma expirado';
    }
}
