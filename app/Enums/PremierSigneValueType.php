<?php

namespace App\Enums;

enum PremierSigneValueType: string
{
    case Age = 'age';
    case Quantity = 'quantity';

    public function defaultLabel(): string
    {
        return match ($this) {
            self::Age => 'Âge',
            self::Quantity => 'Nombre',
        };
    }

    public function unit(): string
    {
        return match ($this) {
            self::Age => 'ans',
            self::Quantity => '',
        };
    }
}
