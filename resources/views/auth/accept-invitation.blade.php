<x-layouts.guest title="Einladung">
    <div class="page-intro">
        <span class="kicker">Einladung</span>
        <h1 class="page-title">Willkommen bei LingoLens AI</h1>
        <p>Leg dein Passwort fest – danach kannst du gleich deinen ersten Text scannen.</p>
    </div>

    <form method="POST" action="{{ route('invitation.store') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="field">
            <label for="email">E-Mail</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" class="input" required readonly autocomplete="username"
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <span id="email-error" class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label for="password">Passwort</label>
            <input id="password" type="password" name="password" class="input" required autofocus autocomplete="new-password"
                aria-describedby="password-hint @error('password') password-error @enderror"
                @error('password') aria-invalid="true" @enderror>
            <span id="password-hint" class="setup-step__hint">Mindestens 8 Zeichen.</span>
            @error('password')
                <span id="password-error" class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Passwort wiederholen</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password">
        </div>

        <div class="field">
            <label class="checkbox checkbox--top">
                <input type="checkbox" name="privacy" value="1" required @checked(old('privacy'))
                    @error('privacy') aria-invalid="true" aria-describedby="privacy-error" @enderror>
                <span>
                    Ich habe die <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">Datenschutzerklärung</a>
                    gelesen und bin mindestens {{ config('legal.minimum_age') }} Jahre alt – oder meine Erziehungsberechtigten sind einverstanden.
                </span>
            </label>
            @error('privacy')
                <span id="privacy-error" class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            <span class="btn-label">Passwort speichern &amp; loslegen</span>
            <x-icon name="arrow-right" />
        </button>
    </form>

    <p style="font-size:15px">Schon ein Passwort? <a href="{{ route('login') }}">Anmelden</a></p>
</x-layouts.guest>
