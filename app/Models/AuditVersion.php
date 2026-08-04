<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditVersion extends Model
{
    protected $fillable = [
        'audit_id',
        'version',
        'snapshot',
        'content_hash',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'version' => 'integer',
        ];
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
