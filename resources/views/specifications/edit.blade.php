@extends('layouts.app')

@section('title', 'Modifier ' . $specification->reference)

@section('content')
<div class="mb-4">
    <p class="text-muted mb-1">
        <a href="{{ route('audits.show', $audit) }}">{{ $audit->reference }}</a> ·
        {{ $audit->client_name }}
    </p>
    <h1 class="h3 mb-0">Modifier {{ $specification->reference }}</h1>
</div>

<form action="{{ route('audits.specification.update', $audit) }}" method="POST" data-specification-form>
    @include('specifications.partials.form', ['method' => 'PUT', 'submitLabel' => 'Enregistrer'])
</form>

<datalist id="phase-suggestions">
    @foreach ($specification->lots->pluck('phase')->filter()->unique() as $phase)
        <option value="{{ $phase }}"></option>
    @endforeach
    <option value="1 — Socle opérationnel"></option>
    <option value="2 — Pilotage et finance"></option>
    <option value="3 — Communication et commercialisation"></option>
</datalist>
@endsection
