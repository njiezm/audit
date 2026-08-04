@extends('layouts.guest')

@section('title', 'Vérifier un rapport')
@section('aside-title', 'Vérification d\'authenticité')
@section('aside-text', 'Chaque rapport signé porte un code unique et une empreinte de son contenu. Saisissez le code imprimé sur votre PDF pour confirmer que le document est authentique et n\'a pas été modifié.')

@section('content')
<h2 class="h4 mb-1">Vérifier un rapport</h2>
<p class="text-muted small mb-4">
    Le code figure en pied de la page de garde de votre rapport, au format
    <code>XXXX-XXXX-XXXX</code>.
</p>

<form method="POST" action="{{ route('verify.lookup') }}">
    @csrf

    <div class="mb-4">
        <label for="code" class="form-label">Code de vérification</label>
        <input type="text" class="form-control text-uppercase @error('code') is-invalid @enderror"
               id="code" name="code" value="{{ old('code') }}" placeholder="XXXX-XXXX-XXXX"
               maxlength="24" autocomplete="off" required autofocus
               style="letter-spacing:.14em; font-family: ui-monospace, monospace">
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <button type="submit" class="btn btn-nj w-100 py-2">Vérifier</button>
</form>

<p class="text-center small mt-4 mb-0">
    <a href="{{ route('login') }}">Espace auditeur</a>
</p>
@endsection
