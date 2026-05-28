<?php

namespace App\Enums;

enum RequisitionStatus: string
{
    case Pendente = 'pendente';
    case Atendida = 'atendida';
    case Recusada = 'recusada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Atendida => 'Atendida',
            self::Recusada => 'Recusada',
            self::Cancelada => 'Cancelada',
        };
    }
}
