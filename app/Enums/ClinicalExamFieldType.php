<?php

namespace App\Enums;

enum ClinicalExamFieldType: string
{
    case Boolean = 'boolean';
    case BooleanWithNote = 'boolean_with_note';
    case Text = 'text';
    case Number = 'number';

    public function usesPresent(): bool
    {
        return match ($this) {
            self::Boolean, self::BooleanWithNote => true,
            default => false,
        };
    }
}
