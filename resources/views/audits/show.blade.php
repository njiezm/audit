@extends('layouts.app')

@section('title', 'Audit ' . $audit->reference)

@section('content')
@php
    $radar = app(\App\Services\RadarChart::class)->svg(
        $audit->categories->map(fn ($c) => ['label' => $c->title, 'value' => $c->score])->all()
    );
    $plan = $audit->actionPlan();
    $risks = $audit->topRisks();
    $delta = $previous && $audit->global_score !== null && $previous->global_score !== null
        ? round((float) $audit->global_score - (float) $previous->global_score, 2)
        : null;
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <h1 class="h3 mb-0">{{ $audit->reference }}</h1>
            <x-status-badge :status="$audit->status" />
            @if ($audit->is_countersigned)
                <span class="badge-status badge-status--signed">Contre-signé</span>
            @endif
            @if ($audit->watermark)
                <span class="badge text-bg-light border">Filigrane : {{ $audit->watermark }}</span>
            @endif
        </div>
        <p class="text-muted mb-0">
            {{ $audit->display_title }} ·
            @if ($audit->client)
                <a href="{{ route('clients.show', $audit->client) }}">{{ $audit->client_name }}</a>
            @else
                {{ $audit->client_name }}
            @endif
            · {{ $audit->audit_date?->format('d/m/Y') }}
        </p>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        @can('update', $audit)
            <a href="{{ route('audits.edit', $audit) }}" class="btn btn-outline-secondary">Modifier</a>
        @endcan
        <a href="{{ route('audits.previewPdf', $audit) }}" target="_blank" rel="noopener"
           class="btn btn-outline-secondary">Aperçu PDF</a>
        <a href="{{ route('audits.downloadPdf', $audit) }}" class="btn btn-nj-outline">Télécharger</a>
        <a href="{{ route('audits.sendForm', $audit) }}" class="btn btn-outline-secondary">Envoyer au client</a>

        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">Plus</button>
            <ul class="dropdown-menu dropdown-menu-end">
                @can('create', App\Models\Audit::class)
                    <li><button type="submit" form="form-duplicate" class="dropdown-item">Dupliquer pour un suivi</button></li>
                @endcan
                @can('update', $audit)
                    @if ($audit->status === \App\Enums\AuditStatus::Draft)
                        <li><button type="submit" form="form-finalize" class="dropdown-item">Marquer comme finalisé</button></li>
                    @endif
                    <li><button type="submit" form="form-archive" class="dropdown-item">Archiver</button></li>
                @endcan
                @if ($audit->status === \App\Enums\AuditStatus::Archived)
                    <li><button type="submit" form="form-unarchive" class="dropdown-item">Sortir des archives</button></li>
                @endif
                @can('delete', $audit)
                    <li><hr class="dropdown-divider"></li>
                    <li><button type="submit" form="form-delete" class="dropdown-item text-danger">Supprimer</button></li>
                @endcan
            </ul>
        </div>
    </div>
</div>

{{-- ------------------------------------------------------------------
     Bandeau de signature : c'est ici que se joue la valeur probante.
     ------------------------------------------------------------------ --}}
@if ($audit->is_signed)
    @php
        $banner = match ($audit->integrityState()) {
            'intact'  => ['integrity-banner--ok', '🔒', 'Document signé et intègre'],
            'altered' => ['integrity-banner--ko', '⚠', 'Attention : le contenu ne correspond plus à la version signée'],
            default   => ['integrity-banner--unknown', 'ℹ', 'Document signé avant la mise en place du contrôle d\'intégrité'],
        };
    @endphp

    <div class="integrity-banner {{ $banner[0] }} mb-4">
        <span style="font-size:1.6rem" aria-hidden="true">{{ $banner[1] }}</span>
        <div class="flex-grow-1">
            <p class="fw-bold mb-1">{{ $banner[2] }}</p>
            <p class="mb-1 small">
                Signé le {{ $audit->signed_at?->format('d/m/Y à H:i') }} par {{ $audit->signed_by }}.
                @if ($audit->is_countersigned)
                    Contre-signé par {{ $audit->countersigned_by }}
                    le {{ $audit->countersigned_at?->format('d/m/Y') }}.
                @endif
            </p>

            @if ($audit->verification_code)
                <p class="mb-0 small">
                    Code de vérification :
                    <code class="fw-bold">{{ $audit->verification_code }}</code>
                    — vérifiable sur
                    <a href="{{ route('verify.show', $audit->verification_code) }}" target="_blank" rel="noopener">
                        {{ route('verify.form') }}
                    </a>
                </p>
            @else
                <p class="mb-0 small">
                    Ce rapport ne porte ni empreinte ni code de vérification. Retirez puis reposez
                    la signature pour lui en attribuer.
                </p>
            @endif
        </div>
        @can('unsign', $audit)
            <button type="submit" form="form-unsign" class="btn btn-sm btn-outline-warning flex-shrink-0">
                Retirer la signature
            </button>
        @endcan
    </div>
