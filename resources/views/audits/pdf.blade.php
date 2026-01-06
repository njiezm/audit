<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport d'Audit - {{ $audit->audit_id }}</title>
    <style>
        /* ============================================
           DÉCLARATION DES POLICES LOCALES POUR DOMPDF
           ============================================ */
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

        /* ============================================
           CONFIGURATION DE LA PAGE ET DU PIED DE PAGE
           ============================================ */
        @page {
            margin: 15mm; /* Marges plus étroites */
            size: A4;
            
            /* Pied de page pour TOUTES les pages */
            @bottom-center {
                content: "© NJIEZM.FR - Expertise Stratégique | Page " counter(page);
                font-family: 'Space Grotesk';
                font-size: 9px;
                color: #888;
            }
        }
        
        /* ============================================
           STYLES GLOBAUX
           ============================================ */
        body {
            font-family: 'Space Grotesk'; /* Police par défaut pour tout le document */
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            font-size: 13px; /* Légèrement plus petit pour plus de contenu */
            line-height: 1.4;
        }

        .brand-font { 
            font-family: 'Special Elite', cursive; 
        }
        
        .report-page {
            width: 100%;
        }

        /* ============================================
           STYLES DES SECTIONS
           ============================================ */
        .report-header {
            border-bottom: 3px solid #003366;
            padding-bottom: 10px; /* Moins d'espace */
            margin-bottom: 15px; /* Moins d'espace */
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .category-block {
            margin-bottom: 20px; /* Moins d'espace entre les catégories */
            page-break-inside: avoid;
        }

        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px; /* Moins d'espace sous le titre */
        }

        .category-title {
            background: #003366;
            color: white;
            padding: 4px 12px; /* Plus compact */
            display: inline-block;
            font-family: 'Special Elite', cursive;
            text-transform: uppercase;
            font-size: 14px;
            font-weight: normal;
        }

        .finding-item {
            margin-bottom: 12px; /* Moins d'espace */
            padding-left: 15px;
            border-left: 3px solid #FFD700;
        }

        .recommendation-box {
            background: #eef2f7;
            border: 1px dashed #003366;
            padding: 10px; /* Plus compact */
            margin-top: 6px;
            font-style: italic;
            font-size: 0.9em;
        }
        
        .score-badge {
            /* Score plus compact et carré */
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: #003366;
            color: #FFD700;
            font-weight: bold;
            font-size: 14px;
            border-radius: 0; /* Carré */
            flex-shrink: 0; /* Empêche le rétrécissement */
        }

        .conclusion-box {
            margin-top: 20px; /* Moins d'espace */
            padding: 15px; /* Plus compact */
            border: 2px solid #003366;
            background: #fdfdfd;
            page-break-inside: avoid;
        }

        .signature-box {
            margin-top: 20px;
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
            background: #f9f9f9;
            page-break-inside: avoid;
        }

        .signature-info {
            font-size: 0.8em;
            margin-bottom: 8px;
            color: #666;
        }

        .signature-image {
            max-height: 50px; /* Un peu plus petit */
        }
        
        /* Styles pour les titres et textes */
        h1 { font-size: 22px; margin: 0; }
        h5 { font-size: 14px; margin: 0; }
        .small { font-size: 11px; }
        .text-muted { color: #666; }
        .fw-bold { font-weight: bold; }
        .mb-2 { margin-bottom: 6px; }
        .mt-2 { margin-top: 6px; }

        @page {
    margin: 15mm;
}

/* Pied de page avec numérotation pour Dompdf */
.footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    text-align: center;
    font-family: 'Space Grotesk';
    font-size: 9px;
    color: #888;
}

/* Dompdf utilise cette classe pour remplacer automatiquement par le numéro de page */
@page {
    margin: 15mm;
}

.pagenum:before {
    content: counter(page);
}


    </style>
</head>
<body>
    <div class="report-page">
        <!-- EN-TÊTE -->
        <div class="report-header">
            <div>
                <div style="text-align: left;">
                <div class="brand-font" style="font-size: 18px;">RAPPORT D'AUDIT</div>
                
            </div>
                <p class="brand-font text-muted small" style="margin-top: 2px;">{{ $audit->audit_id }}</p>
            </div>
            <div style="text-align: right;">
                <div class="brand-font" style="font-size: 18px;">NJIEZM<small>.FR</small></div>
                <div class="small">{{ \Carbon\Carbon::parse($audit->audit_date)->format('d/m/Y') }}</div>
            </div>
        </div>
        
        <!-- CLIENT -->

        <div style="text-align: left;">
                <div class="brand-font" style="font-size: 18px;">Client : <span style="color: #003366;">{{ $audit->client_name }}</span></div>
                <br>
            </div>
        
        <!-- CATÉGORIES -->
        @foreach($audit->categories as $category)
            @php
                $color = '#003366';
                if($category->score <= 2) $color = '#ff4757';
                if($category->score >= 4) $color = '#2ed573';
            @endphp
            <div class="category-block">
                <!-- TITRE ET NOTE SUR LA MÊME LIGNE -->
                <div class="category-header">
                    <div class="category-title">{{ $category->title }}</div>
                    <div class="score-badge brand-font" style="background-color: {{ $color }}; color: white; text-align:center">{{ $category->score }}</div>
                </div>
                
                <div class="finding-item">
                    <div class="fw-bold small mb-2">Observations :</div>
                    <div class="mb-2">{{ $category->observations ?: "N/A" }}</div>
                    <div class="recommendation-box">
                        <strong>Conseil N'jie ZAMON :</strong><br>
                        {{ $category->recommendations ?: "À définir." }}
                    </div>
                </div>
            </div>
        @endforeach
        
        <!-- CONCLUSION -->
        @if($audit->conclusion)
            <div class="conclusion-box">
                <div class="brand-font" style="margin-bottom: 5px; font-size: 16px;">SYNTHÈSE GLOBALE</div>
                <p style="margin:0;">{{ $audit->conclusion }}</p>
            </div>
        @endif
        
        <!-- SIGNATURE -->
        @if($audit->is_signed)
            <div class="signature-box">
                <p class="brand-font" style="margin-bottom: 5px; font-size: 14px;">SIGNATURE ÉLECTRONIQUE</p>
                <p class="signature-info">Signé le {{ \Carbon\Carbon::parse($audit->signed_at)->format('d/m/Y à H:i') }} par {{ $audit->signed_by }}</p>
               <img src="{{ public_path('images/signature.png') }}" alt="Signature" class="signature-image">

            </div>
        @endif
    </div>

    <div class="footer">
    © NJIEZM.FR - Expertise Stratégique | Page <span class="pagenum"></span>
</div>

</body>
</html>