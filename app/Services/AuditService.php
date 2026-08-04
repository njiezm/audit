<?php

namespace App\Services;

use App\Enums\AuditStatus;
use App\Models\ActivityLog;
use App\Models\Audit;
use App\Models\AuditCategory;
use App\Models\CategoryLibraryEntry;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditService
{
    public function __construct(private readonly ReferenceGenerator $references)
    {
    }

    public function create(array $data, User $author): Audit
    {
        return DB::transaction(function () use ($data, $author) {
            $client = Client::resolveByName($data['client_name']);

            $audit = Audit::create([
                'client_id' => $client->id,
                'user_id' => $author->id,
                'created_by' => $author->id,
                'updated_by' => $author->id,
                'client_name' => $client->name,
                'reference' => $this->references->next(),
                'title' => $data['title'] ?? null,
                'status' => AuditStatus::Draft,
                'scoring_mode' => $data['scoring_mode'] ?? 'weighted',
                'watermark' => ($data['watermark'] ?? null) ?: null,
                'audit_date' => $data['audit_date'],
                'follow_up_on' => $data['follow_up_on'] ?? null,
                'conclusion' => $data['conclusion'] ?? null,
            ]);

            $this->syncCategories($audit, $data['categories']);
            $this->refreshScore($audit);

            ActivityLog::record('created', $audit, $audit->reference);

            return $audit->fresh(['categories', 'client']);
        });
    }

    public function update(Audit $audit, array $data, User $author): Audit
    {
        return DB::transaction(function () use ($audit, $data, $author) {
            $client = Client::resolveByName($data['client_name']);

            $audit->update([
                'client_id' => $client->id,
                'updated_by' => $author->id,
                'client_name' => $client->name,
                'title' => $data['title'] ?? null,
                'scoring_mode' => $data['scoring_mode'] ?? 'weighted',
                'watermark' => ($data['watermark'] ?? null) ?: null,
                'audit_date' => $data['audit_date'],
                'follow_up_on' => $data['follow_up_on'] ?? null,
                'conclusion' => $data['conclusion'] ?? null,
            ]);

            $this->syncCategories($audit, $data['categories']);
            $this->refreshScore($audit);

            ActivityLog::record('updated', $audit, $audit->reference);

            return $audit->fresh(['categories', 'client']);
        });
    }

    /**
     * Réconcilie les catégories par identifiant au lieu du delete/recreate
     * de l'ancienne version : les identifiants restent stables, les dates de
     * création sont conservées, et les pièces jointes rattachées survivent.
     */
    private function syncCategories(Audit $audit, array $categories): void
    {
        $keptIds = [];

        foreach (array_values($categories) as $position => $payload) {
            $attributes = [
                'position' => $position,
                'title' => trim($payload['title']),
                'score' => (int) $payload['score'],
                'weight' => max(1, (int) ($payload['weight'] ?? 1)),
                'observations' => $payload['observations'] ?? null,
                'recommendations' => $payload['recommendations'] ?? null,
                // Ces trois champs sont facultatifs : une chaîne vide venant
                // du formulaire comme une clé absente valent « non renseigné ».
                'priority' => ($payload['priority'] ?? null) ?: null,
                'due_on' => ($payload['due_on'] ?? null) ?: null,
                'owner' => ($payload['owner'] ?? null) ?: null,
            ];

            $existing = ! empty($payload['id'])
                ? $audit->categories()->whereKey($payload['id'])->first()
                : null;

            if ($existing) {
                $existing->update($attributes);
                $keptIds[] = $existing->id;
            } else {
                $keptIds[] = $audit->categories()->create($attributes)->id;
            }

            CategoryLibraryEntry::remember($attributes['title'], $attributes['weight']);
        }

        $audit->categories()->whereKeyNot($keptIds)->delete();
    }

    public function refreshScore(Audit $audit): void
    {
        $audit->load('categories');
        $audit->forceFill(['global_score' => $audit->computeGlobalScore()])->save();
    }

    // ------------------------------------------------------------------
    // Cycle de vie
    // ------------------------------------------------------------------

    public function finalize(Audit $audit): Audit
    {
        $audit->update(['status' => AuditStatus::Finalized]);
        ActivityLog::record('finalized', $audit);

        return $audit;
    }

    public function sign(Audit $audit, User $signatory): Audit
    {
        return DB::transaction(function () use ($audit, $signatory) {
            $audit->load('categories');
            $this->refreshScore($audit);
            $audit->load('categories');

            $hash = $audit->computeContentHash();

            $audit->update([
                'status' => AuditStatus::Signed,
                'is_signed' => true,
                'signed_at' => now(),
                'signed_by' => $signatory->name,
                'signed_by_user_id' => $signatory->id,
                'content_hash' => $hash,
                'verification_code' => $audit->verification_code ?: $this->generateVerificationCode(),
            ]);

            // Instantané figé : ce qui a été signé reste consultable même si
            // la signature est retirée et le contenu retravaillé ensuite.
            $audit->versions()->create([
                'version' => ($audit->versions()->max('version') ?? 0) + 1,
                'snapshot' => $audit->toSnapshot(),
                'content_hash' => $hash,
                'created_by' => $signatory->id,
            ]);

            ActivityLog::record('signed', $audit, $signatory->name, ['hash' => $hash]);

            return $audit->fresh(['categories']);
        });
    }

    public function unsign(Audit $audit): Audit
    {
        $audit->update([
            'status' => AuditStatus::Draft,
            'is_signed' => false,
            'signed_at' => null,
            'signed_by' => null,
            'signed_by_user_id' => null,
            'content_hash' => null,
        ]);

        ActivityLog::record('unsigned', $audit);

        return $audit;
    }

    public function countersign(Audit $audit, string $name): Audit
    {
        $audit->update([
            'is_countersigned' => true,
            'countersigned_at' => now(),
            'countersigned_by' => $name,
        ]);

        ActivityLog::record('countersigned', $audit, $name);

        return $audit;
    }

    public function archive(Audit $audit): Audit
    {
        $audit->update([
            'status' => AuditStatus::Archived,
            'archived_at' => now(),
        ]);

        ActivityLog::record('archived', $audit);

        return $audit;
    }

    public function unarchive(Audit $audit): Audit
    {
        $audit->update([
            'status' => $audit->is_signed ? AuditStatus::Signed : AuditStatus::Draft,
            'archived_at' => null,
        ]);

        ActivityLog::record('unarchived', $audit);

        return $audit;
    }

    /**
     * Duplication : le cas d'usage « audit de suivi du même client », qui
     * obligeait jusqu'ici à ressaisir toutes les catégories.
     */
    public function duplicate(Audit $source, User $author): Audit
    {
        return DB::transaction(function () use ($source, $author) {
            $source->load('categories');

            $copy = Audit::create([
                'client_id' => $source->client_id,
                'user_id' => $author->id,
                'created_by' => $author->id,
                'updated_by' => $author->id,
                'client_name' => $source->client_name,
                'reference' => $this->references->next(),
                'title' => $source->title ? $source->title.' (suivi)' : null,
                'status' => AuditStatus::Draft,
                'scoring_mode' => $source->scoring_mode,
                'watermark' => $source->watermark,
                'audit_date' => now()->toDateString(),
                'conclusion' => $source->conclusion,
            ]);

            foreach ($source->categories as $category) {
                $copy->categories()->create([
                    'position' => $category->position,
                    'title' => $category->title,
                    'score' => $category->score,
                    'weight' => $category->weight,
                    // Les constats du précédent audit sont repris comme point
                    // de départ ; les recommandations restent à réévaluer.
                    'observations' => $category->observations,
                    'recommendations' => $category->recommendations,
                    'priority' => $category->priority?->value,
                    'owner' => $category->owner,
                ]);
            }

            $this->refreshScore($copy);

            ActivityLog::record('duplicated', $copy, 'Copie de '.$source->reference, [
                'source_id' => $source->id,
                'source_reference' => $source->reference,
            ]);

            return $copy->fresh(['categories']);
        });
    }

    private function generateVerificationCode(): string
    {
        do {
            $code = Str::upper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4));
        } while (Audit::withTrashed()->where('verification_code', $code)->exists());

        return $code;
    }

    /** Statistiques du tableau de bord, calculées en SQL. */
    public function portfolioStats(?User $user): array
    {
        $base = fn () => Audit::query()->visibleTo($user);

        $scores = $base()->whereNotNull('global_score')->pluck('global_score');

        $weakest = AuditCategory::query()
            ->whereIn('audit_id', $base()->select('id'))
            ->selectRaw('title, AVG(score) AS avg_score, COUNT(*) AS occurrences')
            ->groupBy('title')
            ->havingRaw('COUNT(*) >= 1')
            ->orderBy('avg_score')
            ->limit(5)
            ->get();

        return [
            'total' => $base()->count(),
            'signed' => $base()->where('is_signed', true)->count(),
            'drafts' => $base()->where('status', AuditStatus::Draft->value)->count(),
            'clients' => $base()->distinct('client_id')->count('client_id'),
            'average_score' => $scores->isEmpty() ? null : round((float) $scores->avg(), 2),
            'weakest_categories' => $weakest,
            'follow_ups' => $base()
                ->whereNotNull('follow_up_on')
                ->whereDate('follow_up_on', '<=', now()->addDays(30))
                ->orderBy('follow_up_on')
                ->with('client')
                ->limit(5)
                ->get(),
            'recent' => $base()->with('client')->latest()->limit(5)->get(),
            'monthly' => $this->monthlyVolume($user),
        ];
    }

    private function monthlyVolume(?User $user): array
    {
        $rows = Audit::query()
            ->visibleTo($user)
            ->where('audit_date', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("to_char(audit_date, 'YYYY-MM') AS bucket, COUNT(*) AS total, AVG(global_score) AS avg_score")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->keyBy('bucket');

        $series = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i)->startOfMonth();
            $key = $month->format('Y-m');
            $row = $rows->get($key);

            $series[] = [
                'label' => $month->translatedFormat('M'),
                'month' => $key,
                'total' => (int) ($row->total ?? 0),
                'average' => $row?->avg_score !== null ? round((float) $row->avg_score, 2) : null,
            ];
        }

        return $series;
    }
}
