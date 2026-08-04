@extends('layouts.app')

@section('title', 'Utilisateurs')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Utilisateurs</h1>
        <p class="text-muted mb-0">
            Chaque auditeur ne voit que ses propres missions ; un administrateur voit tout le portefeuille.
        </p>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-nj">Nouveau compte</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-stack align-middle mb-0">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Rôle</th>
                    <th class="text-center">Audits</th>
                    <th>Dernière connexion</th>
                    <th>État</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td data-label="Utilisateur">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar-chip">{{ $user->initials() }}</span>
                                <div>
                                    <div class="fw-semibold">{{ $user->name }}</div>
                                    <div class="small text-muted">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Rôle">{{ $user->role->label() }}</td>
                        <td data-label="Audits" class="text-center">{{ $user->audits_count }}</td>
                        <td data-label="Dernière connexion">{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td data-label="État">
                            @if ($user->trashed())
                                <span class="badge-status badge-status--archived">Archivé</span>
                            @elseif ($user->is_active)
                                <span class="badge-status badge-status--signed">Actif</span>
                            @else
                                <span class="badge-status badge-status--draft">Désactivé</span>
                            @endif
                        </td>
                        <td data-label="Actions" class="text-end">
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Modifier</a>

                            @if ($user->id !== auth()->id() && ! $user->trashed())
                                <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline"
                                      data-confirm="Le compte de {{ $user->name }} sera désactivé et archivé. Ses audits sont conservés."
                                      data-confirm-title="Désactiver ce compte ?"
                                      data-confirm-danger="true">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Désactiver</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-center mt-4">{{ $users->links() }}</div>
@endsection
