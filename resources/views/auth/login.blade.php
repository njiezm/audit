@extends('layouts.guest')

@section('title', 'Connexion')

@section('content')
<h2 class="h4 mb-1">Connexion</h2>
<p class="text-muted small mb-4">Accédez à votre espace d'audit.</p>

<form method="POST" action="{{ route('login.authenticate') }}">
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label">Adresse e-mail</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror"
               id="email" name="email" value="{{ old('email') }}"
               autocomplete="username" required autofocus>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Mot de passe</label>
        <input type="password" class="form-control @error('password') is-invalid @enderror"
               id="password" name="password" autocomplete="current-password" required>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1"
                   @checked(old('remember'))>
            <label class="form-check-label small" for="remember">Rester connecté</label>
        </div>
        <a href="{{ route('password.request') }}" class="small">Mot de passe oublié ?</a>
    </div>

    <button type="submit" class="btn btn-nj w-100 py-2">Se connecter</button>
</form>

<p class="text-center small text-muted mt-4 mb-0">
    <a href="{{ route('verify.form') }}">Vérifier l'authenticité d'un rapport</a>
</p>
@endsection
