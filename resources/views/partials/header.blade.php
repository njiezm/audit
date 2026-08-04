@php
    // Forme bloc volontaire : la variante en ligne est mal interprétée par le
    // compilateur Blade dès qu'un bloc PHP est fermé plus loin dans le fichier.
    $user = auth()->user();
@endphp

<header class="header-bar">
    <nav class="navbar navbar-expand-lg py-0 container-xxl" aria-label="Navigation principale">
        <a class="navbar-brand py-2" href="{{ $user ? route('dashboard') : route('login') }}"
           aria-label="Audit Master — accueil">
            <x-logo variant="reversed" :size="36" />
        </a>

        @if ($user)
            <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse"
                    data-bs-target="#main-nav" aria-controls="main-nav" aria-expanded="false"
                    aria-label="Afficher la navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="main-nav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard*') ? 'active' : '' }}"
                           href="{{ route('dashboard') }}">Tableau de bord</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('audits.*') ? 'active' : '' }}"
                           href="{{ route('audits.index') }}">Audits</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}"
                           href="{{ route('clients.index') }}">Clients</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('templates.*') ? 'active' : '' }}"
                           href="{{ route('templates.index') }}">Modèles</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2 flex-wrap py-2 py-lg-0">
                    @can('create', App\Models\Audit::class)
                        <a href="{{ route('audits.create') }}" class="btn btn-sm btn-nj px-3">Nouvel audit</a>
                    @endcan

                    <button type="button" class="btn btn-sm btn-outline-light" data-theme-toggle
                            aria-label="Changer de thème">☾</button>

                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-light dropdown-toggle d-flex align-items-center gap-2"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="avatar-chip">{{ $user->initials() }}</span>
                            <span class="d-none d-xl-inline">{{ $user->name }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="dropdown-header">
                                {{ $user->name }}<br>
                                <span class="text-muted small">{{ $user->role->label() }}</span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Mon profil</a></li>
                            <li><a class="dropdown-item" href="{{ route('audits.trash') }}">Corbeille</a></li>
                            @if ($user->isAdmin())
                                <li><a class="dropdown-item" href="{{ route('users.index') }}">Utilisateurs</a></li>
                                <li><a class="dropdown-item" href="{{ route('activity.index') }}">Journal d'activité</a></li>
                            @endif
                            <li><a class="dropdown-item" href="{{ route('verify.form') }}">Vérifier un rapport</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST" data-no-loading="true">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">Déconnexion</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        @else
            <div class="ms-auto d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-light" data-theme-toggle
                        aria-label="Changer de thème">☾</button>
                <a href="{{ route('login') }}" class="btn btn-sm btn-nj px-3">Se connecter</a>
            </div>
        @endif
    </nav>
</header>
