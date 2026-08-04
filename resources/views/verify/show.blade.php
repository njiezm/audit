@extends('layouts.guest')

@section('title', 'Résultat de la vérification')
@section('aside-title', 'Vérification d\'authenticité')
@section('aside-text', 'Le résultat ci-contre porte uniquement sur l\'existence du rapport et l\'intégrité de son contenu. Le détail de l\'audit n\'est jamais divulgué publiquement.')

@section('content')
@if ($audit === null)
    <div class="integrity-banner integrity-banner--unknown mb-4">
        <span style="font-size:1.6rem" aria-hidden="true">✕</span>
        <div>
            <p class="fw-bold mb-1">Aucun rapport signé ne correspond à ce code</p>
            <p class="mb-0 small">
                Vérifiez la saisie du code <code>{{ $code }}</code>. Si le doute persiste,
                rapprochez-vous de votre auditeur.
            </p>
        </div>
    </div>
@elseif ($intact)
    <div class="integrity-banner integrity-banner--ok mb-4">
        <span style="font-size:1.6rem" aria-hidden="true">🔒</span>
        <div>
            <p class="fw-bold mb-1">Document authentique et intègre</p>
            <p class="mb-0 small">
                Le contenu de ce rapport est identique à celui qui a été signé.
            </p>
        </div>
    </div>
@else
    <div class="integrity-banner integrity-banner--ko mb-4">
        <span style="font-size:1.6rem" aria-hidden="true">⚠</span>
        <div>
            <p class="fw-bold mb-1">Le contenu a été modifié depuis la signature</p>
            <p class="mb-0 small">
                Ce code correspond bien à un rapport émis, mais son contenu actuel ne
                correspond plus à l'empreinte enregistrée lors de la signature.
                Ne considérez pas ce document comme valide.
            </p>
        </div>
    </div>
@endif

@if ($audit)
    {{-- Seules les métadonnées de signature sont exposées : ni les constats,
         ni les scores, ni les recommandations. --}}
    <dl class="row mb-0 small">
        <dt class="col-5 text-muted">Référence</dt>
        <dd class="col-7 fw-bold">{{ $audit->reference }}</dd>

        <dt class="col-5 text-muted">Client</dt>
        <dd class="col-7">{{ $audit->client_name }}</dd>

        <dt class="col-5 text-muted">Date de l'audit</dt>
        <dd class="col-7">{{ $audit->audit_date?->format('d/m/Y') }}</dd>

        <dt class="col-5 text-muted">Signé le</dt>
        <dd class="col-7">{{ $audit->signed_at?->format('d/m/Y à H:i') }}</dd>

        <dt class="col-5 text-muted">Signataire</dt>
        <dd class="col-7">{{ $audit->signed_by }}</dd>

        @if ($audit->is_countersigned)
            <dt class="col-5 text-muted">Contre-signé par</dt>
            <dd class="col-7">
                {{ $audit->countersigned_by }}
                le {{ $audit->countersigned_at?->format('d/m/Y') }}
            </dd>
        @endif

        <dt class="col-5 text-muted">Empreinte SHA-256</dt>
        <dd class="col-7">
            <code style="word-break:break-all; font-size:.72rem">{{ $audit->content_hash }}</code>
        </dd>
    </dl>
@endif

<p class="text-center small mt-4 mb-0">
    <a href="{{ route('verify.form') }}">Vérifier un autre code</a>
</p>
@endsection
