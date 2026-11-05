<?php

namespace App\Exceptions\Domain;

class InvalidCredentials extends DomainException
{
    public function __construct(string $detail = 'El email o la contraseña no son correctos.')
    {
        parent::__construct($detail);
    }

    public function status(): int
    {
        return 401;
    }

    public function errorCode(): string
    {
        return 'invalid_credentials';
    }

    public function title(): string
    {
        return 'Credenciales inválidas';
    }
}
