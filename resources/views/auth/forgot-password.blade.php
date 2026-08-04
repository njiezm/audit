@extends('layouts.guest')

@section('title', 'Mot de passe oublié')
@section('aside-title', 'Réinitialisation du mot de passe')
@section('aside-text', 'Saisissez votre adresse e-mail : un lien de réinitialisation valable 60 minutes vous sera envoyé.')

@section('content')
<h2 class="h4 mb-1">Mot de passe oublié</h2>
<p class="text-muted small mb-4">Nous vous enverrons un lien de réinitialisation.</p>

<form method="POST" action="{{ route('password.email') }}">
    @csrf

    <div class="mb-4">
        <label for="email" class="form-label">Adresse e-mail</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror"
               id="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <button type="submit" class="btn btn-nj w-100 py-2">Envoyer le lien</button>
</form>

<p class="text-center small mt-4 mb-0">
    <a href="{{ route('login') }}">Retour à la connexion</a>
</p>
@endsection
