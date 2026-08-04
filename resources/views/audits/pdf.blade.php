{{--
    Rapport PDF — rendu par DomPDF.

    Toute la mise en page repose sur des <table> : DomPDF 3 ne gère ni
    flexbox ni grid. L'ancienne version utilisait `display:flex` pour
    l'en-tête et pour la ligne « titre + note », qui se retrouvaient donc
    empilées dans le PDF réel au lieu d'être alignées.
--}}
@php
    use App\Support\ScoreScale;

    $plan = $audit->actionPlan();
    $risks = $audit->topRisks();
    $signature = $audit->signatory?->signatureFile();
    // Variante à fond bleu : posée sur le bandeau, elle ne laisse aucun halo.
    $logo = public_path('images/logo-pdf-band.png');
    $clientLogo = $audit->client?->logo_path
        ? storage_path('app/public/'.$audit->client->logo_path)
        : null;

    $watermarkSize = max(44, min(130, (int) (1250 / max(6, mb_strlen((string) $audit->watermark)))));
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport d'audit {{ $audit->reference }}</title>
    <style>
        @font-face {
            font-family: 'Space Grotesk';
            font-style: normal;
            font-weight: normal;
            src: url('{{ public_path('fonts/SpaceGrotesk-VariableFont_wght.ttf') }}') format('truetype');
        }

        @font-face {
            font-family: 'Special Elite';
            font-style: normal;
            font-weight: normal;
            src: url('{{ public_path('fonts/SpecialElite-Regular.ttf') }}') format('truetype');
        }

        /* Un seul bloc @page : la version précédente en déclarait trois,
           dont un utilisant @bottom-center que DomPDF ignore. */
        @page { margin: 16mm 15mm 20mm 15mm; size: A4 portrait; }

        body {
            font-family: 'Space Grotesk', sans-serif;
            color: #1a1a1a;
            font-size: 11.5px;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }

        .brand-font { font-family: 'Special Elite', cursive; }
        h1, h2, h3 { margin: 0; font-weight: normal; }
        p { margin: 0 0 6px; }
        table { border-collapse: collapse; width: 100%; }

        /* --- Pied de page, répété sur chaque page --- */
        .page-footer {
            position: fixed;
            bottom: -14mm;
            left: 0;
            right: 0;
            height: 12mm;
            font-size: 8.5px;
            color: #6b7280;      /* contraste AA, contre l'ancien opacity:.5 */
            border-top: 1px solid #e2e6ea;
            padding-top: 4px;
        }

        .page-footer td { vertical-align: top; }
        .page-footer .right { text-align: right; }
        .pagenum:before { content: counter(page); }

        /* --- Couverture --- */
        .cover { page-break-after: always; }

        .cover-band {
            background: #003366;
            color: #fff;
            padding: 18px 20px;
        }

        .cover-title { font-size: 26px; letter-spacing: .04em; }
        .cover-sub { font-size: 12px; color: #ffd700; letter-spacing: .16em; }

        .cover-score {
            width: 96px;
            height: 96px;
            text-align: center;
            color: #fff;
            font-size: 34px;
            font-weight: bold;
        }

        .meta-table td { padding: 6px 0; border-bottom: 1px solid #e8ecf1; font-size: 11.5px; }
        .meta-table .label { color: #5a6672; width: 38%; }

        .verify-box {
            border: 2px dashed #003366;
            background: #f5f8fc;
            padding: 12px 14px;
            font-size: 10.5px;
        }

        .verify-code {
            font-family: 'Special Elite', monospace;
            font-size: 17px;
            letter-spacing: .12em;
            color: #003366;
        }

        /* --- En-tête courant --- */
        .report-header {
            border-bottom: 3px solid #003366;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }

        .report-header .right { text-align: right; }

        /* --- Sections --- */
        .section-title {
            font-family: 'Special Elite', cursive;
            font-size: 15px;
            color: #003366;
            border-bottom: 2px solid #ffd700;
            padding-bottom: 4px;
            margin: 0 0 12px;
        }

        /* Pas de `page-break-inside: avoid` ici : sur des constats longs, il
           réservait une page entière à une seule catégorie. Seuls les blocs
           courts qu'il serait absurde de couper sont protégés. */
        .category-block { margin-bottom: 18px; }

        .category-head { page-break-inside: avoid; page-break-after: avoid; }

        .category-title {
            background: #003366;
            color: #fff;
            padding: 4px 11px;
            font-family: 'Special Elite', cursive;
            text-transform: uppercase;
            font-size: 12.5px;
        }

        .score-cell { text-align: right; width: 88px; }

        .score-chip {
            display: inline-block;
            padding: 5px 11px;
            color: #fff;
            font-weight: bold;
            font-size: 13px;
        }

        .score-caption { font-size: 8.5px; font-weight: bold; margin-top: 2px; }

        .finding {
            padding-left: 12px;
            border-left: 3px solid #ffd700;
            margin-top: 8px;
        }

        .label-inline { font-weight: bold; font-size: 9.5px; color: #5a6672; text-transform: uppercase; }

        .recommendation-box {
            background: #eef2f7;
            border: 1px dashed #003366;
            padding: 9px 11px;
            margin-top: 6px;
            font-size: 11px;
            page-break-inside: avoid;
        }

        .conclusion-box {
            border: 2px solid #003366;
            padding: 14px;
            background: #fdfdfd;
            page-break-inside: avoid;
            margin-top: 16px;
        }

        /* --- Plan d'action --- */
        .plan-table th {
            background: #003366;
            color: #fff;
            font-size: 9.5px;
            text-transform: uppercase;
            padding: 6px 8px;
            text-align: left;
        }

        .plan-table td {
            border-bottom: 1px solid #e2e6ea;
            padding: 7px 8px;
            font-size: 10.5px;
            vertical-align: top;
        }

        .priority-tag {
            display: inline-block;
            padding: 2px 7px;
            color: #fff;
            font-size: 9px;
            font-weight: bold;
            white-space: nowrap;
        }

        /* --- Signature --- */
        .signature-box {
            border: 1px solid #d8dee6;
            background: #fafbfc;
            padding: 14px;
            page-break-inside: avoid;
            margin-top: 18px;
        }

        .muted { color: #5a6672; }
        .small { font-size: 9.5px; }
        .center { text-align: center; }

        /* --- Champs libres : balisage léger rendu par RichText --- */
        .rich p { margin: 0 0 7px; }
        .rich p:last-child { margin-bottom: 0; }
        .rich ul { margin: 0 0 7px; padding-left: 16px; }
        .rich li { margin-bottom: 3px; }
        .rich strong { font-weight: bold; }

        .rich code {
            font-family: 'Courier', monospace;
            font-size: .92em;
            background: #eef2f7;
            padding: 1px 3px;
        }

        /* --- Filigrane optionnel ---
           `position: fixed` le fait répéter sur chaque page, et il est
           déclaré avant le contenu pour rester en arrière-plan : DomPDF
           n'applique pas z-index de façon fiable. */
        .watermark {
            position: fixed;
            top: 40%;
            left: 0;
            right: 0;
            text-align: center;
            font-family: 'Special Elite', cursive;
            /* Taille calculée d'après la longueur du texte : à corps fixe, un
               libellé long passait à la ligne et sortait du cadre. */
            font-size: {{ $watermarkSize }}px;
            letter-spacing: .05em;
            white-space: nowrap;
            color: #003366;
            opacity: .10;
            transform: rotate(-30deg);
        }
    </style>
</head>
<body>

@if (filled($audit->watermark))
    <div class="watermark">{{ $audit->watermark }}</div>
@endif

{{-- Pied de page : `position: fixed` est la seule mécanique de répétition
     que DomPDF sache honorer. --}}
<table class="page-footer">
    <tr>
        <td>© {{ date('Y') }} NJIEZM.FR — Expertise Stratégique</td>
        <td class="center">{{ $audit->reference }}</td>
        <td class="right">Page <span class="pagenum"></span></td>
    </tr>
</table>

{{-- ==================================================================
     PAGE DE GARDE
     ================================================================== --}}
<div class="cover">
    <table class="cover-band">
        <tr>
            <td style="width:64px; vertical-align:middle;">
                @if (is_file($logo))
                    <img src="{{ $logo }}" alt="" style="width:54px; height:54px;">
                @endif
            </td>
            <td style="vertical-align:middle; padding-left:12px;">
                <div class="brand-font cover-title">RAPPORT D'AUDIT</div>
                <div class="brand-font cover-sub">NJIEZM.FR — AUDIT MASTER</div>
            </td>
            <td style="vertical-align:middle; text-align:right;">
                <div class="brand-font" style="font-size:15px;">{{ $audit->reference }}</div>
                <div class="small" style="color:#c9d8e8;">
                    Émis le {{ now()->format('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>

    <div style="height:26px;"></div>

    <table>
        <tr>
            <td style="vertical-align:top;">
                <div class="brand-font" style="font-size:22px; color:#003366;">
                    {{ $audit->client_name }}
                </div>
                @if ($audit->title)
                    <div class="muted" style="font-size:13px; margin-top:3px;">{{ $audit->title }}</div>
                @endif
            </td>
            @if ($clientLogo && is_file($clientLogo))
                <td style="width:120px; text-align:right; vertical-align:top;">
                    <img src="{{ $clientLogo }}" alt="" style="max-width:110px; max-height:56px;">
                </td>
            @endif
        </tr>
    </table>

    <div style="height:22px;"></div>

    <table>
        <tr>
            {{-- Score global : la donnée la plus attendue d'un rapport
                 d'audit, et qui n'était calculée nulle part. --}}
            <td style="width:130px; vertical-align:top;">
                @if ($audit->global_score !== null)
                    <table class="cover-score" style="background: {{ $audit->score_color }};">
                        <tr>
                            <td style="color:#fff; font-size:30px; font-weight:bold; text-align:center; height:96px;">
                                {{ number_format($audit->global_score, 1, ',', '') }}
                            </td>
                        </tr>
                    </table>
                    <div class="center" style="font-size:10px; font-weight:bold; color:{{ $audit->score_color }}; margin-top:5px; width:96px;">
                        {{ $audit->score_label }}
                    </div>
                    <div class="center small muted" style="width:96px;">sur 5</div>
                @endif
            </td>

            <td style="vertical-align:top; padding-left:18px;">
                <table class="meta-table">
                    <tr>
                        <td class="label">Référence</td>
                        <td><strong>{{ $audit->reference }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Date de l'audit</td>
                        <td>{{ $audit->audit_date?->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Périmètre</td>
                        <td>{{ $audit->categories->count() }} catégorie(s) évaluée(s)</td>
                    </tr>
                    <tr>
                        <td class="label">Notation</td>
                        <td>{{ $audit->scoring_mode === 'weighted' ? 'Moyenne pondérée' : 'Moyenne simple' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Auditeur</td>
                        <td>
                            {{ $audit->owner?->name ?? $audit->signed_by ?? '—' }}
                            @if ($audit->owner?->job_title)
                                <span class="muted small">— {{ $audit->owner->job_title }}</span>
                            @endif
                        </td>
                    </tr>
                    @if ($audit->follow_up_on)
                        <tr>
                            <td class="label">Suivi prévu</td>
                            <td>{{ $audit->follow_up_on->format('d/m/Y') }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div style="height:20px;"></div>

    {{-- Barème : un « 3 » ne voulait rien dire tant qu'il n'était pas documenté. --}}
    <div class="section-title">Barème de notation</div>
    <table>
        <tr>
            @foreach (ScoreScale::all() as $level => $meta)
                <td style="width:20%; padding-right:5px; vertical-align:top;">
                    <div style="background: {{ $meta['color'] }}; color:#fff; text-align:center; padding:4px 0; font-weight:bold; font-size:12px;">
                        {{ $level }}
                    </div>
                    <div style="font-size:9px; font-weight:bold; text-align:center; margin-top:3px;">
                        {{ $meta['label'] }}
                    </div>
                </td>
            @endforeach
        </tr>
    </table>

    @if ($risks->isNotEmpty())
        <div style="height:18px;"></div>
        <div class="section-title">Points d'attention prioritaires</div>
        <table>
            @foreach ($risks as $risk)
                <tr>
                    <td style="width:34px; padding:5px 0;">
                        <div class="score-chip" style="background: {{ $risk->score_color }};">{{ $risk->score }}</div>
                    </td>
                    <td style="padding:5px 0 5px 10px;">
                        <strong>{{ $risk->title }}</strong>
                        <span class="muted small">
                            — {{ $risk->score_label }}@if ($risk->weight > 1), poids ×{{ $risk->weight }}@endif
                        </span>
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($audit->is_signed)
        <div style="height:18px;"></div>
        <table class="verify-box">
            <tr>
                <td style="vertical-align:middle;">
                    <strong>Vérification d'authenticité</strong><br>
                    Ce rapport est signé électroniquement. Contrôlez son intégrité sur<br>
                    <strong>{{ route('verify.form') }}</strong> à l'aide du code ci-contre.
                </td>
                <td style="text-align:right; vertical-align:middle; width:190px;">
                    <div class="verify-code">{{ $audit->verification_code }}</div>
                    <div class="small muted" style="margin-top:3px;">Code de vérification</div>
                </td>
            </tr>
        </table>
    @endif
</div>

{{-- ==================================================================
     CONSTATS
     ================================================================== --}}
<table class="report-header">
    <tr>
        <td style="vertical-align:bottom;">
            <div class="brand-font" style="font-size:16px; color:#003366;">CONSTATS DÉTAILLÉS</div>
            <div class="small muted">{{ $audit->client_name }} — {{ $audit->reference }}</div>
        </td>
        <td class="right" style="vertical-align:bottom;">
            <div class="brand-font" style="font-size:14px;">NJIEZM<span style="font-size:10px;">.FR</span></div>
            <div class="small">{{ $audit->audit_date?->format('d/m/Y') }}</div>
        </td>
    </tr>
</table>

@foreach ($audit->categories as $category)
    <div class="category-block">
        {{-- Titre et note alignés par un tableau à deux cellules : c'est le
             seul moyen fiable de les tenir sur la même ligne dans DomPDF. --}}
        <table class="category-head">
            <tr>
                <td style="vertical-align:middle;">
                    <span class="category-title">{{ $category->title }}</span>
                    @if ($category->weight > 1)
                        <span class="muted small"> poids ×{{ $category->weight }}</span>
                    @endif
                </td>
                <td class="score-cell" style="vertical-align:middle;">
                    <div class="score-chip" style="background: {{ $category->score_color }};">
                        {{ $category->score }} / 5
                    </div>
                    <div class="score-caption" style="color: {{ $category->score_color }};">
                        {{ $category->score_label }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="finding">
            <div class="label-inline">Observations</div>
            <div class="rich">{!! \App\Support\RichText::render($category->observations ?: 'Non renseigné.') !!}</div>

            @if ($category->recommendations)
                <div class="recommendation-box">
                    <strong>Recommandation</strong>
                    @if ($category->priority || $category->due_on || $category->owner)
                        <span class="muted small">
                            —
                            @if ($category->priority) criticité {{ $category->priority->label() }} @endif
                            @if ($category->due_on) · échéance {{ $category->due_on->format('d/m/Y') }} @endif
                            @if ($category->owner) · {{ $category->owner }} @endif
                        </span>
                    @endif
                    <div class="rich">{!! \App\Support\RichText::render($category->recommendations) !!}</div>
                </div>
            @endif

            @if ($category->attachments->isNotEmpty())
                <div class="small muted" style="margin-top:5px;">
                    Pièces jointes :
                    {{ $category->attachments->pluck('original_name')->implode(', ') }}
                </div>
            @endif
        </div>
    </div>
@endforeach

@if ($audit->conclusion)
    <div class="conclusion-box">
        <div class="brand-font" style="font-size:14px; color:#003366; margin-bottom:6px;">SYNTHÈSE GLOBALE</div>
        <div class="rich">{!! \App\Support\RichText::render($audit->conclusion) !!}</div>
    </div>
@endif

{{-- ==================================================================
     PLAN D'ACTION
     ================================================================== --}}
@if ($plan->isNotEmpty())
    <div style="page-break-before: always;"></div>

    <table class="report-header">
        <tr>
            <td style="vertical-align:bottom;">
                <div class="brand-font" style="font-size:16px; color:#003366;">PLAN D'ACTION</div>
                <div class="small muted">Trié par criticité puis par note croissante</div>
            </td>
            <td class="right" style="vertical-align:bottom;">
                <div class="brand-font" style="font-size:14px;">NJIEZM<span style="font-size:10px;">.FR</span></div>
            </td>
        </tr>
    </table>

    <table class="plan-table">
        <thead>
            <tr>
                <th style="width:64px;">Criticité</th>
                <th style="width:22%;">Catégorie</th>
                <th>Action recommandée</th>
                <th style="width:16%;">Responsable</th>
                <th style="width:62px;">Échéance</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($plan as $item)
                <tr>
                    <td>
                        @if ($item->priority)
                            <span class="priority-tag" style="background: {{ $item->priority->color() }};">
                                {{ $item->priority->label() }}
                            </span>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td><strong>{{ $item->title }}</strong></td>
                    <td class="rich">{!! \App\Support\RichText::render($item->recommendations) !!}</td>
                    <td>{{ $item->owner ?: '—' }}</td>
                    <td>{{ $item->due_on?->format('d/m/y') ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- ==================================================================
     SIGNATURE
     ================================================================== --}}
@if ($audit->is_signed)
    <div class="signature-box">
        <table>
            <tr>
                <td style="vertical-align:top;">
                    <div class="brand-font" style="font-size:13px; color:#003366;">SIGNATURE ÉLECTRONIQUE</div>
                    <div class="small muted" style="margin-top:5px;">
                        Signé le {{ $audit->signed_at?->format('d/m/Y à H:i') }}<br>
                        par <strong>{{ $audit->signed_by }}</strong>
                        @if ($audit->signatory?->job_title)
                            , {{ $audit->signatory->job_title }}
                        @endif
                    </div>

                    @if ($audit->is_countersigned)
                        <div class="small muted" style="margin-top:6px;">
                            Contre-signé par <strong>{{ $audit->countersigned_by }}</strong>
                            le {{ $audit->countersigned_at?->format('d/m/Y') }}
                        </div>
                    @endif

                    <div class="small muted" style="margin-top:8px;">
                        Empreinte SHA-256 du contenu signé :<br>
                        <span style="font-size:7.5px; word-wrap:break-word;">{{ $audit->content_hash }}</span>
                    </div>
                </td>
                <td style="width:200px; text-align:right; vertical-align:top;">
                    @if ($signature && is_file($signature))
                        <img src="{{ $signature }}" alt="Signature" style="max-height:56px;">
                    @endif
                    <div class="small muted" style="margin-top:6px;">
                        Code : <strong>{{ $audit->verification_code }}</strong>
                    </div>
                </td>
            </tr>
        </table>
    </div>
@else
    <div class="signature-box center muted small">
        Document non signé — version de travail, sans valeur d'engagement.
    </div>
@endif

</body>
</html>