@else
    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <p class="fw-bold mb-1">Ce rapport n'est pas encore signé</p>
                <p class="text-muted small mb-0">
                    Une fois signé, son contenu est figé et un code de vérification est délivré au client.
                </p>
            </div>
            @can('sign', $audit)
                <button type="button" class="btn btn-nj" data-bs-toggle="modal" data-bs-target="#sign-modal">
                    Signer l'audit
                </button>
            @else
                <span class="text-muted small">{{ Gate::inspect('sign', $audit)->message() }}</span>
            @endcan
        </div>
    </div>
@endif

<div class="row g-3 mb-4">
    {{-- Score global --}}
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <h2 class="h6 text-uppercase text-muted mb-3">Score global</h2>

                @if ($audit->global_score !== null)
                    <div class="score-badge mx-auto mb-2"
                         style="width:76px;height:76px;font-size:1.9rem;background:{{ $audit->score_color }}">
                        @score($audit->global_score)
                    </div>
                    <p class="fw-bold mb-1" style="color: {{ $audit->score_color }}">{{ $audit->score_label }}</p>
                    <p class="text-muted small mb-2">
                        {{ $audit->scoring_mode === 'weighted' ? 'Moyenne pondérée' : 'Moyenne simple' }}
                        sur {{ $audit->categories->count() }} catégorie(s)
                    </p>

                    @if ($delta !== null)
                        <p class="mb-0 small">
                            <span class="fw-bold {{ $delta >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 1, ',', '') }}
                            </span>
                            par rapport à
                            <a href="{{ route('audits.show', $previous) }}">{{ $previous->reference }}</a>
                            ({{ $previous->audit_date?->format('m/Y') }})
                        </p>
                    @endif
                @else
                    <p class="text-muted mb-0">Aucune catégorie notée.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Radar --}}
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <h2 class="h6 text-uppercase text-muted mb-2">Répartition par catégorie</h2>
                @if ($radar)
                    {!! $radar !!}
                @else
                    <p class="text-muted small mb-0 mt-4">
                        Le radar s'affiche à partir de trois catégories.
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Risques majeurs --}}
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Risques majeurs</h2>

                @forelse ($risks as $risk)
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="score-badge score-badge--sm" style="background: {{ $risk->score_color }}">
                            {{ $risk->score }}
                        </span>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-semibold text-truncate">{{ $risk->title }}</div>
                            <div class="small text-muted">
                                {{ $risk->score_label }}@if ($risk->weight > 1) · poids ×{{ $risk->weight }}@endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">Aucune catégorie.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ------------------------------------------------------------------
     Plan d'action
     ------------------------------------------------------------------ --}}
