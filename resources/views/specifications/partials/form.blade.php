@php
    $rows = old('sections', $sections);
    $lotRows = old('lots', $lots);
    $get = fn ($row, $key, $default = '') => data_get($row, $key, $default);
@endphp

@csrf
@if ($method !== 'POST')
    @method($method)
@endif

<div class="row g-4">
    {{-- ------------------------------------------------------------------
         Colonne principale
         ------------------------------------------------------------------ --}}
    <div class="col-12 col-xl-8">
        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Identification</h2>

                <div class="row g-2">
                    <div class="col-12">
                        <label for="title" class="form-label small">Intitulé <span aria-hidden="true">*</span></label>
                        <input type="text" id="title" name="title" required
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $specification->title) }}">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-6 col-md-3">
                        <label for="version" class="form-label small">Version</label>
                        <input type="text" id="version" name="version" required class="form-control"
                               value="{{ old('version', $specification->version ?? '1.0') }}">
                    </div>

                    <div class="col-6 col-md-4">
                        <label for="status" class="form-label small">Statut</label>
                        <select id="status" name="status" class="form-select">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}"
                                    @selected(old('status', $specification->status?->value ?? 'draft') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label for="starts_on" class="form-label small">Démarrage</label>
                        <input type="date" id="starts_on" name="starts_on" class="form-control"
                               value="{{ old('starts_on', $specification->starts_on?->format('Y-m-d')) }}">
                    </div>

                    <div class="col-6 col-md-3">
                        <label for="valid_until" class="form-label small">Validité</label>
                        <input type="date" id="valid_until" name="valid_until"
                               class="form-control @error('valid_until') is-invalid @enderror"
                               value="{{ old('valid_until', $specification->valid_until?->format('Y-m-d')) }}">
                        @error('valid_until')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Cadrage</h2>

                <div class="mb-3">
                    <label for="context" class="form-label small">Contexte</label>
                    <textarea id="context" name="context" rows="4" class="form-control"
                              placeholder="D'où part le projet, et pourquoi ce chantier.">{{ old('context', $specification->context) }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="objectives" class="form-label small">Objectifs</label>
                    <textarea id="objectives" name="objectives" rows="4" class="form-control"
                              placeholder="Ce que le projet doit permettre à la fin.">{{ old('objectives', $specification->objectives) }}</textarea>
                </div>

                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <label for="scope_in" class="form-label small">Périmètre inclus</label>
                        <textarea id="scope_in" name="scope_in" rows="5" class="form-control"
                                  placeholder="Ce qui est couvert par le chiffrage.">{{ old('scope_in', $specification->scope_in) }}</textarea>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="scope_out" class="form-label small">Périmètre exclu</label>
                        <textarea id="scope_out" name="scope_out" rows="5" class="form-control"
                                  placeholder="Ce qui n'est pas couvert — aussi important que l'inclus.">{{ old('scope_out', $specification->scope_out) }}</textarea>
                        <div class="form-text">Un périmètre exclu explicite évite l'essentiel des litiges.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---------------- Lots ---------------- --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 text-uppercase text-muted mb-0">Lots de travaux</h2>
                    <span class="small text-muted">
                        Total du périmètre de base :
                        <strong data-lot-total>0 – 0 j</strong>
                    </span>
                </div>

                <div id="lot-list">
                    @foreach ($lotRows as $index => $lot)
                        @include('specifications.partials.lot-row', ['index' => $index, 'lot' => $lot])
                    @endforeach
                </div>

                <button type="button" class="btn btn-outline-secondary w-100 mt-2" data-add-lot>
                    + Ajouter un lot
                </button>
            </div>
        </div>

        {{-- ---------------- Sections libres ---------------- --}}
        <div class="card">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Sections</h2>

                <div id="section-list">
                    @foreach ($rows as $index => $section)
                        @include('specifications.partials.section-row', ['index' => $index, 'section' => $section])
                    @endforeach
                </div>

                <button type="button" class="btn btn-outline-secondary w-100 mt-2" data-add-section>
                    + Ajouter une section
                </button>
            </div>
        </div>
    </div>

    {{-- ------------------------------------------------------------------
         Colonne latérale
         ------------------------------------------------------------------ --}}
    <div class="col-12 col-xl-4">
        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Charges et budget</h2>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label for="announced_days_min" class="form-label small">Enveloppe min. (j)</label>
                        <input type="number" id="announced_days_min" name="announced_days_min" min="0" max="9999"
                               class="form-control @error('announced_days_min') is-invalid @enderror"
                               value="{{ old('announced_days_min', $specification->announced_days_min) }}">
                        @error('announced_days_min')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label for="announced_days_max" class="form-label small">Enveloppe max. (j)</label>
                        <input type="number" id="announced_days_max" name="announced_days_max" min="0" max="9999"
                               class="form-control @error('announced_days_max') is-invalid @enderror"
                               value="{{ old('announced_days_max', $specification->announced_days_max) }}">
                        @error('announced_days_max')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="alert alert-secondary small mb-3" data-envelope-note hidden></div>

                <div class="form-text mb-3">
                    Laissez vide pour annoncer exactement la somme des lots. Un écart
                    est affiché sur le document comme marge de cadrage.
                </div>

                <div class="row g-2">
                    <div class="col-8">
                        <label for="daily_rate" class="form-label small">Taux journalier</label>
                        <input type="number" id="daily_rate" name="daily_rate" min="0" max="100000"
                               class="form-control" placeholder="Optionnel"
                               value="{{ old('daily_rate', $specification->daily_rate) }}">
                    </div>
                    <div class="col-4">
                        <label for="currency" class="form-label small">Devise</label>
                        <input type="text" id="currency" name="currency" maxlength="3" required
                               class="form-control text-uppercase"
                               value="{{ old('currency', $specification->currency ?? 'EUR') }}">
                    </div>
                </div>
                <div class="form-text mt-1">Renseigné, le budget estimatif figure sur le document.</div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="include_in_pdf"
                           name="include_in_pdf" value="1"
                           @checked(old('include_in_pdf', $specification->include_in_pdf ?? true))>
                    <label class="form-check-label" for="include_in_pdf">
                        Accoler au PDF du rapport d'audit
                    </label>
                </div>
                <div class="form-text">
                    Décoché, le cahier des charges reste téléchargeable seul.
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-nj">{{ $submitLabel }}</button>
            <a href="{{ $specification->exists ? route('audits.specification.show', $audit) : route('audits.show', $audit) }}"
               class="btn btn-outline-secondary">Annuler</a>
        </div>
    </div>
</div>

{{-- Gabarits clonés par le JS --}}
<template id="lot-template">
    @include('specifications.partials.lot-row', [
        'index' => '__INDEX__',
        'lot' => ['code' => '', 'name' => '', 'content' => '', 'phase' => '', 'days_min' => 0, 'days_max' => 0, 'is_option' => false, 'is_at_risk' => false, 'risk_note' => ''],
    ])
</template>

<template id="section-template">
    @include('specifications.partials.section-row', [
        'index' => '__INDEX__',
        'section' => ['title' => '', 'body' => '', 'page_break_before' => false],
    ])
</template>

@push('scripts')
<script type="module">
    window.initSpecificationEditor();
</script>
@endpush
