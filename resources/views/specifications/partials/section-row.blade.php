@php
    $v = fn (string $key, $default = '') => data_get($section, $key, $default);
@endphp

<div class="section-card" data-section draggable="true">
    <div class="d-flex align-items-center gap-2 mb-2">
        <button type="button" class="section-card__handle" data-handle
                aria-label="Déplacer la section (flèches haut et bas)" title="Glisser ou ↑ ↓">⠿</button>

        <input type="text" data-name="title" name="sections[{{ $index }}][title]"
               class="form-control form-control-sm fw-bold" placeholder="Titre de la section" required
               value="{{ $v('title') }}">

        <button type="button" class="delete-btn" data-remove-section
                aria-label="Supprimer cette section" title="Supprimer">×</button>
    </div>

    <textarea data-name="body" name="sections[{{ $index }}][body]" rows="4"
              class="form-control form-control-sm"
              placeholder="Contenu — *gras*, `code` et lignes commençant par · pour les puces">{{ $v('body') }}</textarea>

    <div class="form-check mt-2">
        <input type="hidden" name="sections[{{ $index }}][page_break_before]" value="0">
        <input class="form-check-input" type="checkbox" data-name="page_break_before"
               name="sections[{{ $index }}][page_break_before]" value="1"
               id="section-{{ $index }}-break" @checked($v('page_break_before'))>
        <label class="form-check-label small" for="section-{{ $index }}-break">
            Commencer sur une nouvelle page du PDF
        </label>
    </div>
</div>
