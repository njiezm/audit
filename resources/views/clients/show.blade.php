@extends('layouts.app')

@section('title', $client->name)

@section('content')
@php
    // Tout le calcul est regroupé ici. La forme PHP en ligne est proscrite :
    // le compilateur Blade la confond avec l'ouverture d'un bloc et absorbe
    // alors tout le balisage jusqu'à la fermeture suivante.
    $scored = collect($trend);
    $max = \App\Support\ScoreScale::MAX;
    $avg = $client->averageScore();
    $trendValue = $client->scoreTrend();

    $w = 640;
    $h = 160;
    $pad = 26;
    $step = $scored->count() > 1 ? ($w - 2 * $pad) / ($scored->count() - 1) : 0;

    $points = $scored->map(function ($p, $i) use ($pad, $step, $h, $max) {
        $x = $pad + $i * $step;
        $y = $h - $pad - (($p['value'] / $max) * ($h - 2 * $pad));

        return ['x' => round($x, 1), 'y' => round($y, 1)] + $p;
    });

    // Ordonnée de chaque graduation du barème.
    $gridLines = collect(range(1, $max))->map(fn ($level) => [
        'level' => $level,
        'y' => round($h - $pad - (($level / $max) * ($h - 2 * $pad)), 1),
    ]);
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        @if ($client->logo_path)
            <img src="{{ asset('storage/'.$client->logo_path) }}" alt=""
                 class="border rounded p-1 bg-white" style="max-height:56px">
        @endif
        <div>
            <h1 class="h3 mb-1">{{ $client->name }}</h1>
            <p class="text-muted mb-0">
                {{ $client->sector ?: 'Secteur non renseigné' }}
                @if ($client->siret) · SIRET {{ $client->siret }} @endif
            </p>
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        @can('update', $client)
            <a href="{{ route('clients.edit', $client) }}" class="btn btn-outline-secondary">Modifier</a>
        @endcan
        @can('create', App\Models\Audit::class)
            <a href="{{ route('audits.create') }}" class="btn btn-nj">Nouvel audit</a>
        @endcan
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-tile">
            <div class="stat-tile__label">Audits</div>
            <div class="stat-tile__value">{{ $audits->count() }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-tile">
            <div class="stat-tile__label">Score moyen</div>
            <div class="stat-tile__value" style="color: {{ \App\Support\ScoreScale::color($avg) }}">
                {{ $avg !== null ? number_format($avg, 1, ',', '') : '—' }}
            </div>
            <div class="stat-tile__hint">{{ $avg !== null ? \App\Support\ScoreScale::label($avg) : 'Pas de donnée' }}</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-tile">
            <div class="stat-tile__label">Tendance</div>
            <div class="stat-tile__value {{ $trendValue === null ? '' : ($trendValue >= 0 ? 'text-success' : 'text-danger') }}">
                {{ $trendValue === null ? '—' : ($trendValue > 0 ? '+' : '').number_format($trendValue, 1, ',', '') }}
            </div>
            <div class="stat-tile__hint">Depuis l'audit précédent</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-tile">
            <div class="stat-tile__label">Contact</div>
            <div class="small mt-1">
                @if ($client->contact_name)<div>{{ $client->contact_name }}</div>@endif
                @if ($client->contact_email)
                    <a href="mailto:{{ $client->contact_email }}">{{ $client->contact_email }}</a>
                @endif
                @if ($client->contact_phone)<div>{{ $client->contact_phone }}</div>@endif
                @if (! $client->contact_name && ! $client->contact_email)
                    <span class="text-muted">Non renseigné</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Évolution du score dans le temps : impossible à lire jusqu'ici, faute de
     rattachement des audits à un client. --}}
@if ($scored->count() >= 2)
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-3">Évolution du score</h2>

            <svg viewBox="0 0 {{ $w }} {{ $h }}" class="sparkline" style="height:170px" role="img"
                 aria-label="Évolution du score global de {{ $client->name }}">
                @foreach ($gridLines as $line)
                    <line x1="{{ $pad }}" y1="{{ $line['y'] }}" x2="{{ $w - $pad }}" y2="{{ $line['y'] }}"
                          stroke="var(--border)" stroke-width="1"/>
                    <text x="4" y="{{ $line['y'] + 4 }}" font-size="10" fill="var(--text-muted)">{{ $line['level'] }}</text>
                @endforeach

                <polyline fill="none" stroke="var(--nj-blue)" stroke-width="2.5"
                          points="{{ $points->map(fn ($p) => $p['x'].','.$p['y'])->implode(' ') }}"/>

                @foreach ($points as $p)
                    <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="4"
                            fill="{{ \App\Support\ScoreScale::color($p['value']) }}">
                        <title>{{ $p['reference'] }} — {{ number_format($p['value'], 1, ',', '') }}/5</title>
                    </circle>
                    <text x="{{ $p['x'] }}" y="{{ $h - 6 }}" font-size="10" text-anchor="middle"
                          fill="var(--text-muted)">{{ $p['label'] }}</text>
                @endforeach
            </svg>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <h2 class="h6 text-uppercase text-muted mb-3">Historique des audits</h2>

        <div class="table-responsive">
            <table class="table table-hover table-stack align-middle mb-0">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Date</th>
                        <th>Score</th>
                        <th>Statut</th>
                        <th class="text-center">Catég.</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($audits as $audit)
                        <tr>
                            <td data-label="Référence">
                                <a href="{{ route('audits.show', $audit) }}" class="fw-semibold">{{ $audit->reference }}</a>
                                @if ($audit->title)<div class="small text-muted">{{ $audit->title }}</div>@endif
                            </td>
                            <td data-label="Date">{{ $audit->audit_date?->format('d/m/Y') }}</td>
                            <td data-label="Score"><x-score-meter :score="$audit->global_score" /></td>
                            <td data-label="Statut"><x-status-badge :status="$audit->status" /></td>
                            <td data-label="Catégories" class="text-center">{{ $audit->categories_count }}</td>
                            <td data-label="Actions" class="text-end">
                                <a href="{{ route('audits.downloadPdf', $audit) }}"
                                   class="btn btn-sm btn-outline-secondary">PDF</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-empty-state icon="📋" title="Aucun audit pour ce client" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if ($client->notes)
    <div class="card mt-3">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-2">Notes internes</h2>
            <div>@nl($client->notes)</div>
        </div>
    </div>
@endif
@endsection
