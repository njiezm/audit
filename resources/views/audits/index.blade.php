@extends('layouts.app')

@section('title', 'Audits')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Audits</h1>
        <p class="text-muted mb-0">{{ $audits->total() }} audit(s) dans le portefeuille.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('audits.export', request()->query()) }}" class="btn btn-outline-secondary">Exporter en CSV</a>
        <a href="{{ route('audits.trash') }}" class="btn btn-outline-secondary">Corbeille</a>
        @can('create', App\Models\Audit::class)
            <a href="{{ route('audits.create') }}" class="btn btn-nj">Nouvel audit</a>
        @endcan
    </div>
</div>

{{-- Recherche, filtres et tri : la liste n'offrait aucun des trois. --}}
<form method="GET" action="{{ route('audits.index') }}" class="filter-bar" data-no-loading="true">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-lg-3">
            <label for="f-q" class="form-label small fw-semibold">Recherche</label>
            <input type="search" id="f-q" name="q" value="{{ request('q') }}" class="form-control"
                   placeholder="Référence, client, intitulé…">
        </div>
        <div class="col-6 col-lg-2">
            <label for="f-status" class="form-label small fw-semibold">Statut</label>
            <select id="f-status" name="status" class="form-select">
                <option value="">Tous</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <label for="f-client" class="form-label small fw-semibold">Client</label>
            <select id="f-client" name="client_id" class="form-select">
                <option value="">Tous</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}" @selected((string) request('client_id') === (string) $client->id)>
                        {{ $client->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <label for="f-from" class="form-label small fw-semibold">Du</label>
            <input type="date" id="f-from" name="from" value="{{ request('from') }}" class="form-control">
        </div>
        <div class="col-6 col-lg-2">
            <label for="f-to" class="form-label small fw-semibold">Au</label>
            <input type="date" id="f-to" name="to" value="{{ request('to') }}" class="form-control">
        </div>
        <div class="col-12 col-lg-1 d-grid">
            <button type="submit" class="btn btn-nj-outline">Filtrer</button>
        </div>
    </div>

    <div class="row g-2 align-items-end mt-1">
        <div class="col-6 col-lg-2">
            <label for="f-min" class="form-label small fw-semibold">Score min.</label>
            <input type="number" id="f-min" name="min_score" value="{{ request('min_score') }}"
                   class="form-control" min="1" max="5" step="0.5">
        </div>
        <div class="col-6 col-lg-2">
            <label for="f-max" class="form-label small fw-semibold">Score max.</label>
            <input type="number" id="f-max" name="max_score" value="{{ request('max_score') }}"
                   class="form-control" min="1" max="5" step="0.5">
        </div>
        <div class="col-6 col-lg-2">
            <label for="f-per" class="form-label small fw-semibold">Par page</label>
            <select id="f-per" name="per_page" class="form-select">
                @foreach ([10, 15, 25, 50, 100] as $size)
                    <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-lg-6 text-lg-end">
            @if (request()->hasAny(['q', 'status', 'client_id', 'from', 'to', 'min_score', 'max_score']))
                <a href="{{ route('audits.index') }}" class="btn btn-sm btn-outline-secondary">Réinitialiser les filtres</a>
            @endif
        </div>
    </div>
</form>

<form method="POST" action="{{ route('audits.bulk') }}" id="bulk-form">
    @csrf

    <div class="alert alert-secondary d-flex flex-wrap align-items-center gap-2" data-bulk-bar hidden>
        <strong><span data-bulk-count>0</span> sélectionné(s)</strong>
        <button type="submit" name="action" value="archive" class="btn btn-sm btn-outline-secondary">Archiver</button>
        <button type="submit" name="action" value="unarchive" class="btn btn-sm btn-outline-secondary">Désarchiver</button>
        <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger"
                formnovalidate
                onclick="return confirm('Placer les audits sélectionnés dans la corbeille ?')">
            Mettre à la corbeille
        </button>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-stack align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:34px">
                            <input type="checkbox" class="form-check-input" data-select-all
                                   aria-label="Tout sélectionner">
                        </th>
                        <th><x-sort-link column="reference" label="Référence" :sort="$sort" :direction="$direction" /></th>
                        <th><x-sort-link column="client_name" label="Client" :sort="$sort" :direction="$direction" /></th>
                        <th><x-sort-link column="audit_date" label="Date" :sort="$sort" :direction="$direction" /></th>
                        <th><x-sort-link column="global_score" label="Score" :sort="$sort" :direction="$direction" /></th>
                        <th><x-sort-link column="status" label="Statut" :sort="$sort" :direction="$direction" /></th>
                        <th class="text-center">Catég.</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($audits as $audit)
                        <tr>
                            <td data-label="">
                                <input type="checkbox" class="form-check-input" name="ids[]" value="{{ $audit->id }}"
                                       data-row-select aria-label="Sélectionner {{ $audit->reference }}">
                            </td>
                            <td data-label="Référence">
                                <a href="{{ route('audits.show', $audit) }}" class="fw-semibold">{{ $audit->reference }}</a>
                                @if ($audit->title)
                                    <div class="small text-muted">{{ $audit->title }}</div>
                                @endif
                            </td>
                            <td data-label="Client">
                                @if ($audit->client)
                                    <a href="{{ route('clients.show', $audit->client) }}">{{ $audit->client_name }}</a>
                                @else
                                    {{ $audit->client_name }}
                                @endif
                            </td>
                            <td data-label="Date">{{ $audit->audit_date?->format('d/m/Y') }}</td>
                            <td data-label="Score"><x-score-meter :score="$audit->global_score" /></td>
                            <td data-label="Statut"><x-status-badge :status="$audit->status" /></td>
                            <td data-label="Catégories" class="text-center">{{ $audit->categories_count }}</td>
                            <td data-label="Actions" class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('audits.show', $audit) }}">Consulter</a></li>
                                        @can('update', $audit)
                                            <li><a class="dropdown-item" href="{{ route('audits.edit', $audit) }}">Modifier</a></li>
                                        @endcan
                                        <li>
                                            <a class="dropdown-item" href="{{ route('audits.previewPdf', $audit) }}" target="_blank" rel="noopener">
                                                Aperçu PDF
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('audits.downloadPdf', $audit) }}">
                                                Télécharger le PDF
                                            </a>
                                        </li>
                                        @can('create', App\Models\Audit::class)
                                            <li>
                                                <button type="submit" form="dup-{{ $audit->id }}" class="dropdown-item">
                                                    Dupliquer
                                                </button>
                                            </li>
                                        @endcan
                                        @can('delete', $audit)
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button type="submit" form="del-{{ $audit->id }}"
                                                        class="dropdown-item text-danger">Supprimer</button>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-empty-state icon="🔍"
                                               title="Aucun audit ne correspond"
                                               description="Ajustez vos filtres ou créez une nouvelle mission.">
                                    @can('create', App\Models\Audit::class)
                                        <a href="{{ route('audits.create') }}" class="btn btn-nj btn-sm">Créer un audit</a>
                                    @endcan
                                </x-empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

{{-- Les formulaires d'action vivent hors du formulaire d'actions groupées :
     HTML interdit les formulaires imbriqués. --}}
@foreach ($audits as $audit)
    @can('create', App\Models\Audit::class)
        <form id="dup-{{ $audit->id }}" action="{{ route('audits.duplicate', $audit) }}" method="POST" class="d-none">
            @csrf
        </form>
    @endcan
    @can('delete', $audit)
        <form id="del-{{ $audit->id }}" action="{{ route('audits.destroy', $audit) }}" method="POST" class="d-none"
              data-confirm="L'audit {{ $audit->reference }} sera placé dans la corbeille. Vous pourrez le restaurer."
              data-confirm-title="Supprimer {{ $audit->reference }} ?"
              data-confirm-accept="Mettre à la corbeille"
              data-confirm-danger="true">
            @csrf
            @method('DELETE')
        </form>
    @endcan
@endforeach

<div class="d-flex justify-content-center mt-4">
    {{ $audits->onEachSide(1)->links() }}
</div>
@endsection
