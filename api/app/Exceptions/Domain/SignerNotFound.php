<?php

namespace App\Exceptions\Domain;

class SignerNotFound extends DomainException
{
    public function __construct(string $detail = 'No existe un firmante con ese identificador.')
    {
        parent::__construct($detail);
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'signer_not_found';
    }

    public function title(): string
    {
        return 'Firmante no encontrado';
    }
}
