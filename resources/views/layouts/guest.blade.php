<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') · {{ config('app.name') }}</title>

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon-32.png') }}" sizes="32x32" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <meta name="theme-color" content="#003366">

    <script>
        (function () {
            var stored = localStorage.getItem('audit-master-theme');
            if (stored === 'dark' || stored === 'light') {
                document.documentElement.dataset.theme = stored;
            }
        })();
    </script>

    {{-- Même bundle que le reste du site : plus de Bootstrap en CDN ni de
         charte recopiée en CSS inline dans la page de connexion. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="login-shell">
        <div class="login-card">
            <div class="login-card__aside">
                <x-logo variant="reversed" :size="44" class="mb-4" />

                <h1 class="h5 mb-3">@yield('aside-title', 'Plateforme d\'audit professionnelle')</h1>
                <p class="small mb-4">
                    @yield('aside-text', 'Évaluez, notez et signez vos rapports d\'audit. Chaque document signé
                    reçoit une empreinte d\'intégrité vérifiable par votre client.')
                </p>

                <ul class="list-unstyled small mb-4" style="color: rgba(255,255,255,.85)">
                    <li class="mb-2">✓ Notation pondérée et plan d'action</li>
                    <li class="mb-2">✓ Rapport PDF signé électroniquement</li>
                    <li class="mb-2">✓ Vérification publique par code</li>
                </ul>

                <p class="small mt-auto mb-0" style="color: rgba(255,255,255,.7)">
                    © {{ date('Y') }} NJIEZM.FR — Expertise Stratégique
                </p>
            </div>

            <div class="login-card__main">
                @include('partials.flash')
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
