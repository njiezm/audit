@extends('layouts.app')

@section('title', 'Liste des audits - Audit Master')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Liste des audits</h1>
        <a href="{{ route('audits.create') }}" class="btn btn-nj">Créer un nouvel audit</a>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($audits as $audit)
                            <tr>
                                <td>{{ $audit->audit_id }}</td>
                                <td>{{ $audit->client_name }}</td>
                                <td>{{ \Carbon\Carbon::parse($audit->audit_date)->format('d/m/Y') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('audits.show', $audit->id) }}" class="btn btn-sm btn-outline-primary">Voir</a>
                                        <a href="{{ route('audits.edit', $audit->id) }}" class="btn btn-sm btn-outline-secondary">Modifier</a>
                                        <form action="{{ route('audits.destroy', $audit->id) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet audit?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">Aucun audit trouvé</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">
                {{ $audits->links() }}
            </div>
        </div>
    </div>
</div>
@endsection