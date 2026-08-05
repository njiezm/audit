<?php

namespace App\Services;

use App\Enums\SpecificationStatus;
use App\Models\ActivityLog;
use App\Models\Audit;
use App\Models\Specification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SpecificationService
{
    public function __construct(private readonly ReferenceGenerator $references)
    {
    }

    public function createFor(Audit $audit, array $data, User $author): Specification
    {
        return DB::transaction(function () use ($audit, $data, $author) {
            $specification = $audit->specification()->create($this->attributes($data) + [
                'reference' => $this->references->nextSpecification(),
                'created_by' => $author->id,
                'updated_by' => $author->id,
            ]);

            $this->syncChildren($specification, $data);

            ActivityLog::record('specification_created', $audit, $specification->reference);

            return $specification->fresh(['sections', 'lots']);
        });
    }

    public function update(Specification $specification, array $data, User $author): Specification
    {
        return DB::transaction(function () use ($specification, $data, $author) {
            $specification->update($this->attributes($data) + ['updated_by' => $author->id]);

            $this->syncChildren($specification, $data);

            ActivityLog::record('specification_updated', $specification->audit, $specification->reference);

            return $specification->fresh(['sections', 'lots']);
        });
    }

    /**
     * Sections et lots sont intégralement remplacés : contrairement aux
     * catégories d'un audit, rien ne s'y rattache (ni pièce jointe, ni
     * historique), donc leur identité n'a pas besoin d'être préservée.
     */
    private function syncChildren(Specification $specification, array $data): void
    {
        $specification->sections()->delete();

        foreach (array_values($data['sections'] ?? []) as $position => $section) {
            $specification->sections()->create([
                'position' => $position,
                'title' => trim($section['title']),
                'body' => $section['body'] ?? null,
                'page_break_before' => (bool) ($section['page_break_before'] ?? false),
            ]);
        }

        $specification->lots()->delete();

        foreach (array_values($data['lots'] ?? []) as $position => $lot) {
            $specification->lots()->create([
                'position' => $position,
                'code' => $lot['code'] ?? null,
                'name' => trim($lot['name']),
                'content' => $lot['content'] ?? null,
                'phase' => $lot['phase'] ?: null,
                'days_min' => (int) ($lot['days_min'] ?? 0),
                'days_max' => (int) ($lot['days_max'] ?? 0),
                'is_option' => (bool) ($lot['is_option'] ?? false),
                'is_at_risk' => (bool) ($lot['is_at_risk'] ?? false),
                'risk_note' => $lot['risk_note'] ?? null,
            ]);
        }
    }

    private function attributes(array $data): array
    {
        return [
            'title' => $data['title'],
            'version' => $data['version'] ?? '1.0',
            'status' => $data['status'] ?? SpecificationStatus::Draft->value,
            'context' => $data['context'] ?? null,
            'objectives' => $data['objectives'] ?? null,
            'scope_in' => $data['scope_in'] ?? null,
            'scope_out' => $data['scope_out'] ?? null,
            'announced_days_min' => $data['announced_days_min'] ?? null,
            'announced_days_max' => $data['announced_days_max'] ?? null,
            'daily_rate' => $data['daily_rate'] ?? null,
            'currency' => $data['currency'] ?? 'EUR',
            'starts_on' => $data['starts_on'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
            'include_in_pdf' => (bool) ($data['include_in_pdf'] ?? true),
        ];
    }
}
