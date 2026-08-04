@extends('layouts.app')

@section('title', 'Modifier ' . $user->name)

@section('content')
<h1 class="h3 mb-4">Modifier {{ $user->name }}</h1>

<div class="card">
    <div class="card-body">
        <form action="{{ route('users.update', $user) }}" method="POST">
            @include('users.partials.form', ['method' => 'PUT', 'submitLabel' => 'Enregistrer'])
        </form>
    </div>
</div>
@endsection
