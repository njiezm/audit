<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecificationSection extends Model
{
    protected $fillable = [
        'position',
        'title',
        'body',
        'page_break_before',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'page_break_before' => 'boolean',
        ];
    }

    public function specification(): BelongsTo
    {
        return $this->belongsTo(Specification::class);
    }
}
