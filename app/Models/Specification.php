<?php

namespace App\Models;

use App\Enums\SpecificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Specification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'audit_id',
        'reference',
        'title',
        'version',
        'status',
        'context',
        'objectives',
        'scope_in',
        'scope_out',
        'announced_days_min',
        'announced_days_max',
        'daily_rate',
        'currency',
        'starts_on',
        'valid_until',
        'include_in_pdf',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => SpecificationStatus::class,
            'starts_on' => 'date',
            'valid_until' => 'date',
            'include_in_pdf' => 'boolean',
            'announced_days_min' => 'integer',
            'announced_days_max' => 'integer',
            'daily_rate' => 'integer',
        ];
    }

    // ------------------------------------------------------------------
    // Relations
    // ------------------------------------------------------------------

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(SpecificationSection::class)->orderBy('position');
    }

    public function lots(): HasMany
    {
        return $this->hasMany(SpecificationLot::class)->orderBy('position');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ------------------------------------------------------------------
    // Charges
    // ------------------------------------------------------------------

    private function allLots(): Collection
    {
        return $this->relationLoaded('lots') ? $this->lots : $this->lots()->get();
    }

    /** Lots du périmètre de base : les options en sont exclues. */
    public function baseLots(): Collection
    {
        return $this->allLots()->reject(fn (SpecificationLot $lot) => $lot->is_option)->values();
    }

    public function optionLots(): Collection
    {
        return $this->allLots()->filter(fn (SpecificationLot $lot) => $lot->is_option)->values();
    }

    public function daysMin(): int
    {
        return (int) $this->baseLots()->sum('days_min');
    }

    public function daysMax(): int
    {
        return (int) $this->baseLots()->sum('days_max');
    }

    /**
     * Écart entre l'enveloppe annoncée et la somme des lots. Positif, c'est
     * une marge de cadrage ; négatif, le devis ne couvre pas le chantier
     * décrit — un signal qu'il vaut mieux voir avant de signer.
     */
    public function announcedMargin(): ?array
    {
        if ($this->announced_days_min === null && $this->announced_days_max === null) {
            return null;
        }

        return [
            'min' => ($this->announced_days_min ?? $this->daysMin()) - $this->daysMin(),
            'max' => ($this->announced_days_max ?? $this->daysMax()) - $this->daysMax(),
        ];
    }

    public function hasAnnouncedEnvelope(): bool
    {
        return $this->announced_days_min !== null || $this->announced_days_max !== null;
    }

    public function budgetRange(): ?array
    {
        if (! $this->daily_rate) {
            return null;
        }

        $min = ($this->announced_days_min ?? $this->daysMin()) * $this->daily_rate;
        $max = ($this->announced_days_max ?? $this->daysMax()) * $this->daily_rate;

        return ['min' => $min, 'max' => $max];
    }

    /**
     * Lots regroupés par phase, triés par nom de phase.
     *
     * Le tri est indispensable : groupBy conserve l'ordre de première
     * apparition, si bien qu'un lot de phase 3 placé avant un lot de phase 2
     * faisait sortir les phases dans le désordre.
     */
    public function lotsByPhase(): Collection
    {
        return $this->baseLots()
            ->groupBy(fn (SpecificationLot $lot) => $lot->phase ?: 'Non phasé')
            ->sortKeys()
            ->map(fn (Collection $lots) => [
                'lots' => $lots,
                'days_min' => (int) $lots->sum('days_min'),
                'days_max' => (int) $lots->sum('days_max'),
            ]);
    }

    public function risks(): Collection
    {
        return $this->allLots()->filter(fn (SpecificationLot $lot) => $lot->is_at_risk)->values();
    }

    public function pdfFilename(): string
    {
        return collect([
            'cahier-des-charges',
            $this->reference,
            \Illuminate\Support\Str::slug($this->audit?->client_name ?? ''),
        ])->filter()->implode('-').'.pdf';
    }

    public function formatBudget(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' '.($this->currency === 'EUR' ? '€' : $this->currency);
    }
}
