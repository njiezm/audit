<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditTemplateCategory extends Model
{
    protected $fillable = [
        'position',
        'title',
        'weight',
        'hint',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'weight' => 'integer',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AuditTemplate::class, 'audit_template_id');
    }
}
