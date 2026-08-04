@extends('layouts.app')

@section('title', 'Nouveau modèle')

@section('content')
<h1 class="h3 mb-4">Nouveau modèle d'audit</h1>

<div class="card">
    <div class="card-body">
        <form action="{{ route('templates.store') }}" method="POST">
            @include('templates.partials.form', ['method' => 'POST', 'submitLabel' => 'Créer le modèle'])
        </form>
    </div>
</div>
@endsection
