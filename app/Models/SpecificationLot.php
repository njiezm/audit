<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecificationLot extends Model
{
    protected $fillable = [
        'position',
        'code',
        'name',
        'content',
        'phase',
        'days_min',
        'days_max',
        'is_option',
        'is_at_risk',
        'risk_note',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'days_min' => 'integer',
            'days_max' => 'integer',
            'is_option' => 'boolean',
            'is_at_risk' => 'boolean',
        ];
    }

    public function specification(): BelongsTo
    {
        return $this->belongsTo(Specification::class);
    }

    /**
     * Le code du lot, ou un tiret. `?:` ne convient pas : le lot « 0 » est
     * un intitulé légitime que PHP considère comme vide.
     */
    public function codeLabel(): string
    {
        return $this->code === null || $this->code === '' ? '—' : $this->code;
    }

    /** « 4 – 6 j », ou « 4 j » quand la fourchette est fermée. */
    public function daysLabel(): string
    {
        if ($this->days_min === 0 && $this->days_max === 0) {
            return 'à chiffrer';
        }

        return $this->days_min === $this->days_max
            ? $this->days_min.' j'
            : $this->days_min.' – '.$this->days_max.' j';
    }
}
