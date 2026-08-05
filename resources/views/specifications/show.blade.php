@extends('layouts.app')

@section('title', $specification->reference)

@section('content')
@php
    $margin = $specification->announcedMargin();
    $budget = $specification->budgetRange();
    $phases = $specification->lotsByPhase();
    $options = $specification->optionLots();
    $risks = $specification->risks();
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <p class="text-muted mb-1">
            <a href="{{ route('audits.show', $audit) }}">{{ $audit->reference }}</a> ·
            {{ $audit->client_name }}
        </p>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <h1 class="h3 mb-0">{{ $specification->reference }}</h1>
            <span class="{{ $specification->status->badgeClass() }}">{{ $specification->status->label() }}</span>
            <span class="badge text-bg-light border">v{{ $specification->version }}</span>
            @unless ($specification->include_in_pdf)
                <span class="badge text-bg-light border">Non accolé au rapport</span>
            @endunless
        </div>
        <p class="text-muted mb-0">{{ $specification->title }}</p>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        @can('update', $specification)
            <a href="{{ route('audits.specification.edit', $audit) }}" class="btn btn-outline-secondary">Modifier</a>
        @endcan
        <a href="{{ route('audits.specification.pdf', $audit) }}" target="_blank" rel="noopener"
           class="btn btn-nj-outline">PDF du cahier</a>
        <a href="{{ route('audits.downloadPdf', $audit) }}" class="btn btn-outline-secondary">
            Rapport complet
        </a>
        @can('delete', $specification)
            <form action="{{ route('audits.specification.destroy', $audit) }}" method="POST"
                  data-confirm="Le cahier des charges {{ $specification->reference }} sera supprimé. L'audit n'est pas affecté."
                  data-confirm-title="Supprimer le cahier des charges ?"
                  data-confirm-danger="true">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Supprimer</button>
            </form>
        @endcan
    </div>
</div>

{{-- Charges --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-tile">
            <div class="stat-tile__label">Somme des lots</div>
            <div class="stat-tile__value">{{ $specification->daysMin() }} – {{ $specification->daysMax() }}</div>
            <div class="stat-tile__hint">jours-homme, hors options</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-tile">
            <div class="stat-tile__label">Enveloppe annoncée</div>
            <div class="stat-tile__value">
                @if ($specification->hasAnnouncedEnvelope())
                    {{ $specification->announced_days_min ?? $specification->daysMin() }}
                    – {{ $specification->announced_days_max ?? $specification->daysMax() }}
                @else
                    —
                @endif
            </div>
            <div class="stat-tile__hint">
                @if ($margin && ($margin['min'] !== 0 || $margin['max'] !== 0))
                    <span class="{{ $margin['min'] < 0 || $margin['max'] < 0 ? 'text-danger fw-bold' : '' }}">
                        marge {{ $margin['min'] > 0 ? '+' : '' }}{{ $margin['min'] }}
                        à {{ $margin['max'] > 0 ? '+' : '' }}{{ $margin['max'] }} j
                    </span>
                @else
                    identique à la somme des lots
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-tile">
            <div class="stat-tile__label">Budget estimatif</div>
            <div class="stat-tile__value" style="font-size:1.3rem">
                @if ($budget)
                    {{ $specification->formatBudget($budget['min']) }}
                @else
                    —
                @endif
            </div>
            <div class="stat-tile__hint">
                @if ($budget)
                    à {{ $specification->formatBudget($budget['max']) }}
                @else
                    taux journalier non renseigné
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-tile">
            <div class="stat-tile__label">Lots</div>
            <div class="stat-tile__value">{{ $specification->baseLots()->count() }}</div>
            <div class="stat-tile__hint">
                {{ $options->count() }} option(s) · {{ $risks->count() }} à risque
            </div>
        </div>
    </div>
</div>

<div class="alert alert-secondary small">
    <strong>Jours-homme, et non jours de calendrier.</strong>
    Un jour-homme correspond à une personne travaillant une journée pleine sur ce seul projet.
    La durée réelle dépend de la cadence hebdomadaire convenue, des autres engagements en cours
    et des délais de validation du client. La charge et le délai sont deux engagements distincts.
</div>

@if ($risks->isNotEmpty())
    <div class="alert alert-warning">
        <p class="fw-bold mb-2">Lots à risque</p>
        <ul class="mb-0 ps-3">
            @foreach ($risks as $risk)
                <li>
                    <strong>{{ $risk->code ? $risk->code.' — ' : '' }}{{ $risk->name }}</strong>
                    @if ($risk->risk_note) — {{ $risk->risk_note }} @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Cadrage --}}
<div class="row g-3 mb-4">
    @foreach ([
        ['Contexte', $specification->context],
        ['Objectifs', $specification->objectives],
        ['Périmètre inclus', $specification->scope_in],
        ['Périmètre exclu', $specification->scope_out],
    ] as [$label, $body])
        @if (filled($body))
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="h6 text-uppercase text-muted mb-2">{{ $label }}</h2>
                        <div class="rich">@rich($body)</div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>

{{-- Lots par phase --}}
@if ($phases->isNotEmpty())
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-3">Lots de travaux</h2>

            @foreach ($phases as $phaseName => $phase)
                <div class="d-flex justify-content-between align-items-center mb-2 mt-3">
                    <h3 class="h6 mb-0">{{ $phaseName }}</h3>
                    <span class="badge text-bg-light border">
                        {{ $phase['days_min'] }} – {{ $phase['days_max'] }} j
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-stack align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:52px">N°</th>
                                <th>Lot</th>
                                <th>Contenu</th>
                                <th class="text-end" style="width:96px">Charge</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($phase['lots'] as $lot)
                                <tr>
                                    <td data-label="N°">{{ $lot->codeLabel() }}</td>
                                    <td data-label="Lot">
                                        <strong>{{ $lot->name }}</strong>
                                        @if ($lot->is_at_risk)
                                            <span class="badge text-bg-warning ms-1">risque</span>
                                        @endif
                                    </td>
                                    <td data-label="Contenu" class="rich small">@rich($lot->content)</td>
                                    <td data-label="Charge" class="text-end text-nowrap">{{ $lot->daysLabel() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    </div>
@endif

@if ($options->isNotEmpty())
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-3">Options hors périmètre de base</h2>
            <div class="table-responsive">
                <table class="table table-sm table-stack align-middle mb-0">
                    <tbody>
                        @foreach ($options as $lot)
                            <tr>
                                <td data-label="Option">
                                    <strong>{{ $lot->name }}</strong>
                                    @if ($lot->content)
                                        <div class="small text-muted">{{ $lot->content }}</div>
                                    @endif
                                </td>
                                <td data-label="Charge" class="text-end text-nowrap">{{ $lot->daysLabel() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

{{-- Sections libres --}}
@foreach ($specification->sections as $section)
    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-2">{{ $section->title }}</h2>
            <div class="rich">@rich($section->body)</div>
        </div>
    </div>
@endforeach
@endsection
