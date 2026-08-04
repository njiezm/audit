@extends('layouts.app')

@section('title', 'Modifier ' . $template->name)

@section('content')
<h1 class="h3 mb-4">Modifier « {{ $template->name }} »</h1>

<div class="card">
    <div class="card-body">
        <form action="{{ route('templates.update', $template) }}" method="POST">
            @include('templates.partials.form', ['method' => 'PUT', 'submitLabel' => 'Enregistrer'])
        </form>
    </div>
</div>
@endsection
