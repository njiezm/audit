{{--
    Éditeur d'audit partagé par la création et la modification.
    Les deux vues contenaient auparavant le même formulaire et le même script
    recopiés à l'identique (~120 lignes de JS en double).

    Variables attendues :
      $action, $method, $audit (nullable), $categories (array),
      $templates, $suggestions, $submitLabel
--}}

@php
    $scale = \App\Support\ScoreScale::all();
    // Après un échec de validation, on repart de la saisie de l'utilisateur :
    // les catégories ajoutées avant l'échec ne sont plus perdues.
    $categories = old('categories', $categories);
@endphp

@section('main-class', 'p-0')
@section('bare', true)
@section('hide-footer', true)

<div class="editor-tabs" role="tablist" aria-label="Vues de l'éditeur">
    <button type="button" role="tab" data-pane-target="form" aria-selected="true">Édition</button>
    <button type="button" role="tab" data-pane-target="preview" aria-selected="false">Aperçu</button>
</div>

<div class="editor-layout">
    <aside class="editor-form editor-pane is-active" data-pane="form">
        <div class="mb-3">
            @include('partials.flash')
        </div>

        <div id="editor-flash" class="alert alert-warning py-2 small" hidden role="status"></div>

        <form action="{{ $action }}" method="POST" id="audit-form" novalidate>
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif

            {{-- ---------------- Mission ---------------- --}}
            <fieldset class="mb-4">
                <legend class="fw-bold small text-uppercase text-muted">Mission</legend>

                <div class="mb-2">
                    <label for="client_name" class="form-label small">Client <span aria-hidden="true">*</span></label>
                    <input type="text" id="client_name" name="client_name" list="client-list" required
                           class="form-control @error('client_name') is-invalid @enderror"
                           placeholder="Nom de l'entreprise"
                           value="{{ old('client_name', $audit?->client_name) }}">
                    <datalist id="client-list">
                        @foreach ($clients as $client)
                            <option value="{{ $client->name }}"></option>
                        @endforeach
                    </datalist>
                    @error('client_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Un client existant est réutilisé automatiquement (insensible à la casse).</div>
                </div>

                <div class="mb-2">
                    <label for="title" class="form-label small">Intitulé de la mission</label>
                    <input type="text" id="title" name="title"
                           class="form-control @error('title') is-invalid @enderror"
                           placeholder="Audit cyber annuel, revue post-migration…"
                           value="{{ old('title', $audit?->title) }}">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <label for="audit_date" class="form-label small">Date <span aria-hidden="true">*</span></label>
                        <input type="date" id="audit_date" name="audit_date" required
                               class="form-control @error('audit_date') is-invalid @enderror"
                               value="{{ old('audit_date', $audit?->audit_date?->format('Y-m-d') ?? date('Y-m-d')) }}">
                        @error('audit_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label for="follow_up_on" class="form-label small">Suivi prévu le</label>
                        <input type="date" id="follow_up_on" name="follow_up_on"
                               class="form-control @error('follow_up_on') is-invalid @enderror"
                               value="{{ old('follow_up_on', $audit?->follow_up_on?->format('Y-m-d')) }}">
                        @error('follow_up_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mt-2">
                    <label for="scoring_mode" class="form-label small">Mode de notation</label>
                    <select id="scoring_mode" name="scoring_mode" class="form-select form-select-sm">
                        <option value="weighted" @selected(old('scoring_mode', $audit?->scoring_mode ?? 'weighted') === 'weighted')>
                            Moyenne pondérée (recommandé)
                        </option>
                        <option value="simple" @selected(old('scoring_mode', $audit?->scoring_mode) === 'simple')>
                            Moyenne simple
                        </option>
                    </select>
                    <div class="form-text">
                        En mode pondéré, la note de chaque catégorie compte selon son poids ×1 à ×5.
                    </div>
                </div>

                {{-- Filigrane : imprimé en diagonale sur chaque page du PDF. --}}
                <div class="mt-2">
                    <label for="watermark" class="form-label small">Filigrane du PDF</label>
                    <input type="text" id="watermark" name="watermark" list="watermark-presets"
                           maxlength="40" class="form-control form-control-sm @error('watermark') is-invalid @enderror"
                           placeholder="Aucun filigrane"
                           value="{{ old('watermark', $audit?->watermark) }}">
                    <datalist id="watermark-presets">
                        <option value="BROUILLON"></option>
                        <option value="CONFIDENTIEL"></option>
                        <option value="DIAGNOSTIC GRATUIT"></option>
                        <option value="APERÇU"></option>
                        <option value="NE PAS DIFFUSER"></option>
                    </datalist>
                    @error('watermark')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Laissez vide pour un PDF sans filigrane.</div>
                </div>
            </fieldset>

            {{-- ---------------- Modèle ---------------- --}}
            @if ($templates->isNotEmpty())
                <div class="mb-3 p-2 rounded" style="background: var(--surface-2); border: 1px solid var(--border);">
                    <label for="template-picker" class="form-label small fw-semibold mb-1">
                        Charger un modèle d'audit
                    </label>
                    <select id="template-picker" class="form-select form-select-sm" data-template-picker
                            data-url-template="{{ route('templates.categories', ['template' => '__ID__']) }}">
                        <option value="">— Choisir un modèle —</option>
                        @foreach ($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Remplace les catégories en cours par celles du modèle.</div>
                </div>
            @endif

            <hr>

            {{-- ---------------- Catégories ---------------- --}}
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="fw-bold small text-uppercase text-muted mb-0">Catégories évaluées</h2>
                <span class="small text-muted">Glisser pour réordonner</span>
            </div>

            <div id="category-list">
                @foreach ($categories as $index => $category)
                    @include('audits.partials.category-row', [
                        'index' => $index,
                        'category' => $category,
                        'scale' => $scale,
                    ])
                @endforeach
            </div>

            <button type="button" class="btn btn-outline-secondary w-100 mt-2" data-add-category>
                + Ajouter une catégorie <kbd class="ms-1 small">Ctrl+↵</kbd>
            </button>

            {{-- ---------------- Synthèse ---------------- --}}
            <div class="mt-4">
                <label for="conclusion" class="form-label fw-bold small text-uppercase text-muted">
                    Synthèse de l'expert
                </label>
                <textarea id="conclusion" name="conclusion" rows="6"
                          class="form-control @error('conclusion') is-invalid @enderror"
                          placeholder="Votre conclusion générale…">{{ old('conclusion', $audit?->conclusion) }}</textarea>
                @error('conclusion')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mt-3 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-nj flex-grow-1">
                    {{ $submitLabel }} <kbd class="ms-1 small">Ctrl+S</kbd>
                </button>
                @if ($audit)
                    <a href="{{ route('audits.previewPdf', $audit) }}" target="_blank" rel="noopener"
                       class="btn btn-outline-secondary">Aperçu PDF réel</a>
                    <a href="{{ route('audits.show', $audit) }}" class="btn btn-outline-secondary">Annuler</a>
                @else
                    <a href="{{ route('audits.index') }}" class="btn btn-outline-secondary">Annuler</a>
                @endif
            </div>

            <p class="form-text mt-2 mb-0">
                L'aperçu ci-contre est indicatif. Le rendu de référence est le PDF généré par le serveur.
            </p>
        </form>
    </aside>

    <main class="editor-preview editor-pane" data-pane="preview" id="preview-area" aria-live="polite"
          aria-label="Aperçu du rapport"></main>
</div>

{{-- Modèle cloné par le JS pour chaque nouvelle catégorie --}}
<template id="category-template">
    @include('audits.partials.category-row', [
        'index' => '__INDEX__',
        'category' => ['title' => '', 'score' => 3, 'weight' => 1, 'observations' => '', 'recommendations' => '', 'priority' => '', 'due_on' => '', 'owner' => ''],
        'scale' => $scale,
    ])
</template>

<datalist id="category-suggestions">
    @foreach ($suggestions as $suggestion)
        <option value="{{ $suggestion }}"></option>
    @endforeach
</datalist>

<div id="pagination-probe" aria-hidden="true"></div>

@push('scripts')
<script type="module">
    window.initAuditEditor({
        reference: @json($audit?->reference ?? 'Référence attribuée à l\'enregistrement'),
    });
</script>
@endpush
