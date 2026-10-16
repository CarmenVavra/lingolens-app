{{-- Simple text page (Impressum, Datenschutz) – reachable with and without login. --}}
@props(['title'])

<!DOCTYPE html>
<html lang="de">
<head>
    <x-head :title="$title" />
</head>
<body>
    <a href="#main" class="skip-link">Zum Inhalt springen</a>

    <header class="app-topbar page-topbar">
        <x-logo :href="auth()->check() ? route('dashboard') : route('login')" />
        @auth
            <a href="{{ route('dashboard') }}" class="btn page-topbar__link">Zur App</a>
        @else
            <a href="{{ route('login') }}" class="btn page-topbar__link">Anmelden</a>
        @endauth
    </header>

    <main id="main" class="legal-page" tabindex="-1">
        <article class="legal">
            {{ $slot }}
        </article>
        <x-legal-links />
    </main>
</body>
</html>
