<?php

namespace App\Models;

use App\Enums\Priority;
use App\Support\ScoreScale;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditCategory extends Model
{
    use HasFactory;

    /**
     * `audit_id` est volontairement absent : la clé étrangère est posée par
     * la relation hasMany, jamais par une donnée venant du formulaire.
     */
    protected $fillable = [
        'position',
        'title',
        'score',
        'weight',
        'observations',
        'recommendations',
        'priority',
        'due_on',
        'owner',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'weight' => 'integer',
            'position' => 'integer',
            'due_on' => 'date',
            'priority' => Priority::class,
        ];
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function getScoreLabelAttribute(): string
    {
        return ScoreScale::label($this->score);
    }

    public function getScoreColorAttribute(): string
    {
        return ScoreScale::color($this->score);
    }

    public function getScorePercentAttribute(): int
    {
        return (int) round(($this->score / ScoreScale::MAX) * 100);
    }

    public function isOverdue(): bool
    {
        return $this->due_on !== null && $this->due_on->isPast();
    }
}
