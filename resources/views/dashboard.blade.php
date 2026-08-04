@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
@php
    $maxVolume = max(1, collect($stats['monthly'])->max('total'));
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Tableau de bord</h1>
        <p class="text-muted mb-0">Vue d'ensemble de votre portefeuille d'audits.</p>
    </div>
    @can('create', App\Models\Audit::class)
        <a href="{{ route('audits.create') }}" class="btn btn-nj">Nouvel audit</a>
    @endcan
</div>

{{-- Indicateurs : aucune de ces valeurs n'était calculée nulle part. --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-tile">
            <div class="stat-tile__label">Audits</div>
            <div class="stat-tile__value">{{ $stats['total'] }}</div>
            <div class="stat-tile__hint">{{ $stats['drafts'] }} brouillon(s)</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-tile">
            <div class="stat-tile__label">Score moyen</div>
            <div class="stat-tile__value" style="color: {{ \App\Support\ScoreScale::color($stats['average_score']) }}">
                {{ $stats['average_score'] !== null ? number_format($stats['average_score'], 1, ',', '') : '—' }}
                <span class="fs-6 text-muted">/ 5</span>
            </div>
            <div class="stat-tile__hint">
                {{ $stats['average_score'] !== null ? \App\Support\ScoreScale::label($stats['average_score']) : 'Aucune donnée' }}
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-tile">
            <div class="stat-tile__label">Signés</div>
            <div class="stat-tile__value">{{ $stats['signed'] }}</div>
            <div class="stat-tile__hint">
                {{ $stats['total'] > 0 ? round($stats['signed'] / $stats['total'] * 100) : 0 }} % du portefeuille
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-tile">
            <div class="stat-tile__label">Clients</div>
            <div class="stat-tile__value">{{ $stats['clients'] }}</div>
            <div class="stat-tile__hint">
                <a href="{{ route('clients.index') }}">Voir la liste</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Volume et score sur 12 mois</h2>

                <div class="bar-chart mb-2">
                    @foreach ($stats['monthly'] as $month)
                        <div class="bar-chart__col">
                            <span class="small text-muted" style="font-size:.66rem">
                                {{ $month['total'] ?: '' }}
                            </span>
                            <div class="bar-chart__track">
                                <div class="bar-chart__bar"
                                     style="height: {{ $month['total'] > 0 ? max(6, round($month['total'] / $maxVolume * 100)) : 3 }}%;
                                            background: {{ $month['average'] !== null ? \App\Support\ScoreScale::color($month['average']) : 'var(--border-strong)' }}"
                                     title="{{ $month['month'] }} — {{ $month['total'] }} audit(s){{ $month['average'] !== null ? ', score moyen '.number_format($month['average'], 1, ',', '') : '' }}"></div>
                            </div>
                            <span class="bar-chart__label">{{ $month['label'] }}</span>
                        </div>
                    @endforeach
                </div>

                <p class="small text-muted mb-0">
                    Hauteur = nombre d'audits. Couleur = score moyen du mois selon le barème.
                </p>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Points faibles récurrents</h2>

                @forelse ($stats['weakest_categories'] as $category)
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="flex-grow-1 min-w-0">
                            <div class="text-truncate fw-semibold">{{ $category->title }}</div>
                            <div class="small text-muted">{{ $category->occurrences }} occurrence(s)</div>
                        </div>
                        <x-score-meter :score="$category->avg_score" :show-label="false" style="max-width:110px" />
                    </div>
                @empty
                    <p class="text-muted mb-0">Pas encore assez de données.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 text-uppercase text-muted mb-0">Derniers audits</h2>
                    <a href="{{ route('audits.index') }}" class="small">Tout voir</a>
                </div>

                @forelse ($stats['recent'] as $audit)
                    <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                        <div class="flex-grow-1 min-w-0">
                            <a href="{{ route('audits.show', $audit) }}" class="fw-semibold text-truncate d-block">
                                {{ $audit->client_name }}
                            </a>
                            <span class="small text-muted">
                                {{ $audit->reference }} · {{ $audit->audit_date?->format('d/m/Y') }}
                            </span>
                        </div>
                        <x-status-badge :status="$audit->status" />
                        <x-score-meter :score="$audit->global_score" :show-label="false" style="max-width:90px" />
                    </div>
                @empty
                    <x-empty-state icon="🗂" title="Aucun audit" description="Créez votre première mission.">
                        @can('create', App\Models\Audit::class)
                            <a href="{{ route('audits.create') }}" class="btn btn-nj btn-sm">Créer un audit</a>
                        @endcan
                    </x-empty-state>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">À traiter</h2>

                @if ($stats['follow_ups']->isNotEmpty())
                    <p class="small fw-bold mb-2">Suivis à programmer</p>
                    @foreach ($stats['follow_ups'] as $audit)
                        <div class="d-flex justify-content-between align-items-center py-1 small">
                            <a href="{{ route('audits.show', $audit) }}" class="text-truncate">{{ $audit->client_name }}</a>
                            <span class="{{ $audit->follow_up_on?->isPast() ? 'text-danger fw-bold' : 'text-muted' }}">
                                {{ $audit->follow_up_on?->format('d/m/Y') }}
                            </span>
                        </div>
                    @endforeach
                    <hr>
                @endif

                <p class="small fw-bold mb-2">Audits non signés</p>
                @forelse ($unsigned as $audit)
                    <div class="d-flex justify-content-between align-items-center py-1 small">
                        <a href="{{ route('audits.show', $audit) }}" class="text-truncate">
                            {{ $audit->reference }} — {{ $audit->client_name }}
                        </a>
                        <span class="text-muted">{{ $audit->audit_date?->format('d/m/Y') }}</span>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Tous les audits sont signés.</p>
                @endforelse

                @if ($topClients->isNotEmpty())
                    <hr>
                    <p class="small fw-bold mb-2">Clients les plus suivis</p>
                    @foreach ($topClients as $client)
                        <div class="d-flex justify-content-between align-items-center py-1 small">
                            <a href="{{ route('clients.show', $client) }}" class="text-truncate">{{ $client->name }}</a>
                            <span class="text-muted">{{ $client->audits_count }} audit(s)</span>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
