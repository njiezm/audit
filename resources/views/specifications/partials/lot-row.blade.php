@php
    $v = fn (string $key, $default = '') => data_get($lot, $key, $default);
@endphp

<div class="section-card" data-lot draggable="true">
    <div class="d-flex align-items-center gap-2 mb-2">
        <button type="button" class="section-card__handle" data-handle
                aria-label="Déplacer le lot (flèches haut et bas)" title="Glisser ou ↑ ↓">⠿</button>

        <input type="text" data-name="code" name="lots[{{ $index }}][code]"
               class="form-control form-control-sm" style="max-width:70px" placeholder="N°"
               value="{{ $v('code') }}" aria-label="Numéro du lot">

        <input type="text" data-name="name" name="lots[{{ $index }}][name]"
               class="form-control form-control-sm fw-bold" placeholder="Intitulé du lot" required
               value="{{ $v('name') }}">

        <button type="button" class="delete-btn" data-remove-lot
                aria-label="Supprimer ce lot" title="Supprimer">×</button>
    </div>

    <textarea data-name="content" name="lots[{{ $index }}][content]" rows="2"
              class="form-control form-control-sm mb-2"
              placeholder="Ce que couvre le lot…">{{ $v('content') }}</textarea>

    <div class="row g-2 align-items-end">
        <div class="col-6 col-md-4">
            <label class="form-label small mb-1">Phase</label>
            <input type="text" data-name="phase" name="lots[{{ $index }}][phase]" list="phase-suggestions"
                   class="form-control form-control-sm" placeholder="Ex. 1 — Socle"
                   value="{{ $v('phase') }}">
        </div>
        <div class="col-3 col-md-2">
            <label class="form-label small mb-1">Jours min.</label>
            <input type="number" data-name="days_min" name="lots[{{ $index }}][days_min]" min="0" max="999"
                   class="form-control form-control-sm" value="{{ (int) $v('days_min', 0) }}" required>
        </div>
        <div class="col-3 col-md-2">
            <label class="form-label small mb-1">Jours max.</label>
            <input type="number" data-name="days_max" name="lots[{{ $index }}][days_max]" min="0" max="999"
                   class="form-control form-control-sm" value="{{ (int) $v('days_max', 0) }}" required>
        </div>
        <div class="col-12 col-md-4">
            <div class="form-check form-check-inline">
                {{-- Le champ caché garantit l'envoi d'une valeur quand la case
                     est décochée : sans lui, la désactivation ne remonte pas. --}}
                <input type="hidden" name="lots[{{ $index }}][is_option]" value="0">
                <input class="form-check-input" type="checkbox" data-name="is_option"
                       name="lots[{{ $index }}][is_option]" value="1"
                       id="lot-{{ $index }}-option" @checked($v('is_option'))>
                <label class="form-check-label small" for="lot-{{ $index }}-option">Option</label>
            </div>
            <div class="form-check form-check-inline">
                <input type="hidden" name="lots[{{ $index }}][is_at_risk]" value="0">
                <input class="form-check-input" type="checkbox" data-name="is_at_risk"
                       name="lots[{{ $index }}][is_at_risk]" value="1"
                       id="lot-{{ $index }}-risk" @checked($v('is_at_risk'))>
                <label class="form-check-label small" for="lot-{{ $index }}-risk">À risque</label>
            </div>
        </div>
    </div>

    <input type="text" data-name="risk_note" name="lots[{{ $index }}][risk_note]"
           class="form-control form-control-sm mt-2" placeholder="Nature du risque (si applicable)"
           value="{{ $v('risk_note') }}">
</div>
