@extends('layouts.app')

@section('title', 'Cahier des charges — ' . $audit->reference)

@section('content')
<div class="mb-4">
    <p class="text-muted mb-1">
        <a href="{{ route('audits.show', $audit) }}">{{ $audit->reference }}</a> ·
        {{ $audit->client_name }}
    </p>
    <h1 class="h3 mb-1">Nouveau cahier des charges</h1>
    <p class="text-muted mb-0">
        L'audit constate, le cahier des charges engage. Il sera accolé au rapport
        et pourra être téléchargé séparément.
    </p>
</div>

<form action="{{ route('audits.specification.store', $audit) }}" method="POST" data-specification-form>
    @include('specifications.partials.form', ['method' => 'POST', 'submitLabel' => 'Créer le cahier des charges'])
</form>

<datalist id="phase-suggestions">
    <option value="1 — Socle opérationnel"></option>
    <option value="2 — Pilotage et finance"></option>
    <option value="3 — Communication et commercialisation"></option>
</datalist>
@endsection