@if ($plan->isNotEmpty())
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-3">
                Plan d'action <span class="badge text-bg-light border">{{ $plan->count() }}</span>
            </h2>

            <div class="table-responsive">
                <table class="table table-sm table-stack align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Criticité</th>
                            <th>Catégorie</th>
                            <th>Action</th>
                            <th>Responsable</th>
                            <th>Échéance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($plan as $item)
                            <tr>
                                <td data-label="Criticité">
                                    @if ($item->priority)
                                        <span class="priority-dot" style="background: {{ $item->priority->color() }}"></span>
                                        {{ $item->priority->label() }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Catégorie">{{ $item->title }}</td>
                                <td data-label="Action" class="rich">@rich($item->recommendations)</td>
                                <td data-label="Responsable">{{ $item->owner ?: '—' }}</td>
                                <td data-label="Échéance">
                                    @if ($item->due_on)
                                        <span class="{{ $item->isOverdue() ? 'text-danger fw-bold' : '' }}">
                                            {{ $item->due_on->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

{{-- ------------------------------------------------------------------
     Détail des catégories
     ------------------------------------------------------------------ --}}
<div class="card mb-4">
    <div class="card-body">
        <h2 class="h6 text-uppercase text-muted mb-3">Constats détaillés</h2>

        @foreach ($audit->categories as $category)
            <div class="mb-4 pb-3 {{ ! $loop->last ? 'border-bottom' : '' }}">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                    <div>
                        <h3 class="h6 mb-1">{{ $category->title }}</h3>
                        <span class="small text-muted">
                            {{ $category->score_label }}
                            @if ($category->weight > 1) · poids ×{{ $category->weight }}@endif
                        </span>
                    </div>
                    <span class="score-badge" style="background: {{ $category->score_color }}">{{ $category->score }}</span>
                </div>

                <div class="mb-2">
                    <div class="fw-semibold small text-muted">Observations</div>
                    <div class="rich">@rich($category->observations ?: 'Non renseigné.')</div>
                </div>

                @if ($category->recommendations)
                    <div class="p-3 rounded" style="background: var(--surface-3); border-left: 3px solid var(--nj-yellow)">
                        <div class="fw-semibold small">Recommandation</div>
                        <div class="rich">@rich($category->recommendations)</div>
                    </div>
                @endif

                @if ($category->attachments->isNotEmpty())
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        @foreach ($category->attachments as $attachment)
                            <a href="{{ route('audits.attachments.download', [$audit, $attachment]) }}"
                               class="badge text-bg-light border text-decoration-none">
                                📎 {{ $attachment->original_name }} ({{ $attachment->humanSize() }})
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        @if ($audit->conclusion)
            <div class="p-3 rounded" style="border: 2px solid var(--nj-blue)">
                <div class="brand-font mb-2">SYNTHÈSE GLOBALE</div>
                <div class="rich">@rich($audit->conclusion)</div>
            </div>
        @endif
    </div>
</div>

{{-- ------------------------------------------------------------------
     Cahier des charges — module facultatif
     ------------------------------------------------------------------ --}}
@php
    $specification = $audit->specification;
@endphp

<div class="card mb-4">
    <div class="card-body">
        @if ($specification)
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h2 class="h6 text-uppercase text-muted mb-0">Cahier des charges</h2>
                        <span class="{{ $specification->status->badgeClass() }}">
                            {{ $specification->status->label() }}
                        </span>
                        <span class="badge text-bg-light border">{{ $specification->reference }}</span>
                    </div>
                    <p class="mb-1">{{ $specification->title }}</p>
                    <p class="text-muted small mb-0">
                        {{ $specification->baseLots()->count() }} lot(s) ·
                        {{ $specification->announced_days_min ?? $specification->daysMin() }}
                        – {{ $specification->announced_days_max ?? $specification->daysMax() }} jours
                        @if ($budget = $specification->budgetRange())
                            · {{ $specification->formatBudget($budget['min']) }}
                            à {{ $specification->formatBudget($budget['max']) }}
                        @endif
                        @unless ($specification->include_in_pdf)
                            · <span class="text-warning-emphasis">non accolé au rapport</span>
                        @endunless
                    </p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('audits.specification.show', $audit) }}"
                       class="btn btn-sm btn-nj-outline">Consulter</a>
                    @can('update', $specification)
                        <a href="{{ route('audits.specification.edit', $audit) }}"
                           class="btn btn-sm btn-outline-secondary">Modifier</a>
                    @endcan
                    <a href="{{ route('audits.specification.pdf', $audit) }}" target="_blank" rel="noopener"
                       class="btn btn-sm btn-outline-secondary">PDF</a>
                </div>
            </div>
        @else
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="h6 text-uppercase text-muted mb-1">Cahier des charges</h2>
                    <p class="text-muted small mb-0">
                        Module facultatif. L'audit constate ; le cahier des charges chiffre le chantier
                        en lots et s'accole au rapport.
                    </p>
                </div>
                @can('update', $audit)
                    <a href="{{ route('audits.specification.create', $audit) }}" class="btn btn-nj-outline">
                        Ajouter un cahier des charges
                    </a>
                @endcan
            </div>
        @endif
    </div>
</div>

<div class="row g-3">
    {{-- Pièces jointes --}}
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Pièces justificatives</h2>

                @forelse ($audit->attachments as $attachment)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div class="min-w-0">
                            <a href="{{ route('audits.attachments.download', [$audit, $attachment]) }}"
                               class="text-truncate d-block">{{ $attachment->original_name }}</a>
                            <span class="small text-muted">
                                {{ $attachment->humanSize() }} ·
                                {{ $attachment->category?->title ?? 'Général' }}
                            </span>
                        </div>
                        @can('update', $audit)
                            <form action="{{ route('audits.attachments.destroy', [$audit, $attachment]) }}" method="POST"
                                  data-confirm="Supprimer « {{ $attachment->original_name }} » ?"
                                  data-confirm-danger="true">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                            </form>
                        @endcan
                    </div>
                @empty
                    <p class="text-muted small">Aucune pièce jointe.</p>
                @endforelse

                @can('update', $audit)
                    <form action="{{ route('audits.attachments.store', $audit) }}" method="POST"
                          enctype="multipart/form-data" class="mt-3">
                        @csrf
                        <div class="row g-2">
                            <div class="col-12">
                                <label for="att-file" class="form-label small">Ajouter une preuve (image ou PDF, 8 Mo max.)</label>
                                <input type="file" id="att-file" name="file" class="form-control form-control-sm"
                                       accept=".png,.jpg,.jpeg,.webp,.pdf" required>
                            </div>
                            <div class="col-7">
                                <select name="audit_category_id" class="form-select form-select-sm"
                                        aria-label="Catégorie rattachée">
                                    <option value="">Général</option>
                                    @foreach ($audit->categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-5 d-grid">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Téléverser</button>
                            </div>
                        </div>
                    </form>
                @endcan
            </div>
        </div>
    </div>

    {{-- Journal + versions --}}
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Historique</h2>

                @if ($audit->versions->isNotEmpty())
                    <p class="small fw-bold mb-2">Versions signées</p>
                    <ul class="list-unstyled small mb-3">
                        @foreach ($audit->versions as $version)
                            <li class="d-flex justify-content-between py-1">
                                <span>Version {{ $version->version }} — {{ $version->author?->name ?? 'Inconnu' }}</span>
                                <span class="text-muted">{{ $version->created_at->format('d/m/Y H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <ul class="timeline">
                    @forelse ($history as $entry)
                        <li>
                            <span class="timeline__icon" aria-hidden="true">{{ $entry->icon() }}</span>
                            <span class="flex-grow-1">
                                <strong>{{ $entry->label() }}</strong>
                                @if ($entry->description)
                                    <span class="text-muted">— {{ $entry->description }}</span>
                                @endif
                                <br>
                                <span class="text-muted" style="font-size:.78rem">
                                    {{ $entry->user_name ?? 'Système' }} ·
                                    {{ $entry->created_at->format('d/m/Y H:i') }}
                                </span>
                            </span>
                        </li>
                    @empty
                        <li class="text-muted">Aucune activité enregistrée.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- ------------------------------------------------------------------
     Formulaires d'action, hors du flux visuel
     ------------------------------------------------------------------ --}}
@can('create', App\Models\Audit::class)
    <form id="form-duplicate" action="{{ route('audits.duplicate', $audit) }}" method="POST" class="d-none">@csrf</form>
@endcan
@can('update', $audit)
    <form id="form-finalize" action="{{ route('audits.finalize', $audit) }}" method="POST" class="d-none">@csrf</form>
    <form id="form-archive" action="{{ route('audits.archive', $audit) }}" method="POST" class="d-none">@csrf</form>
@endcan
<form id="form-unarchive" action="{{ route('audits.unarchive', $audit) }}" method="POST" class="d-none">@csrf</form>

@can('unsign', $audit)
    <form id="form-unsign" action="{{ route('audits.unsign', $audit) }}" method="POST" class="d-none"
          data-confirm="La signature sera retirée et l'audit repassera en brouillon. Le code de vérification cessera de confirmer l'intégrité tant qu'il ne sera pas re-signé."
          data-confirm-title="Retirer la signature de {{ $audit->reference }} ?"
          data-confirm-accept="Retirer la signature">
        @csrf
    </form>
@endcan

@can('delete', $audit)
    <form id="form-delete" action="{{ route('audits.destroy', $audit) }}" method="POST" class="d-none"
          data-confirm="Cet audit sera placé dans la corbeille. Saisissez sa référence pour confirmer."
          data-confirm-title="Supprimer {{ $audit->reference }} ?"
          data-confirm-phrase="{{ $audit->reference }}"
          data-confirm-accept="Mettre à la corbeille"
          data-confirm-danger="true">
        @csrf
        @method('DELETE')
    </form>
@endcan

{{-- Signature : le mot de passe est redemandé, l'acte engage l'auditeur. --}}
@can('sign', $audit)
    <div class="modal fade" id="sign-modal" tabindex="-1" aria-labelledby="sign-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" action="{{ route('audits.sign', $audit) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="sign-modal-title">Signer {{ $audit->reference }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="small">
                        En signant, vous figez le contenu de ce rapport. Une empreinte SHA-256 est calculée
                        et un code de vérification est délivré au client. Le contenu ne pourra plus être
                        modifié sans retirer la signature.
                    </p>
                    <label for="sign-password" class="form-label small">Confirmez votre mot de passe</label>
                    <input type="password" id="sign-password" name="password" class="form-control"
                           autocomplete="current-password" required>
                    @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-nj">Signer</button>
                </div>
            </form>
        </div>
    </div>

    @if ($errors->has('password'))
        @push('scripts')
            <script type="module">
                new window.bootstrap.Modal(document.getElementById('sign-modal')).show();
            </script>
        @endpush
    @endif
@endcan
@endsection
