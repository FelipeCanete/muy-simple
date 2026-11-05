<?php

namespace App\Exceptions\Domain;

class InvalidWebhookSignature extends DomainException
{
    public function __construct(string $detail = 'La firma o el timestamp del webhook no son válidos.')
    {
        parent::__construct($detail);
    }

    public function status(): int
    {
        return 401;
    }

    public function errorCode(): string
    {
        return 'invalid_webhook_signature';
    }

    public function title(): string
    {
        return 'Firma de webhook inválida';
    }
}
