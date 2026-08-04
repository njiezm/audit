<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Auditor = 'auditor';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Auditor => 'Auditeur',
            self::Viewer => 'Lecture seule',
        };
    }

    public function canWrite(): bool
    {
        return $this !== self::Viewer;
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
