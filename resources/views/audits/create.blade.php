@extends('layouts.app')

@section('title', 'Nouvel audit')

@section('content')
    @include('audits.partials.editor', [
        'action' => route('audits.store'),
        'method' => 'POST',
        'audit' => null,
        'categories' => $initialCategories,
        'submitLabel' => "Créer l'audit",
    ])
@endsection
