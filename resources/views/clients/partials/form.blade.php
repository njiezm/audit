@csrf
@if ($method !== 'POST')
    @method($method)
@endif

<div class="row g-3">
    <div class="col-12 col-md-6">
        <label for="name" class="form-label">Raison sociale <span aria-hidden="true">*</span></label>
        <input type="text" id="name" name="name" required
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $client->name) }}">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-6 col-md-3">
        <label for="siret" class="form-label">SIRET</label>
        <input type="text" id="siret" name="siret" class="form-control @error('siret') is-invalid @enderror"
               value="{{ old('siret', $client->siret) }}">
        @error('siret')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-6 col-md-3">
        <label for="sector" class="form-label">Secteur</label>
        <input type="text" id="sector" name="sector" class="form-control @error('sector') is-invalid @enderror"
               value="{{ old('sector', $client->sector) }}" placeholder="Industrie, santé…">
        @error('sector')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-4">
        <label for="contact_name" class="form-label">Contact</label>
        <input type="text" id="contact_name" name="contact_name" class="form-control"
               value="{{ old('contact_name', $client->contact_name) }}">
    </div>

    <div class="col-12 col-md-4">
        <label for="contact_email" class="form-label">E-mail du contact</label>
        <input type="email" id="contact_email" name="contact_email"
               class="form-control @error('contact_email') is-invalid @enderror"
               value="{{ old('contact_email', $client->contact_email) }}">
        @error('contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Pré-rempli lors de l'envoi d'un rapport.</div>
    </div>

    <div class="col-12 col-md-4">
        <label for="contact_phone" class="form-label">Téléphone</label>
        <input type="text" id="contact_phone" name="contact_phone" class="form-control"
               value="{{ old('contact_phone', $client->contact_phone) }}">
    </div>

    <div class="col-12 col-md-6">
        <label for="address" class="form-label">Adresse</label>
        <textarea id="address" name="address" rows="3" class="form-control">{{ old('address', $client->address) }}</textarea>
    </div>

    <div class="col-12 col-md-6">
        <label for="notes" class="form-label">Notes internes</label>
        <textarea id="notes" name="notes" rows="3" class="form-control"
                  placeholder="Contexte, historique, points d'attention…">{{ old('notes', $client->notes) }}</textarea>
    </div>

    <div class="col-12 col-md-6">
        <label for="logo" class="form-label">Logo</label>
        <input type="file" id="logo" name="logo" class="form-control @error('logo') is-invalid @enderror"
               accept="image/png,image/jpeg,image/webp">
        @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Repris en couverture du rapport PDF. PNG ou JPG, 2 Mo max.</div>

        @if ($client->logo_path)
            <img src="{{ asset('storage/'.$client->logo_path) }}" alt="Logo actuel de {{ $client->name }}"
                 class="mt-2 border rounded p-2 bg-white" style="max-height:64px">
        @endif
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-nj">{{ $submitLabel }}</button>
    <a href="{{ $client->exists ? route('clients.show', $client) : route('clients.index') }}"
       class="btn btn-outline-secondary">Annuler</a>
</div>
