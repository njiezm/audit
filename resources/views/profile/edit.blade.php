@extends('layouts.app')

@section('title', 'Mon profil')

@section('content')
<h1 class="h3 mb-4">Mon profil</h1>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Informations</h2>

                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">Nom affiché</label>
                        <input type="text" id="name" name="name" required
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Ce nom apparaît sur les rapports que vous signez.</div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse e-mail</label>
                        <input type="email" id="email" name="email" required
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="job_title" class="form-label">Fonction</label>
                        <input type="text" id="job_title" name="job_title" class="form-control"
                               value="{{ old('job_title', $user->job_title) }}" placeholder="Auditeur principal">
                    </div>

                    <div class="mb-3">
                        <label for="signature" class="form-label">Signature manuscrite</label>
                        <input type="file" id="signature" name="signature"
                               class="form-control @error('signature') is-invalid @enderror"
                               accept="image/png,image/jpeg,image/webp">
                        @error('signature')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">
                            Image sur fond transparent de préférence. Sans signature propre, l'image
                            livrée par défaut est utilisée pour tout le monde.
                        </div>

                        @if ($user->signatureUrl())
                            <img src="{{ $user->signatureUrl() }}" alt="Votre signature actuelle"
                                 class="mt-2 border rounded p-2 bg-white" style="max-height:60px">
                        @endif
                    </div>

                    <button type="submit" class="btn btn-nj">Enregistrer</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Compte</h2>
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Rôle</dt>
                    <dd class="col-7">{{ $user->role->label() }}</dd>
                    <dt class="col-5 text-muted">Audits</dt>
                    <dd class="col-7">{{ $user->audits()->count() }}</dd>
                    <dt class="col-5 text-muted">Dernière connexion</dt>
                    <dd class="col-7">{{ $user->last_login_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Mot de passe</h2>

                <form action="{{ route('profile.password') }}" method="POST">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mot de passe actuel</label>
                        <input type="password" id="current_password" name="current_password" required
                               autocomplete="current-password"
                               class="form-control @error('current_password') is-invalid @enderror">
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Nouveau mot de passe</label>
                        <input type="password" id="password" name="password" required autocomplete="new-password"
                               class="form-control @error('password') is-invalid @enderror">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">10 caractères minimum, lettres et chiffres.</div>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmation</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               required autocomplete="new-password" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-outline-secondary">Modifier le mot de passe</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
