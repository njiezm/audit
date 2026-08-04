<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_default',
        'created_by',
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(AuditTemplateCategory::class)->orderBy('position');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Forme attendue par l'éditeur : mêmes clés que les catégories d'audit. */
    public function toCategoryPayload(): array
    {
        return $this->categories->map(fn (AuditTemplateCategory $c) => [
            'title' => $c->title,
            'score' => 3,
            'weight' => $c->weight,
            'observations' => '',
            'recommendations' => '',
            'priority' => '',
            'due_on' => '',
            'owner' => '',
            'hint' => $c->hint,
        ])->all();
    }
}
