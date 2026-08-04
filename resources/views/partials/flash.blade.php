{{-- Messages flash centralisés : ils étaient recopiés dans chaque vue. --}}

@foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'] as $key => $variant)
    @if (session($key))
        <div class="alert alert-{{ $variant }} alert-dismissible fade show d-flex align-items-center gap-2"
             role="alert" @if ($key === 'success') data-autohide="6000" @endif>
            <div class="flex-grow-1">
                {{ session($key) }}

                @if ($key === 'success' && session('undo'))
                    <form action="{{ session('undo') }}" method="POST" class="d-inline ms-2" data-no-loading="true">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-success">Annuler</button>
                    </form>
                @endif
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif
@endforeach

{{-- Récapitulatif des erreurs de validation : la création et la modification
     d'audit n'en affichaient aucune, le formulaire revenait sans explication. --}}
@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <p class="fw-bold mb-2">
            {{ $errors->count() === 1 ? 'Un champ doit être corrigé :' : $errors->count().' champs doivent être corrigés :' }}
        </p>
        <ul class="mb-0 ps-3">
            @foreach ($errors->unique() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
