<?php

namespace App\Services;

use App\Models\Document;

/**
 * Resultado de crear un documento. Los tokens en claro solo existen en
 * memoria durante esta respuesta: en la BD se guarda únicamente su hash.
 */
final readonly class CreatedDocument
{
    /**
     * @param  array<string, string>  $plainTokens  signer_id => token
     */
    public function __construct(
        public Document $document,
        public array $plainTokens,
    ) {}
}
