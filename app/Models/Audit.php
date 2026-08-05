<?php

namespace App\Models;

use App\Enums\AuditStatus;
use App\Enums\Priority;
use App\Support\ScoreScale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Audit extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'user_id',
        'created_by',
        'updated_by',
        'client_name',
        'reference',
        'title',
        'status',
        'scoring_mode',
        'watermark',
        'global_score',
        'audit_date',
        'follow_up_on',
        'conclusion',
        'is_signed',
        'signed_at',
        'signed_by',
        'signed_by_user_id',
        'content_hash',
        'verification_code',
        'is_countersigned',
        'countersigned_at',
        'countersigned_by',
        'sent_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'audit_date' => 'date',
            'follow_up_on' => 'date',
            'is_signed' => 'boolean',
            'signed_at' => 'datetime',
            'is_countersigned' => 'boolean',
            'countersigned_at' => 'datetime',
            'sent_at' => 'datetime',
            'archived_at' => 'datetime',
            'global_score' => 'float',
            'status' => AuditStatus::class,
        ];
    }

    // ------------------------------------------------------------------
    // Relations
    // ------------------------------------------------------------------

    public function categories(): HasMany
    {
        return $this->hasMany(AuditCategory::class)->orderBy('position');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function signatory(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by_user_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /** Cahier des charges facultatif, accolé au rapport d'audit. */
    public function specification(): HasOne
    {
        return $this->hasOne(Specification::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AuditVersion::class)->orderByDesc('version');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'subject_id')
            ->where('subject_type', self::class)
            ->latest();
    }

    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.str_replace('%', '\%', $term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('reference', 'ilike', $like)
                ->orWhere('client_name', 'ilike', $like)
                ->orWhere('title', 'ilike', $like)
                ->orWhereHas('client', fn (Builder $c) => $c->where('name', 'ilike', $like));
        });
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        // Un administrateur voit tout le portefeuille ; un auditeur ne voit
        // que ses propres missions. C'est le cloisonnement qui manquait
        // totalement tant que l'application était mono-compte.
        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('user_id', $user->id)->orWhere('created_by', $user->id);
        });
    }

    // ------------------------------------------------------------------
    // Score
    // ------------------------------------------------------------------

    /** Moyenne pondérée (ou simple selon scoring_mode) des catégories, sur 5. */
    public function computeGlobalScore(): ?float
    {
        $categories = $this->relationLoaded('categories') ? $this->categories : $this->categories()->get();

        if ($categories->isEmpty()) {
            return null;
        }

        if ($this->scoring_mode === 'simple') {
            return round((float) $categories->avg('score'), 2);
        }

        $totalWeight = $categories->sum(fn (AuditCategory $c) => max(1, (int) $c->weight));

        if ($totalWeight === 0) {
            return null;
        }

        $weighted = $categories->sum(fn (AuditCategory $c) => $c->score * max(1, (int) $c->weight));

        return round($weighted / $totalWeight, 2);
    }

    public function getScoreLabelAttribute(): string
    {
        return ScoreScale::label($this->global_score);
    }

    public function getScoreColorAttribute(): string
    {
        return ScoreScale::color($this->global_score);
    }

    /** Score ramené sur 100, pour les jauges et le tableau de bord. */
    public function getScorePercentAttribute(): int
    {
        return (int) round((($this->global_score ?? 0) / ScoreScale::MAX) * 100);
    }

    // ------------------------------------------------------------------
    // Intégrité
    // ------------------------------------------------------------------

    /**
     * Empreinte du contenu au moment de la signature. Rejouée à la
     * vérification : si elle diffère, le document a été altéré.
     */
    public function computeContentHash(): string
    {
        return hash('sha256', json_encode($this->toSnapshot(), JSON_THROW_ON_ERROR));
    }

    public function toSnapshot(): array
    {
        $categories = $this->relationLoaded('categories') ? $this->categories : $this->categories()->get();

        return [
            'reference' => $this->reference,
            'title' => $this->title,
            'client_name' => $this->client_name,
            'audit_date' => optional($this->audit_date)->toDateString(),
            'conclusion' => $this->conclusion,
            'scoring_mode' => $this->scoring_mode,
            'global_score' => $this->global_score,
            'categories' => $categories->map(fn (AuditCategory $c) => [
                'position' => $c->position,
                'title' => $c->title,
                'score' => $c->score,
                'weight' => $c->weight,
                'observations' => $c->observations,
                'recommendations' => $c->recommendations,
                'priority' => $c->priority?->value,
                'due_on' => optional($c->due_on)->toDateString(),
                'owner' => $c->owner,
            ])->values()->all(),
        ];
    }

    public function isIntact(): bool
    {
        return $this->content_hash !== null && hash_equals($this->content_hash, $this->computeContentHash());
    }

    /**
     * Trois états, et non deux : les audits signés avant la mise en place du
     * contrôle d'intégrité n'ont pas d'empreinte. Les afficher comme
     * « altérés » serait une fausse alerte — ils sont simplement invérifiables.
     */
    public function integrityState(): string
    {
        if (! $this->is_signed) {
            return 'unsigned';
        }

        if ($this->content_hash === null) {
            return 'unknown';
        }

        return $this->isIntact() ? 'intact' : 'altered';
    }

    // ------------------------------------------------------------------
    // État
    // ------------------------------------------------------------------

    public function isLocked(): bool
    {
        return $this->status->isLocked();
    }

    public function isEditable(): bool
    {
        return ! $this->isLocked();
    }

    /** Les 3 catégories les plus faibles : l'encart « risques majeurs ». */
    public function topRisks(int $limit = 3)
    {
        $categories = $this->relationLoaded('categories') ? $this->categories : $this->categories()->get();

        return $categories->sortBy([
            fn (AuditCategory $a, AuditCategory $b) => $a->score <=> $b->score,
            fn (AuditCategory $a, AuditCategory $b) => $b->weight <=> $a->weight,
        ])->take($limit)->values();
    }

    /** Recommandations triées par urgence, pour le plan d'action. */
    public function actionPlan()
    {
        $categories = $this->relationLoaded('categories') ? $this->categories : $this->categories()->get();

        return $categories
            ->filter(fn (AuditCategory $c) => filled($c->recommendations))
            ->sortBy([
                fn (AuditCategory $a, AuditCategory $b) => ($a->priority?->rank() ?? 99) <=> ($b->priority?->rank() ?? 99),
                fn (AuditCategory $a, AuditCategory $b) => $a->score <=> $b->score,
            ])
            ->values();
    }

    public function criticalCount(): int
    {
        $categories = $this->relationLoaded('categories') ? $this->categories : $this->categories()->get();

        return $categories->filter(
            fn (AuditCategory $c) => $c->priority === Priority::Critical || $c->score <= 2
        )->count();
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->title ?: 'Audit '.$this->client_name;
    }

    public function pdfFilename(): string
    {
        return collect([
            'audit',
            $this->reference,
            \Illuminate\Support\Str::slug($this->client_name),
            optional($this->audit_date)->format('Y-m-d'),
        ])->filter()->implode('-').'.pdf';
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
