<?php

namespace App\Enums;

enum AuditStatus: string
{
    case Draft = 'draft';
    case Finalized = 'finalized';
    case Signed = 'signed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Finalized => 'Finalisé',
            self::Signed => 'Signé',
            self::Archived => 'Archivé',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'badge-status badge-status--draft',
            self::Finalized => 'badge-status badge-status--finalized',
            self::Signed => 'badge-status badge-status--signed',
            self::Archived => 'badge-status badge-status--archived',
        };
    }

    /** Un audit signé ou archivé est figé : plus aucune écriture sur son contenu. */
    public function isLocked(): bool
    {
        return in_array($this, [self::Signed, self::Archived], true);
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
