@extends('layouts.app')

@section('title', "Modèles d'audit")

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Modèles d'audit</h1>
        <p class="text-muted mb-0">
            Une grille prête à l'emploi évite de retaper les mêmes catégories à chaque mission.
        </p>
    </div>
    @can('create', App\Models\AuditTemplate::class)
        <a href="{{ route('templates.create') }}" class="btn btn-nj">Nouveau modèle</a>
    @endcan
</div>

<div class="row g-3">
    @forelse ($templates as $template)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <h2 class="h6 mb-0">{{ $template->name }}</h2>
                        @if ($template->is_default)
                            <span class="badge-status badge-status--signed">Par défaut</span>
                        @endif
                    </div>

                    <p class="text-muted small flex-grow-1">{{ $template->description }}</p>

                    <p class="small mb-3">
                        <span class="badge text-bg-light border">{{ $template->categories_count }} catégorie(s)</span>
                    </p>

                    <div class="d-flex gap-2 flex-wrap">
                        @can('create', App\Models\Audit::class)
                            <a href="{{ route('audits.create', ['template' => $template->id]) }}"
                               class="btn btn-sm btn-nj-outline">Démarrer un audit</a>
                        @endcan
                        @can('update', $template)
                            <a href="{{ route('templates.edit', $template) }}"
                               class="btn btn-sm btn-outline-secondary">Modifier</a>
                        @endcan
                        @can('delete', $template)
                            <form action="{{ route('templates.destroy', $template) }}" method="POST"
                                  data-confirm="Le modèle « {{ $template->name }} » sera supprimé. Les audits déjà créés ne sont pas affectés."
                                  data-confirm-title="Supprimer ce modèle ?" data-confirm-danger="true">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <x-empty-state icon="🧩" title="Aucun modèle"
                                   description="Créez une grille réutilisable pour vos missions récurrentes.">
                        @can('create', App\Models\AuditTemplate::class)
                            <a href="{{ route('templates.create') }}" class="btn btn-nj btn-sm">Créer un modèle</a>
                        @endcan
                    </x-empty-state>
                </div>
            </div>
        </div>
    @endforelse
</div>
@endsection
