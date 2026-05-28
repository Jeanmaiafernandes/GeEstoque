<?php

namespace App\Enums;

enum ItemType: string
{
    case MateriaPrima = 'materia_prima';
    case Consumivel = 'consumivel';

    public function label(): string
    {
        return match ($this) {
            self::MateriaPrima => 'Matéria-prima',
            self::Consumivel => 'Consumível/Ferramenta',
        };
    }

    /**
     * @return array<string, string> value => label
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
