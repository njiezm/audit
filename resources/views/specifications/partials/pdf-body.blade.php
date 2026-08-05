@php
    use App\Support\RichText;

    $margin = $specification->announcedMargin();
    $budget = $specification->budgetRange();
    $phases = $specification->lotsByPhase();
    $options = $specification->optionLots();
    $risks = $specification->risks();

    $envMin = $specification->announced_days_min ?? $specification->daysMin();
    $envMax = $specification->announced_days_max ?? $specification->daysMax();
@endphp

{{-- Bandeau de tête --}}
<table class="cdc-band">
    <tr>
        <td style="vertical-align:middle;">
            <div class="title">CAHIER DES CHARGES</div>
            <div class="sub">NJIEZM.FR — AUDIT MASTER</div>
        </td>
        <td style="vertical-align:middle; text-align:right;">
            <div class="brand-font" style="font-size:14px;">{{ $specification->reference }}</div>
            <div style="font-size:9px; color:#c9d8e8;">
                Version {{ $specification->version }} · {{ $specification->status->label() }}
            </div>
        </td>
    </tr>
</table>

<div style="height:14px;"></div>

<div class="brand-font" style="font-size:17px; color:#003366;">{{ $specification->title }}</div>
<div class="muted small" style="margin-top:2px;">
    {{ $audit->client_name }} · adossé au rapport d'audit {{ $audit->reference }}
    @if ($specification->starts_on)
        · démarrage envisagé le {{ $specification->starts_on->format('d/m/Y') }}
    @endif
    @if ($specification->valid_until)
        · proposition valable jusqu'au {{ $specification->valid_until->format('d/m/Y') }}
    @endif
</div>

{{-- Enveloppe de charge --}}
<div style="height:12px;"></div>
<table class="cdc-total">
    <tr>
        <td style="vertical-align:middle;">
            <div class="caption">Charge totale</div>
            <div class="figure">{{ $envMin }} – {{ $envMax }} jours</div>
        </td>
        <td style="vertical-align:middle; text-align:right; font-size:9.5px; color:#bdc7d1;">
            Somme des lots : {{ $specification->daysMin() }} – {{ $specification->daysMax() }} j
            @if ($margin && ($margin['min'] !== 0 || $margin['max'] !== 0))
                <br>Marge de cadrage : {{ $margin['min'] > 0 ? '+' : '' }}{{ $margin['min'] }}
                à {{ $margin['max'] > 0 ? '+' : '' }}{{ $margin['max'] }} j
            @endif
            @if ($budget)
                <br>Budget estimatif : {{ $specification->formatBudget($budget['min']) }}
                – {{ $specification->formatBudget($budget['max']) }}
            @endif
            @if ($options->isNotEmpty())
                <br>Hors options ({{ $options->count() }})
            @endif
        </td>
    </tr>
</table>

{{-- Avertissement permanent : la confusion entre effort et calendrier est la
     première source de malentendu sur un devis au forfait. --}}
<div class="cdc-note" style="margin-top:0; border-top:none;">
    <strong>Jours-homme, et non jours de calendrier.</strong>
    Un jour-homme correspond à une personne travaillant une journée pleine sur ce seul projet.
    {{ $envMin }} jours-homme ne signifient donc ni {{ $envMin }} jours de calendrier, ni
    {{ round($envMin / 21, 1) }} mois : la durée réelle dépend de la cadence hebdomadaire
    convenue, des autres engagements en cours et des délais de validation.
    La charge et le délai sont deux engagements distincts ; seul le premier est chiffré ici.
</div>

{{-- Cadrage --}}
@if (filled($specification->context))
    <div class="cdc-section-title">Contexte</div>
    <div class="rich">{!! RichText::render($specification->context) !!}</div>
@endif

@if (filled($specification->objectives))
    <div class="cdc-section-title">Objectifs</div>
    <div class="rich">{!! RichText::render($specification->objectives) !!}</div>
@endif

{{-- Titre et tableau solidaires : sans cela, l'intitulé « Périmètre »
     restait orphelin en pied de page. --}}
@if (filled($specification->scope_in) || filled($specification->scope_out))
    <div style="page-break-inside: avoid;">
    <div class="cdc-section-title">Périmètre</div>
    <table class="cdc-scope">
        <tr>
            <td class="in">
                <strong>Inclus</strong>
                <div class="rich">{!! RichText::render($specification->scope_in ?: '—') !!}</div>
            </td>
            <td class="out">
                <strong>Exclu</strong>
                <div class="rich">{!! RichText::render($specification->scope_out ?: '—') !!}</div>
            </td>
        </tr>
    </table>
    </div>
@endif

{{-- Lots --}}
@if ($phases->isNotEmpty())
    <div style="page-break-before: always;"></div>
    <div class="cdc-section-title">Lots de travaux</div>

    <table class="cdc-lots">
        <thead>
            <tr>
                <th style="width:38px;">N°</th>
                <th style="width:24%;">Lot</th>
                <th>Contenu</th>
                <th style="width:64px; text-align:right;">Charge</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($phases as $phaseName => $phase)
                <tr class="cdc-phase-row">
                    <td colspan="3">{{ $phaseName }}</td>
                    <td style="text-align:right;">{{ $phase['days_min'] }} – {{ $phase['days_max'] }} j</td>
                </tr>
                @foreach ($phase['lots'] as $lot)
                    <tr>
                        <td>{{ $lot->codeLabel() }}</td>
                        <td>
                            <strong>{{ $lot->name }}</strong>
                            @if ($lot->is_at_risk)
                                <br><span class="cdc-tag">RISQUE</span>
                            @endif
                        </td>
                        <td class="rich">{!! RichText::render($lot->content) !!}</td>
                        <td style="text-align:right; white-space:nowrap;">{{ $lot->daysLabel() }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
@endif

@if ($risks->isNotEmpty())
    <div class="cdc-risk">
        <strong>Lots à risque</strong>
        <ul style="margin:5px 0 0; padding-left:15px;">
            @foreach ($risks as $risk)
                <li>
                    <strong>{{ $risk->code ? $risk->code.' — ' : '' }}{{ $risk->name }}</strong>{{ $risk->risk_note ? ' — '.$risk->risk_note : '' }}
                </li>
            @endforeach
        </ul>
    </div>
@endif

@if ($options->isNotEmpty())
    <div class="cdc-section-title">Options hors périmètre de base</div>
    <table class="cdc-lots">
        <tbody>
            @foreach ($options as $lot)
                <tr>
                    <td>
                        <strong>{{ $lot->name }}</strong>
                        @if ($lot->content)
                            <div class="muted" style="font-size:9.5px;">{{ $lot->content }}</div>
                        @endif
                    </td>
                    <td style="width:70px; text-align:right; white-space:nowrap;">{{ $lot->daysLabel() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="cdc-note">
        Les options ne sont pas comprises dans la charge totale annoncée ci-dessus.
    </div>
@endif

{{-- Sections libres --}}
@foreach ($specification->sections as $section)
    @if ($section->page_break_before)
        <div style="page-break-before: always;"></div>
    @endif

    <div class="cdc-section-title">{{ $section->title }}</div>
    <div class="rich">{!! RichText::render($section->body) !!}</div>
@endforeach
