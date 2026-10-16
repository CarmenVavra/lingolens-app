<x-layouts.guest title="Neues Passwort">
    <div class="page-intro">
        <span class="kicker">Passwort zurücksetzen</span>
        <h1 class="page-title">Neues Passwort festlegen</h1>
    </div>

    <form method="POST" action="{{ route('password.store') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="field">
            <label for="email">E-Mail</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" class="input" required autocomplete="username"
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <span id="email-error" class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label for="password">Neues Passwort</label>
            <input id="password" type="password" name="password" class="input" required autofocus autocomplete="new-password"
                aria-describedby="password-hint @error('password') password-error @enderror"
                @error('password') aria-invalid="true" @enderror>
            <span id="password-hint" class="setup-step__hint">Mindestens 8 Zeichen.</span>
            @error('password')
                <span id="password-error" class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Neues Passwort wiederholen</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            <span class="btn-label">Passwort speichern</span>
            <x-icon name="arrow-right" />
        </button>
    </form>

    <p style="font-size:15px"><a href="{{ route('password.request') }}">Neuen Link anfordern</a></p>
</x-layouts.guest>
