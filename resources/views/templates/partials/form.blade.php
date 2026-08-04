@php
    $rows = old('categories', $template->exists
        ? $template->categories->map(fn ($c) => ['title' => $c->title, 'weight' => $c->weight, 'hint' => $c->hint])->all()
        : [['title' => '', 'weight' => 1, 'hint' => '']]);
@endphp

@csrf
@if ($method !== 'POST')
    @method($method)
@endif

<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <label for="name" class="form-label">Nom du modèle <span aria-hidden="true">*</span></label>
        <input type="text" id="name" name="name" required
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $template->name) }}" placeholder="Audit cyber PME">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="description" class="form-label">Description</label>
        <input type="text" id="description" name="description" class="form-control"
               value="{{ old('description', $template->description) }}">
    </div>

    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1"
                   @checked(old('is_default', $template->is_default))>
            <label class="form-check-label" for="is_default">
                Modèle par défaut — pré-chargé à la création d'un nouvel audit
            </label>
        </div>
    </div>
</div>

<h2 class="h6 text-uppercase text-muted mb-2">Catégories</h2>

<div id="template-rows">
    @foreach ($rows as $index => $row)
        <div class="section-card" data-template-row>
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label small mb-1">Intitulé</label>
                    <input type="text" name="categories[{{ $index }}][title]" class="form-control form-control-sm"
                           value="{{ data_get($row, 'title') }}" placeholder="Sécurité & Cyber" required>
                </div>
                <div class="col-4 col-md-2">
                    <label class="form-label small mb-1">Poids</label>
                    <select name="categories[{{ $index }}][weight]" class="form-select form-select-sm">
                        @for ($w = 1; $w <= 5; $w++)
                            <option value="{{ $w }}" @selected((int) data_get($row, 'weight', 1) === $w)>×{{ $w }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-8 col-md-4">
                    <label class="form-label small mb-1">Aide à la saisie</label>
                    <input type="text" name="categories[{{ $index }}][hint]" class="form-control form-control-sm"
                           value="{{ data_get($row, 'hint') }}" placeholder="Points à contrôler…">
                </div>
                <div class="col-12 col-md-1 d-grid">
                    <button type="button" class="btn btn-sm btn-outline-danger" data-remove-row>Retirer</button>
                </div>
            </div>
        </div>
    @endforeach
</div>

<button type="button" class="btn btn-outline-secondary w-100 mb-4" data-add-row>+ Ajouter une catégorie</button>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-nj">{{ $submitLabel }}</button>
    <a href="{{ route('templates.index') }}" class="btn btn-outline-secondary">Annuler</a>
</div>

@push('scripts')
<script type="module">
    const list = document.getElementById('template-rows');
    let index = {{ count($rows) }};

    document.querySelector('[data-add-row]').addEventListener('click', () => {
        const row = list.firstElementChild.cloneNode(true);

        row.querySelectorAll('input, select').forEach((field) => {
            field.name = field.name.replace(/categories\[\d+\]/, `categories[${index}]`);
            if (field.tagName === 'SELECT') field.selectedIndex = 0;
            else field.value = '';
        });

        list.appendChild(row);
        index++;
        row.querySelector('input')?.focus();
    });

    list.addEventListener('click', (event) => {
        if (!event.target.closest('[data-remove-row]')) return;

        if (list.querySelectorAll('[data-template-row]').length <= 1) return;
        event.target.closest('[data-template-row]').remove();
    });
</script>
@endpush
