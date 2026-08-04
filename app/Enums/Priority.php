<?php

namespace App\Enums;

enum Priority: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Critique',
            self::High => 'Élevée',
            self::Medium => 'Moyenne',
            self::Low => 'Faible',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Critical => '#b3001b',
            self::High => '#e8590c',
            self::Medium => '#b58100',
            self::Low => '#2f6f4f',
        };
    }

    /** Ordre de tri du plan d'action : le plus urgent d'abord. */
    public function rank(): int
    {
        return match ($this) {
            self::Critical => 0,
            self::High => 1,
            self::Medium => 2,
            self::Low => 3,
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
