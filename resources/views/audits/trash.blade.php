@extends('layouts.app')

@section('title', 'Corbeille')

@section('content')
<div class="mb-3">
    <h1 class="h3 mb-1">Corbeille</h1>
    <p class="text-muted mb-0">
        Les audits supprimés sont conservés ici et restent restaurables.
        Auparavant, une suppression était définitive et emportait toutes les catégories.
    </p>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-stack align-middle mb-0">
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Client</th>
                    <th>Date de l'audit</th>
                    <th>Supprimé le</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($audits as $audit)
                    <tr>
                        <td data-label="Référence">{{ $audit->reference }}</td>
                        <td data-label="Client">{{ $audit->client_name }}</td>
                        <td data-label="Date">{{ $audit->audit_date?->format('d/m/Y') }}</td>
                        <td data-label="Supprimé le">{{ $audit->deleted_at?->format('d/m/Y H:i') }}</td>
                        <td data-label="Actions" class="text-end">
                            <form action="{{ route('audits.restore', $audit->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success">Restaurer</button>
                            </form>

                            @if (auth()->user()->isAdmin())
                                <form action="{{ route('audits.forceDestroy', $audit->id) }}" method="POST" class="d-inline"
                                      data-confirm="L'audit {{ $audit->reference }} sera détruit définitivement, sans retour possible. Saisissez sa référence pour confirmer."
                                      data-confirm-title="Suppression définitive"
                                      data-confirm-phrase="{{ $audit->reference }}"
                                      data-confirm-accept="Supprimer définitivement"
                                      data-confirm-danger="true">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Détruire</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-empty-state icon="🗑" title="La corbeille est vide" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-center mt-4">{{ $audits->links() }}</div>
@endsection
