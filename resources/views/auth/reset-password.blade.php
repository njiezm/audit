@extends('layouts.guest')

@section('title', 'Nouveau mot de passe')
@section('aside-title', 'Choisissez un nouveau mot de passe')
@section('aside-text', 'Au moins 10 caractères, comprenant des lettres et des chiffres.')

@section('content')
<h2 class="h4 mb-1">Nouveau mot de passe</h2>
<p class="text-muted small mb-4">Choisissez un mot de passe solide.</p>

<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="mb-3">
        <label for="email" class="form-label">Adresse e-mail</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror"
               id="email" name="email" value="{{ old('email', $email) }}" autocomplete="username" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Nouveau mot de passe</label>
        <input type="password" class="form-control @error('password') is-invalid @enderror"
               id="password" name="password" autocomplete="new-password" required>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">10 caractères minimum, lettres et chiffres.</div>
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label">Confirmation</label>
        <input type="password" class="form-control" id="password_confirmation"
               name="password_confirmation" autocomplete="new-password" required>
    </div>

    <button type="submit" class="btn btn-nj w-100 py-2">Enregistrer</button>
</form>
@endsection
