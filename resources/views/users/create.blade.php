@extends('layouts.app')

@section('title', 'Nouveau compte')

@section('content')
<h1 class="h3 mb-4">Nouveau compte</h1>

<div class="card">
    <div class="card-body">
        <form action="{{ route('users.store') }}" method="POST">
            @include('users.partials.form', ['method' => 'POST', 'submitLabel' => 'Créer le compte'])
        </form>
    </div>
</div>
@endsection
