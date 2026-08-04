@props([
    // 'default' = sceau bleu sur fond clair · 'reversed' = sceau jaune sur fond bleu
    'variant' => 'default',
    'size' => 34,
    'wordmark' => true,
    'tagline' => true,
])

@php
    $reversed = $variant === 'reversed';
    $seal  = $reversed ? '#FFD700' : '#003366';
    $check = $reversed ? '#003366' : '#FFD700';
    // Teinte des barres déjà fusionnée avec le sceau : une seule couche de
    // peinture, donc un rendu identique en SVG, en PNG et dans le PDF.
    $bars  = $reversed ? '#c7b316' : '#386088';
    $ring  = $reversed ? '#003366' : '#ffffff';
    $ringOpacity = $reversed ? '.14' : '.16';
@endphp

<span {{ $attributes->merge(['class' => 'd-inline-flex align-items-center gap-2 text-decoration-none']) }}>
    {{--
        Le sceau : trois barres de notation (l'évaluation) validées par une
        coche (la vérification). Lisible jusqu'à 16 px, où les barres
        deviennent une texture et la coche reste le signe dominant.
    --}}
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 64 64" role="img"
         aria-label="Audit Master" focusable="false" style="flex-shrink:0">
        <rect x="2" y="2" width="60" height="60" rx="15" fill="{{ $seal }}"/>
        <rect x="4.5" y="4.5" width="55" height="55" rx="12.5" fill="none"
              stroke="{{ $ring }}" stroke-opacity="{{ $ringOpacity }}" stroke-width="1"/>

        <g fill="{{ $bars }}">
            <rect x="14" y="45" width="9" height="9" rx="2"/>
            <rect x="27" y="39" width="9" height="15" rx="2"/>
            <rect x="40" y="33" width="9" height="21" rx="2"/>
        </g>

        <path d="M16 30 L26 40 L48 15" fill="none" stroke="{{ $check }}"
              stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>

    @if ($wordmark)
        <span class="d-inline-flex flex-column" style="line-height:1">
            <span class="brand-font" style="font-size:{{ round($size * 0.5) }}px; letter-spacing:.01em">NJIEZM</span>
            @if ($tagline)
                <span style="font-size:{{ max(8, round($size * 0.25)) }}px; letter-spacing:.18em; font-weight:600; opacity:.85">
                    AUDIT MASTER
                </span>
            @endif
        </span>
    @endif
</span>
