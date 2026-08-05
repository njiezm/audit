{{-- Cahier des charges livré seul, sans le rapport d'audit. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Cahier des charges {{ $specification->reference }}</title>
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
        p { margin: 0 0 6px; }
        table { border-collapse: collapse; width: 100%; }
        .muted { color: #5a6672; }
        .small { font-size: 9.5px; }

        .page-footer {
            position: fixed;
            bottom: -14mm;
            left: 0;
            right: 0;
            height: 12mm;
            font-size: 8.5px;
            color: #6b7280;
            border-top: 1px solid #e2e6ea;
            padding-top: 4px;
        }

        .page-footer td { vertical-align: top; }
        .page-footer .right { text-align: right; }
        .pagenum:before { content: counter(page); }

        .rich p { margin: 0 0 6px; }
        .rich p:last-child { margin-bottom: 0; }
        .rich ul { margin: 0 0 6px; padding-left: 15px; }
        .rich li { margin-bottom: 3px; }
        .rich strong { font-weight: bold; }
        .rich code { font-family: 'Courier', monospace; font-size: .92em; background: #eef2f7; padding: 1px 3px; }

        .rich pre {
            background: #f4f6f9;
            border: 1px solid #dde3ea;
            padding: 8px 10px;
            margin: 0 0 7px;
            font-family: 'Courier', monospace;
            font-size: 8.5px;
            line-height: 1.35;
            page-break-inside: avoid;
        }

        .rich pre code { background: none; padding: 0; font-size: inherit; }

        .rich-table {
            width: 100%;
            margin: 0 0 8px;
            border-collapse: collapse;
            font-size: 9.5px;
        }

        .rich-table th {
            background: #003366;
            color: #fff;
            text-align: left;
            padding: 4px 6px;
            font-size: 8.5px;
            text-transform: uppercase;
        }

        .rich-table td {
            border-bottom: 1px solid #e2e6ea;
            padding: 4px 6px;
            vertical-align: top;
        }
    </style>

    @include('specifications.partials.pdf-styles')
</head>
<body>

<table class="page-footer">
    <tr>
        <td>© {{ date('Y') }} NJIEZM.FR — Expertise Stratégique</td>
        <td style="text-align:center;">{{ $specification->reference }}</td>
        <td class="right">Page <span class="pagenum"></span></td>
    </tr>
</table>

@include('specifications.partials.pdf-body')

</body>
</html>
