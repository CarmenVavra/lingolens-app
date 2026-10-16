<x-layouts.guest title="Anmelden">
    <div class="page-intro">
        <span class="kicker">Willkommen zurück</span>
        <h1 class="page-title">Anmelden</h1>
    </div>

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="field">
            <label for="email">E-Mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="input" required autofocus autocomplete="username"
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <span id="email-error" class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label for="password">Passwort</label>
            <input id="password" type="password" name="password" class="input" required autocomplete="current-password"
                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            @error('password')
                <span id="password-error" class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <label class="checkbox">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            Angemeldet bleiben
        </label>

        <button type="submit" class="btn btn-primary btn-block">
            <span class="btn-label">Anmelden</span>
            <x-icon name="arrow-right" />
        </button>
    </form>

    <p style="font-size:15px">Noch kein Konto? <a href="{{ route('register') }}">Jetzt registrieren</a></p>
</x-layouts.guest>
