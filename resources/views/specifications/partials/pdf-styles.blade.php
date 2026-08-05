{{-- Styles du cahier des charges, partagés par le document autonome et par
     l'annexe accolée au rapport d'audit. Comme le reste du PDF, la mise en
     page repose sur des tables : DomPDF ignore flexbox. --}}
<style>
    .cdc-band {
        background: #003366;
        color: #fff;
        padding: 14px 18px;
    }

    .cdc-band .title { font-family: 'Special Elite', cursive; font-size: 21px; }
    .cdc-band .sub { font-size: 10px; color: #ffd700; letter-spacing: .14em; }

    .cdc-section-title {
        font-family: 'Special Elite', cursive;
        font-size: 14px;
        color: #003366;
        border-bottom: 2px solid #ffd700;
        padding-bottom: 3px;
        margin: 16px 0 8px;
    }

    .cdc-scope td {
        vertical-align: top;
        width: 50%;
        padding: 10px;
        font-size: 10.5px;
    }

    .cdc-scope .in { background: #eef5ef; border-left: 3px solid #2f6f4f; }
    .cdc-scope .out { background: #f7eeee; border-left: 3px solid #b3001b; }

    .cdc-lots th {
        background: #003366;
        color: #fff;
        font-size: 9px;
        text-transform: uppercase;
        padding: 5px 7px;
        text-align: left;
    }

    .cdc-lots td {
        border-bottom: 1px solid #e2e6ea;
        padding: 6px 7px;
        font-size: 10px;
        vertical-align: top;
    }

    .cdc-phase-row td {
        background: #eef2f7;
        font-weight: bold;
        font-size: 10px;
        border-bottom: 1px solid #c9d4e0;
    }

    .cdc-total {
        background: #1a1a1a;
        color: #fff;
        padding: 12px 16px;
        page-break-inside: avoid;
    }

    .cdc-total .figure { font-family: 'Special Elite', cursive; font-size: 22px; }
    .cdc-total .caption { font-size: 9px; color: #bdc7d1; text-transform: uppercase; letter-spacing: .1em; }

    .cdc-risk {
        border-left: 3px solid #e8590c;
        background: #fdf3ec;
        padding: 9px 11px;
        margin-top: 8px;
        font-size: 10px;
        page-break-inside: avoid;
    }

    .cdc-note {
        border: 1px dashed #003366;
        background: #f5f8fc;
        padding: 9px 11px;
        font-size: 10px;
        margin-top: 8px;
    }

    .cdc-tag {
        display: inline-block;
        padding: 1px 6px;
        background: #e8590c;
        color: #fff;
        font-size: 8px;
        font-weight: bold;
    }
</style>
