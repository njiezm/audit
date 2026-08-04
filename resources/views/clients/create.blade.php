@extends('layouts.app')

@section('title', 'Nouveau client')

@section('content')
<h1 class="h3 mb-4">Nouveau client</h1>

<div class="card">
    <div class="card-body">
        <form action="{{ route('clients.store') }}" method="POST" enctype="multipart/form-data">
            @include('clients.partials.form', ['method' => 'POST', 'submitLabel' => 'Créer le client'])
        </form>
    </div>
</div>
@endsection
