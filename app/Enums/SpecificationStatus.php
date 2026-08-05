<?php

namespace App\Enums;

enum SpecificationStatus: string
{
    case Draft = 'draft';
    case Proposed = 'proposed';
    case Accepted = 'accepted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Proposed => 'Proposé au client',
            self::Accepted => 'Accepté',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'badge-status badge-status--draft',
            self::Proposed => 'badge-status badge-status--finalized',
            self::Accepted => 'badge-status badge-status--signed',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
