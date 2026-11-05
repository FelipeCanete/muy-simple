<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Pendiente = 'pendiente';
    case FirmadoParcial = 'firmado_parcial';
    case Completo = 'completo';

    /**
     * Regla de negocio central: el estado del documento se deriva
     * únicamente de cuántos firmantes ya firmaron.
     */
    public static function fromCounts(int $signed, int $total): self
    {
        if ($total < 1 || $signed < 0 || $signed > $total) {
            throw new \InvalidArgumentException(
                "Conteo de firmas inválido: {$signed} de {$total}."
            );
        }

        return match (true) {
            $signed === 0 => self::Pendiente,
            $signed < $total => self::FirmadoParcial,
            default => self::Completo,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'pendiente',
            self::FirmadoParcial => 'firmado parcial',
            self::Completo => 'completo',
        };
    }
}
