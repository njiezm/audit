@extends('layouts.app')

@section('title', 'Modifier ' . $client->name)

@section('content')
<h1 class="h3 mb-4">Modifier {{ $client->name }}</h1>

<div class="card">
    <div class="card-body">
        <form action="{{ route('clients.update', $client) }}" method="POST" enctype="multipart/form-data">
            @include('clients.partials.form', ['method' => 'PUT', 'submitLabel' => 'Enregistrer'])
        </form>
    </div>
</div>

@can('delete', $client)
    <div class="card mt-3 border-danger">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <p class="fw-bold mb-1">Supprimer ce client</p>
                <p class="text-muted small mb-0">
                    Possible uniquement si aucun audit ne lui est rattaché.
                </p>
            </div>
            <form action="{{ route('clients.destroy', $client) }}" method="POST"
                  data-confirm="Le client {{ $client->name }} sera supprimé."
                  data-confirm-title="Supprimer {{ $client->name }} ?"
                  data-confirm-danger="true">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">Supprimer</button>
            </form>
        </div>
    </div>
@endcan
@endsection
