<header class="header-bar">
    <div class="brand-font fs-3">NJIEZM <span class="fs-6" style="color: var(--nj-yellow);">| AUDIT MASTER</span></div>
    
    <div class="d-flex gap-2 align-items-center">
        @if(session('authenticated'))
            <!-- Affiché si l'utilisateur est connecté -->
            <span class="text-white me-3">
                Bienvenue, Expert N'jie ZAMON
            </span>
            
            <a href="{{ route('audits.index') }}" class="btn btn-sm btn-nj px-3">Liste des audits</a>
            <a href="{{ route('audits.create') }}" class="btn btn-sm btn-outline-light px-3">Nouvel audit</a>
            
            <!-- Formulaire de déconnexion (méthode POST pour la sécurité) -->
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-warning px-3">Déconnexion</button>
            </form>
        @else
            <!-- Affiché si l'utilisateur n'est pas connecté (au cas où) -->
            <a href="{{ route('login') }}" class="btn btn-sm btn-nj px-3">Se connecter</a>
        @endif
    </div>
</header>