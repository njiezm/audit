@extends('layouts.app')

@section('title', 'Modifier ' . $audit->reference)

@section('content')
    @include('audits.partials.editor', [
        'action' => route('audits.update', $audit),
        'method' => 'PUT',
        'audit' => $audit,
        // Les identifiants sont transmis : les catégories sont réconciliées
        // au lieu d'être détruites puis recréées à chaque enregistrement.
        'categories' => $audit->categories->map(fn ($c) => [
            'id' => $c->id,
            'title' => $c->title,
            'score' => $c->score,
            'weight' => $c->weight,
            'observations' => $c->observations,
            'recommendations' => $c->recommendations,
            'priority' => $c->priority?->value,
            'due_on' => $c->due_on?->format('Y-m-d'),
            'owner' => $c->owner,
        ])->all(),
        'submitLabel' => 'Enregistrer les modifications',
    ])
@endsection
