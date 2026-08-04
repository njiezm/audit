<?php

namespace App\Http\Requests;

use App\Enums\Priority;
use App\Support\ScoreScale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Règles partagées par la création et la mise à jour. Elles étaient
 * auparavant dupliquées entre store() et update(), avec une règle
 * `categories.*.id` validée puis jamais utilisée.
 */
class AuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // l'autorisation passe par AuditPolicy dans le contrôleur
    }

    public function rules(): array
    {
        $auditId = $this->route('audit')?->id;

        return [
            'client_name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'audit_date' => ['required', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:'.now()->addYear()->toDateString()],
            'follow_up_on' => ['nullable', 'date', 'after:audit_date'],
            'scoring_mode' => ['required', Rule::in(['weighted', 'simple'])],
            'watermark' => ['nullable', 'string', 'max:40'],
            'conclusion' => ['nullable', 'string', 'max:20000'],

            'categories' => ['required', 'array', 'min:1', 'max:40'],
            'categories.*.id' => [
                'nullable',
                'integer',
                // Une catégorie ne peut être réutilisée que si elle appartient
                // bien à l'audit édité.
                Rule::exists('audit_categories', 'id')->where('audit_id', $auditId),
            ],
            'categories.*.title' => ['required', 'string', 'max:255'],
            'categories.*.score' => ['required', 'integer', 'min:'.ScoreScale::MIN, 'max:'.ScoreScale::MAX],
            'categories.*.weight' => ['required', 'integer', 'min:1', 'max:5'],
            'categories.*.observations' => ['nullable', 'string', 'max:20000'],
            'categories.*.recommendations' => ['nullable', 'string', 'max:20000'],
            'categories.*.priority' => ['nullable', Rule::enum(Priority::class)],
            'categories.*.due_on' => ['nullable', 'date'],
            'categories.*.owner' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'client_name' => 'nom du client',
            'title' => 'intitulé de la mission',
            'audit_date' => "date de l'audit",
            'follow_up_on' => 'date de suivi',
            'scoring_mode' => 'mode de notation',
            'conclusion' => 'synthèse',
            'categories' => 'catégories',
            'categories.*.title' => 'intitulé de la catégorie',
            'categories.*.score' => 'note',
            'categories.*.weight' => 'pondération',
            'categories.*.observations' => 'observations',
            'categories.*.recommendations' => 'recommandations',
            'categories.*.priority' => 'criticité',
            'categories.*.due_on' => 'échéance',
            'categories.*.owner' => 'responsable',
        ];
    }

    public function messages(): array
    {
        return [
            'categories.required' => 'Un audit doit comporter au moins une catégorie.',
            'categories.min' => 'Un audit doit comporter au moins une catégorie.',
            'categories.max' => 'Un audit ne peut pas dépasser 40 catégories.',
            'audit_date.before_or_equal' => "La date de l'audit ne peut pas être située plus d'un an dans le futur.",
            'follow_up_on.after' => "La date de suivi doit être postérieure à la date de l'audit.",
        ];
    }

    protected function prepareForValidation(): void
    {
        $categories = collect($this->input('categories', []))
            // Les lignes entièrement vides ajoutées puis abandonnées dans le
            // formulaire sont écartées au lieu de faire échouer la validation.
            ->reject(fn ($c) => blank($c['title'] ?? null) && blank($c['observations'] ?? null) && blank($c['recommendations'] ?? null))
            ->values()
            ->all();

        $this->merge([
            'client_name' => trim((string) $this->input('client_name')),
            'scoring_mode' => $this->input('scoring_mode', 'weighted'),
            'categories' => $categories,
        ]);
    }
}
