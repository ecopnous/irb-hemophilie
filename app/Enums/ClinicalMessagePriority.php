<?php

namespace App\Enums;

enum ClinicalMessagePriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Faible',
            self::Normal => 'Normal',
            self::High => 'Haute',
            self::Urgent => 'Urgent',
            self::Critical => 'Critique',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Low => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
            self::Normal => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
            self::High => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
            self::Urgent, self::Critical => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
        };
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $priority) => ['label' => $priority->label(), 'value' => $priority->value])
            ->all();
    }
}
