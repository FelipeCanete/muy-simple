<?php

namespace App\Exceptions\Domain;

class SignerAlreadySigned extends DomainException
{
    public function __construct(string $detail = 'Este firmante ya firmó el documento.')
    {
        parent::__construct($detail);
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'already_signed';
    }

    public function title(): string
    {
        return 'Documento ya firmado';
    }
}
