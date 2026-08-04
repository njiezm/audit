@extends('layouts.app')

@section('title', 'Clients')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Clients</h1>
        <p class="text-muted mb-0">{{ $clients->total() }} client(s) au portefeuille.</p>
    </div>
    @can('create', App\Models\Client::class)
        <a href="{{ route('clients.create') }}" class="btn btn-nj">Nouveau client</a>
    @endcan
</div>

<form method="GET" class="filter-bar" data-no-loading="true">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-8">
            <label for="q" class="form-label small fw-semibold">Recherche</label>
            <input type="search" id="q" name="q" value="{{ request('q') }}" class="form-control"
                   placeholder="Raison sociale, SIRET, secteur, e-mail…">
        </div>
        <div class="col-6 col-md-2 d-grid">
            <button type="submit" class="btn btn-nj-outline">Rechercher</button>
        </div>
        <div class="col-6 col-md-2 d-grid">
            <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-stack align-middle mb-0">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Secteur</th>
                    <th>Contact</th>
                    <th class="text-center">Audits</th>
                    <th>Score moyen</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clients as $client)
                    <tr>
                        <td data-label="Client">
                            <a href="{{ route('clients.show', $client) }}" class="fw-semibold">{{ $client->name }}</a>
                            @if ($client->siret)
                                <div class="small text-muted">SIRET {{ $client->siret }}</div>
                            @endif
                        </td>
                        <td data-label="Secteur">{{ $client->sector ?: '—' }}</td>
                        <td data-label="Contact">
                            @if ($client->contact_email)
                                <a href="mailto:{{ $client->contact_email }}">{{ $client->contact_email }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td data-label="Audits" class="text-center">{{ $client->audits_count }}</td>
                        <td data-label="Score moyen">
                            <x-score-meter :score="$client->average_score" />
                        </td>
                        <td data-label="Actions" class="text-end">
                            <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-outline-secondary">Fiche</a>
                            @can('update', $client)
                                <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-secondary">Modifier</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <x-empty-state icon="🏢" title="Aucun client"
                                           description="Les clients sont créés automatiquement à l'enregistrement d'un audit.">
                                @can('create', App\Models\Client::class)
                                    <a href="{{ route('clients.create') }}" class="btn btn-nj btn-sm">Créer un client</a>
                                @endcan
                            </x-empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-center mt-4">{{ $clients->links() }}</div>
@endsection
