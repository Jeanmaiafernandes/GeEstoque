<?php

namespace App\Enums;

enum InventoryStatus: string
{
    case Aberto = 'aberto';
    case Finalizado = 'finalizado';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Aberto => 'Em contagem',
            self::Finalizado => 'Finalizado',
            self::Cancelado => 'Cancelado',
        };
    }
}
