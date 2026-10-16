<x-layouts.guest title="Passwort vergessen">
    <div class="page-intro">
        <span class="kicker">Passwort vergessen</span>
        <h1 class="page-title">Neues Passwort anfordern</h1>
        <p>Gib die E-Mail-Adresse deines Kontos ein. Wir schicken dir einen Link, mit dem du ein neues Passwort festlegen kannst.</p>
    </div>

    @if (session('status'))
        <div class="flash" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="field">
            <label for="email">E-Mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="input" required autofocus autocomplete="username"
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <span id="email-error" class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            <span class="btn-label">Link senden</span>
            <x-icon name="arrow-right" />
        </button>
    </form>

    <p style="font-size:15px"><a href="{{ route('login') }}">Zurück zur Anmeldung</a></p>
</x-layouts.guest>
