<x-mail::message>
# Rapport d'audit {{ $audit->reference }}

@if ($body)
{{ $body }}
@else
Bonjour,

Veuillez trouver ci-joint le rapport d'audit réalisé le {{ $audit->audit_date?->format('d/m/Y') }}.
@endif

<x-mail::panel>
**Référence :** {{ $audit->reference }}
**Client :** {{ $audit->client_name }}
**Date de l'audit :** {{ $audit->audit_date?->format('d/m/Y') }}
@if ($audit->global_score !== null)
**Score global :** {{ number_format($audit->global_score, 1, ',', '') }} / 5 — {{ $audit->score_label }}
@endif
</x-mail::panel>

@if ($specification)
@if ($specificationAttached)
Le cahier des charges **{{ $specification->reference }}** est joint en document séparé.
@elseif ($specification->include_in_pdf)
Le cahier des charges **{{ $specification->reference }}** figure en annexe du rapport.
@endif

Charge estimée : **{{ $specification->announced_days_min ?? $specification->daysMin() }} à {{ $specification->announced_days_max ?? $specification->daysMax() }} jours-homme**, répartis en {{ $specification->baseLots()->count() }} lots. Il s'agit d'une unité d'effort et non d'une durée de calendrier : la durée réelle dépend de la cadence hebdomadaire convenue.
@endif

@if ($audit->is_signed)
Ce rapport est signé électroniquement. Vous pouvez en vérifier l'authenticité et l'intégrité
à tout moment avec le code ci-dessous.

<x-mail::button :url="route('verify.show', $audit->verification_code)">
Vérifier ce rapport
</x-mail::button>

Code de vérification : **{{ $audit->verification_code }}**
@endif

Cordialement,
{{ $audit->signed_by ?? $audit->owner?->name ?? config('app.name') }}
</x-mail::message>
