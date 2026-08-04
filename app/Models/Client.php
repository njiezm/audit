<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'siret',
        'sector',
        'contact_name',
        'contact_email',
        'contact_phone',
        'address',
        'logo_path',
        'notes',
    ];

    protected static function booted(): void
    {
        static::saving(function (Client $client) {
            if (blank($client->slug)) {
                $client->slug = static::uniqueSlug($client->name);
            }
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'client';
        $slug = $base;
        $i = 2;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /**
     * Rattache un audit à un client existant à partir du nom saisi, ou
     * en crée un. C'est ce qui empêche « SARL Dupont », « Dupont SARL »
     * et « dupont » de devenir trois clients distincts.
     */
    public static function resolveByName(string $name): self
    {
        $name = trim($name);

        $existing = static::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        return $existing ?? static::create(['name' => $name]);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class)->latest('audit_date');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $like = '%'.str_replace('%', '\%', $term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'ilike', $like)
                ->orWhere('siret', 'ilike', $like)
                ->orWhere('sector', 'ilike', $like)
                ->orWhere('contact_email', 'ilike', $like);
        });
    }

    public function averageScore(): ?float
    {
        $scores = $this->audits()->whereNotNull('global_score')->pluck('global_score');

        return $scores->isEmpty() ? null : round((float) $scores->avg(), 2);
    }

    /** Écart entre les deux derniers audits : la progression du client. */
    public function scoreTrend(): ?float
    {
        $last = $this->audits()->whereNotNull('global_score')->take(2)->get();

        if ($last->count() < 2) {
            return null;
        }

        return round((float) $last[0]->global_score - (float) $last[1]->global_score, 2);
    }
}
