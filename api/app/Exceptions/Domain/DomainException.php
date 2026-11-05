<?php

namespace App\Exceptions\Domain;

use RuntimeException;

/**
 * Error de negocio esperable. Se transforma en una respuesta
 * application/problem+json (RFC 9457) en bootstrap/app.php.
 */
abstract class DomainException extends RuntimeException
{
    /** Código HTTP de la respuesta. */
    abstract public function status(): int;

    /** Identificador estable que el front usa para elegir el mensaje. */
    abstract public function errorCode(): string;

    /** Título corto y legible. */
    abstract public function title(): string;
}
