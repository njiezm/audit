@csrf
@if ($method !== 'POST')
    @method($method)
@endif

<div class="row g-3">
    <div class="col-12 col-md-6">
        <label for="name" class="form-label">Nom <span aria-hidden="true">*</span></label>
        <input type="text" id="name" name="name" required
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $user->name) }}">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="email" class="form-label">Adresse e-mail <span aria-hidden="true">*</span></label>
        <input type="email" id="email" name="email" required
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $user->email) }}">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        <label for="role" class="form-label">Rôle <span aria-hidden="true">*</span></label>
        <select id="role" name="role" class="form-select @error('role') is-invalid @enderror"
                @disabled($user->exists && $user->id === auth()->id())>
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $user->role?->value ?? 'auditor') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @if ($user->exists && $user->id === auth()->id())
            <div class="form-text">Vous ne pouvez pas modifier votre propre rôle.</div>
        @else
            <div class="form-text">
                Administrateur : accès total. Auditeur : ses missions. Lecture seule : consultation.
            </div>
        @endif
    </div>

    <div class="col-12 col-md-6">
        <label for="job_title" class="form-label">Fonction</label>
        <input type="text" id="job_title" name="job_title" class="form-control"
               value="{{ old('job_title', $user->job_title) }}">
    </div>

    @if ($user->exists && $user->id !== auth()->id())
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                       @checked(old('is_active', $user->is_active))>
                <label class="form-check-label" for="is_active">Compte actif</label>
            </div>
        </div>
    @endif

    <div class="col-12 col-md-6">
        <label for="password" class="form-label">
            Mot de passe @unless ($user->exists)<span aria-hidden="true">*</span>@endunless
        </label>
        <input type="password" id="password" name="password" autocomplete="new-password"
               class="form-control @error('password') is-invalid @enderror"
               @required(! $user->exists)>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">
            10 caractères minimum, lettres et chiffres.
            @if ($user->exists) Laissez vide pour ne pas le changer. @endif
        </div>
    </div>

    <div class="col-12 col-md-6">
        <label for="password_confirmation" class="form-label">Confirmation</label>
        <input type="password" id="password_confirmation" name="password_confirmation"
               autocomplete="new-password" class="form-control" @required(! $user->exists)>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-nj">{{ $submitLabel }}</button>
    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Annuler</a>
</div>
