<?php

namespace App\Http\Requests;

use App\Enums\SpecificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SpecificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // l'autorisation passe par SpecificationPolicy
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'max:20'],
            'status' => ['required', Rule::enum(SpecificationStatus::class)],

            'context' => ['nullable', 'string', 'max:20000'],
            'objectives' => ['nullable', 'string', 'max:20000'],
            'scope_in' => ['nullable', 'string', 'max:20000'],
            'scope_out' => ['nullable', 'string', 'max:20000'],

            'announced_days_min' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'announced_days_max' => ['nullable', 'integer', 'min:0', 'max:9999', 'gte:announced_days_min'],
            'daily_rate' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'currency' => ['required', 'string', 'size:3'],

            'starts_on' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'include_in_pdf' => ['nullable', 'boolean'],

            'sections' => ['nullable', 'array', 'max:40'],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.body' => ['nullable', 'string', 'max:40000'],
            'sections.*.page_break_before' => ['nullable', 'boolean'],

            'lots' => ['nullable', 'array', 'max:80'],
            'lots.*.code' => ['nullable', 'string', 'max:12'],
            'lots.*.name' => ['required', 'string', 'max:255'],
            'lots.*.content' => ['nullable', 'string', 'max:20000'],
            'lots.*.phase' => ['nullable', 'string', 'max:120'],
            'lots.*.days_min' => ['required', 'integer', 'min:0', 'max:999'],
            'lots.*.days_max' => ['required', 'integer', 'min:0', 'max:999', 'gte:lots.*.days_min'],
            'lots.*.is_option' => ['nullable', 'boolean'],
            'lots.*.is_at_risk' => ['nullable', 'boolean'],
            'lots.*.risk_note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'intitulé',
            'version' => 'version',
            'status' => 'statut',
            'context' => 'contexte',
            'objectives' => 'objectifs',
            'scope_in' => 'périmètre inclus',
            'scope_out' => 'périmètre exclu',
            'announced_days_min' => 'charge annoncée minimale',
            'announced_days_max' => 'charge annoncée maximale',
            'daily_rate' => 'taux journalier',
            'currency' => 'devise',
            'starts_on' => 'date de démarrage',
            'valid_until' => 'validité de la proposition',
        ];
    }

    public function messages(): array
    {
        return [
            'announced_days_max.gte' => 'La charge annoncée maximale doit être supérieure ou égale à la minimale.',
            'lots.*.days_max.gte' => 'La borne haute d\'un lot doit être supérieure ou égale à sa borne basse.',
            'lots.*.name.required' => 'Chaque lot doit porter un intitulé.',
            'sections.*.title.required' => 'Chaque section doit porter un titre.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Les lignes ajoutées puis laissées vides sont écartées plutôt que
        // de faire échouer l'enregistrement.
        $this->merge([
            'sections' => collect($this->input('sections', []))
                ->reject(fn ($s) => blank($s['title'] ?? null) && blank($s['body'] ?? null))
                ->values()
                ->all(),

            'lots' => collect($this->input('lots', []))
                ->reject(fn ($l) => blank($l['name'] ?? null) && blank($l['content'] ?? null))
                ->map(fn ($l) => $l + ['days_min' => 0, 'days_max' => 0])
                ->values()
                ->all(),
        ]);
    }
}
