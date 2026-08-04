@extends('layouts.app')

@section('title', "Journal d'activité")

@section('content')
<div class="mb-3">
    <h1 class="h3 mb-1">Journal d'activité</h1>
    <p class="text-muted mb-0">
        Trace horodatée de toutes les actions : créations, modifications, signatures,
        téléchargements, connexions.
    </p>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-stack align-middle mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Utilisateur</th>
                    <th>Action</th>
                    <th>Objet</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td data-label="Date" class="text-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td data-label="Utilisateur">{{ $log->user_name ?? 'Anonyme' }}</td>
                        <td data-label="Action">
                            <span aria-hidden="true">{{ $log->icon() }}</span> {{ $log->label() }}
                        </td>
                        <td data-label="Objet">
                            @if ($log->subject_type === App\Models\Audit::class && $log->subject_id)
                                <a href="{{ route('audits.show', $log->subject_id) }}">
                                    {{ $log->description ?: 'Audit #'.$log->subject_id }}
                                </a>
                            @else
                                {{ $log->description ?: '—' }}
                            @endif
                        </td>
                        <td data-label="IP" class="small text-muted">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5"><x-empty-state icon="📜" title="Aucune activité enregistrée" /></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-center mt-4">{{ $logs->links() }}</div>
@endsection
