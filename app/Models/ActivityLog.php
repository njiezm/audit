<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    protected $fillable = [
        'subject_type',
        'subject_id',
        'user_id',
        'user_name',
        'event',
        'description',
        'properties',
        'ip_address',
    ];

    protected function casts(): array
    {
        return ['properties' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        string $event,
        ?Model $subject = null,
        ?string $description = null,
        array $properties = [],
    ): self {
        $user = Auth::user();

        return static::create([
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'event' => $event,
            'description' => $description,
            'properties' => $properties ?: null,
            'ip_address' => request()->ip(),
        ]);
    }

    public function icon(): string
    {
        return match ($this->event) {
            'created' => '＋',
            'updated' => '✎',
            'signed' => '✓',
            'unsigned' => '⟲',
            'countersigned' => '✓✓',
            'deleted' => '🗑',
            'restored' => '↺',
            'downloaded' => '⇩',
            'sent' => '✉',
            'duplicated' => '⧉',
            'archived' => '📦',
            default => '•',
        };
    }

    public function label(): string
    {
        return match ($this->event) {
            'created' => 'Audit créé',
            'updated' => 'Audit modifié',
            'signed' => 'Audit signé',
            'unsigned' => 'Signature retirée',
            'countersigned' => 'Contre-signé par le client',
            'deleted' => 'Audit supprimé',
            'restored' => 'Audit restauré',
            'downloaded' => 'PDF téléchargé',
            'sent' => 'Rapport envoyé',
            'duplicated' => 'Audit dupliqué',
            'archived' => 'Audit archivé',
            'login' => 'Connexion',
            'login_failed' => 'Échec de connexion',
            'logout' => 'Déconnexion',
            default => ucfirst($this->event),
        };
    }
}
