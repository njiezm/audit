<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Audit Master — plateforme d'audit et de reporting NJIEZM.FR">
    <title>@yield('title', 'Audit Master') · {{ config('app.name') }}</title>

    {{-- Jeu d'icônes complet : le favicon.ico livré faisait 0 octet. --}}
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon-32.png') }}" sizes="32x32" type="image/png">
    <link rel="icon" href="{{ asset('favicon-16.png') }}" sizes="16x16" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#003366">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="@yield('title', 'Audit Master')">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta name="twitter:card" content="summary_large_image">

    {{-- Évite le flash de thème clair avant l'exécution du bundle. --}}
    <script>
        (function () {
            var stored = localStorage.getItem('audit-master-theme');
            if (stored === 'dark' || stored === 'light') {
                document.documentElement.dataset.theme = stored;
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
    <a class="skip-link" href="#main-content">Aller au contenu principal</a>

    @include('partials.header')

    <main id="main-content" class="@yield('main-class', 'py-4')">
        @hasSection('bare')
            @yield('content')
        @else
            <div class="container-xxl">
                @include('partials.flash')
                @yield('content')
            </div>
        @endif
    </main>

    @unless (View::hasSection('hide-footer'))
        @include('partials.footer')
    @endunless

    @include('partials.confirm-modal')

    @stack('scripts')
</body>
</html>
