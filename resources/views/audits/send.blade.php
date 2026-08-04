@extends('layouts.app')

@section('title', 'Envoyer ' . $audit->reference)

@section('content')
<h1 class="h3 mb-1">Envoyer le rapport</h1>
<p class="text-muted mb-4">
    {{ $audit->reference }} — {{ $audit->client_name }}. Le PDF est joint automatiquement.
</p>

@unless ($audit->is_signed)
    <div class="alert alert-warning">
        Ce rapport n'est pas signé. Le PDF envoyé ne portera ni signature ni code de vérification.
    </div>
@endunless

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('audits.send', $audit) }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="to" class="form-label">Destinataire <span aria-hidden="true">*</span></label>
                        <input type="email" id="to" name="to" required
                               class="form-control @error('to') is-invalid @enderror"
                               value="{{ old('to', $audit->client?->contact_email) }}">
                        @error('to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @unless ($audit->client?->contact_email)
                            <div class="form-text">
                                Renseignez l'e-mail sur la
                                <a href="{{ route('clients.edit', $audit->client) }}">fiche client</a>
                                pour le pré-remplir à l'avenir.
                            </div>
                        @endunless
                    </div>

                    <div class="mb-3">
                        <label for="cc" class="form-label">Copie</label>
                        <input type="email" id="cc" name="cc" class="form-control @error('cc') is-invalid @enderror"
                               value="{{ old('cc') }}">
                        @error('cc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="subject" class="form-label">Objet <span aria-hidden="true">*</span></label>
                        <input type="text" id="subject" name="subject" required
                               class="form-control @error('subject') is-invalid @enderror"
                               value="{{ old('subject', "Rapport d'audit {$audit->reference} — {$audit->client_name}") }}">
                        @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="message" class="form-label">Message</label>
                        <textarea id="message" name="message" rows="7" class="form-control">{{ old('message', "Bonjour,\n\nVeuillez trouver ci-joint le rapport d'audit réalisé le ".$audit->audit_date?->format('d/m/Y').".\n\nJe reste à votre disposition pour en échanger.\n\nCordialement,") }}</textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-nj">Envoyer</button>
                        <a href="{{ route('audits.show', $audit) }}" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Pièce jointe</h2>
                <p class="small mb-2">📄 <code>{{ $audit->pdfFilename() }}</code></p>

                @if ($audit->is_signed)
                    <p class="small mb-0">
                        Code de vérification communiqué au client :
                        <code class="fw-bold">{{ $audit->verification_code }}</code>
                    </p>
                @endif

                @if ($audit->sent_at)
                    <hr>
                    <p class="small text-muted mb-0">
                        Dernier envoi : {{ $audit->sent_at->format('d/m/Y à H:i') }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
