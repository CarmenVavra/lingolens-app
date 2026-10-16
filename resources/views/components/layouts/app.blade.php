{{--
    App shell: navy top nav on laptop, top bar + bottom tab bar on smartphones (< 760px).
    $active: dashboard | setup | history
--}}
@props(['title' => null, 'active' => null])

@php
    $user = auth()->user();
    $navItems = [
        'dashboard' => ['label' => 'Dashboard', 'url' => route('dashboard')],
        'setup' => ['label' => 'Neuer Scan', 'url' => route('scans.create')],
        'history' => ['label' => 'Verlauf', 'url' => route('scans.index')],
    ];
@endphp

<!DOCTYPE html>
<html lang="de">
<head>
    <x-head :title="$title" />
</head>
<body>
    <a href="#main" class="skip-link">Zum Inhalt springen</a>

    <header class="app-nav only-desktop">
        <x-logo />
        <nav aria-label="Hauptnavigation" style="display:flex">
            @foreach ($navItems as $key => $item)
                <a href="{{ $item['url'] }}" class="app-nav__link" @if ($active === $key) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
        </nav>
        <div class="user-chip">
            <span class="avatar" aria-hidden="true">{{ $user->initials() }}</span>
            <a href="{{ route('account.show') }}" class="user-chip__text" title="Mein Konto">
                <span class="user-chip__name">{{ $user->name }}</span>
                <span class="user-chip__meta">{{ $user->chipLabel() }}</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-icon logout-btn" aria-label="Abmelden" title="Abmelden">
                    <x-icon name="log-out" :size="18" />
                </button>
            </form>
        </div>
    </header>

    <header class="app-topbar only-mobile">
        <x-logo />
        <details>
            <summary class="avatar" aria-label="Konto von {{ $user->name }}">{{ $user->initials() }}</summary>
            <div class="account-menu">
                <span style="font-weight:600">{{ $user->name }}</span>
                <span style="font-size:13px;color:var(--color-muted)">{{ $user->chipLabel() }}</span>
                <a href="{{ route('account.show') }}" class="btn btn-secondary" style="width:100%;justify-content:flex-start;min-height:44px">Konto &amp; Passwort</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="width:100%;justify-content:flex-start;min-height:44px">
                        <x-icon name="log-out" :size="18" /> Abmelden
                    </button>
                </form>
            </div>
        </details>
    </header>

    <main id="main" class="app-main" tabindex="-1">
        @if (session('status'))
            <div class="flash" role="status">{{ session('status') }}</div>
        @endif

        {{ $slot }}
    </main>

    <footer class="app-footer">
        <x-legal-links />
    </footer>

    <nav class="app-tabbar only-mobile" aria-label="Hauptnavigation">
        <a href="{{ route('dashboard') }}" class="app-tabbar__item" @if ($active === 'dashboard') aria-current="page" @endif>
            <x-icon name="house" :size="22" />Start
        </a>
        <a href="{{ route('scans.create') }}" class="app-tabbar__camera" aria-label="Neuen Text scannen" @if ($active === 'setup') aria-current="page" @endif>
            <span><x-icon name="camera" :size="26" /></span>
        </a>
        <a href="{{ route('scans.index') }}" class="app-tabbar__item" @if ($active === 'history') aria-current="page" @endif>
            <x-icon name="history" :size="22" />Verlauf
        </a>
    </nav>
</body>
</html>
