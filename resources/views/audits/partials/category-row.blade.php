@php
    // La ligne est rendue aussi bien depuis un tableau (nouveau formulaire,
    // old(), modèle d'audit) que depuis un AuditCategory existant.
    $get = function (string $key, $default = '') use ($category) {
        $value = data_get($category, $key, $default);

        return $value instanceof \BackedEnum ? $value->value : $value;
    };

    $rowId = $get('id');
    $rowScore = (int) ($get('score', 3) ?: 3);
    $rowWeight = (int) ($get('weight', 1) ?: 1);
    $dueOn = $get('due_on');
    $dueOn = $dueOn instanceof \DateTimeInterface ? $dueOn->format('Y-m-d') : $dueOn;
@endphp

<div class="section-card" data-category draggable="true">
    <div class="d-flex align-items-center gap-2 mb-2">
        <button type="button" class="section-card__handle" data-handle
                aria-label="Déplacer la catégorie (flèches haut et bas)" title="Glisser ou ↑ ↓">⠿</button>

        <span class="badge text-bg-light border" data-position>{{ is_numeric($index) ? $index + 1 : 1 }}</span>

        <input type="text" data-name="title" name="categories[{{ $index }}][title]"
               class="form-control form-control-sm fw-bold" list="category-suggestions"
               placeholder="Intitulé de la catégorie" required value="{{ $get('title') }}">

        <button type="button" class="delete-btn" data-remove-category
                aria-label="Supprimer cette catégorie" title="Supprimer">×</button>
    </div>

    @if ($rowId)
        <input type="hidden" data-name="id" name="categories[{{ $index }}][id]" value="{{ $rowId }}">
    @endif

    <p class="category-hint" data-hint @if (! $get('hint')) hidden @endif>{{ $get('hint') }}</p>

    {{-- Sélecteur de note : 5 segments colorés au lieu du <select> « 1/5…5/5 ».
         La couleur du barème est désormais visible dans le formulaire. --}}
    <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
        <div>
            <span class="form-label small d-block mb-1">Note</span>
            <div class="score-picker" data-score-picker role="radiogroup" aria-label="Note de la catégorie">
                @foreach ($scale as $level => $meta)
                    <input type="radio" data-name="score"
                           name="categories[{{ $index }}][score]"
                           id="cat-{{ $index }}-score-{{ $level }}"
                           value="{{ $level }}" @checked($rowScore === $level)>
                    <label for="cat-{{ $index }}-score-{{ $level }}" data-score-label="{{ $level }}"
                           title="{{ $level }} — {{ $meta['label'] }} : {{ $meta['description'] }}">
                        {{ $level }}
                    </label>
                @endforeach
            </div>
            <span class="small fw-semibold" data-score-caption></span>
        </div>

        <div>
            <label class="form-label small d-block mb-1" for="cat-{{ $index }}-weight">Poids</label>
            <select data-name="weight" name="categories[{{ $index }}][weight]"
                    id="cat-{{ $index }}-weight" class="form-select form-select-sm" style="width:auto">
                @for ($w = 1; $w <= 5; $w++)
                    <option value="{{ $w }}" @selected($rowWeight === $w)>×{{ $w }}</option>
                @endfor
            </select>
        </div>
    </div>

    <label class="form-label small mb-1" for="cat-{{ $index }}-obs">Observations</label>
    <textarea data-name="observations" name="categories[{{ $index }}][observations]"
              id="cat-{{ $index }}-obs" class="form-control form-control-sm mb-2" rows="3"
              placeholder="Ce qui a été constaté…">{{ $get('observations') }}</textarea>

    <label class="form-label small mb-1" for="cat-{{ $index }}-rec">Recommandation</label>
    <textarea data-name="recommendations" name="categories[{{ $index }}][recommendations]"
              id="cat-{{ $index }}-rec" class="form-control form-control-sm mb-2" rows="3"
              placeholder="L'action à mener…">{{ $get('recommendations') }}</textarea>

    {{-- Criticité, échéance et responsable : sans eux, une recommandation
         n'est pas un plan d'action exploitable par le client. --}}
    <div class="row g-2">
        <div class="col-4">
            <label class="form-label small mb-1" for="cat-{{ $index }}-priority">Criticité</label>
            <select data-name="priority" name="categories[{{ $index }}][priority]"
                    id="cat-{{ $index }}-priority" class="form-select form-select-sm">
                <option value="">—</option>
                @foreach (\App\Enums\Priority::options() as $value => $label)
                    <option value="{{ $value }}" @selected($get('priority') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-4">
            <label class="form-label small mb-1" for="cat-{{ $index }}-due">Échéance</label>
            <input type="date" data-name="due_on" name="categories[{{ $index }}][due_on]"
                   id="cat-{{ $index }}-due" class="form-control form-control-sm" value="{{ $dueOn }}">
        </div>
        <div class="col-4">
            <label class="form-label small mb-1" for="cat-{{ $index }}-owner">Responsable</label>
            <input type="text" data-name="owner" name="categories[{{ $index }}][owner]"
                   id="cat-{{ $index }}-owner" class="form-control form-control-sm"
                   placeholder="DSI, prestataire…" value="{{ $get('owner') }}">
        </div>
    </div>
</div>
