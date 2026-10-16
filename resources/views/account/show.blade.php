<x-layouts.app title="Mein Konto">
    <div class="stack-24 narrow">
        <div class="page-intro">
            <span class="kicker">Konto</span>
            <h1 class="page-title">Mein Konto</h1>
        </div>

        <section class="setup-card" aria-labelledby="profile-title">
            <div class="setup-step">
                <h2 id="profile-title" class="account-title">Profil</h2>
                <dl class="legal__facts">
                    <dt>Name</dt>
                    <dd>{{ $user->name }}</dd>
                    <dt>E-Mail</dt>
                    <dd>{{ $user->email }}</dd>
                    <dt>Schule</dt>
                    <dd>{{ $user->schoolLabel() }}@if ($user->department) · {{ $user->department }}@endif</dd>
                    @if ($user->class_name || $user->gradeLabel())
                        <dt>Klasse</dt>
                        <dd>{{ collect([$user->class_name, $user->gradeLabel()])->filter()->implode(' · ') }}</dd>
                    @endif
                </dl>
            </div>
        </section>

        <section class="setup-card" aria-labelledby="password-title">
            <form method="POST" action="{{ route('account.password') }}" class="setup-step" novalidate>
                @csrf
                @method('PUT')

                <h2 id="password-title" class="account-title">Passwort ändern</h2>

                @if ($errors->updatePassword->any())
                    <div class="alert-error" role="alert">
                        <strong>Das Passwort wurde nicht geändert.</strong>
                    </div>
                @endif

                @foreach ([
                    ['current_password', 'Aktuelles Passwort', 'current-password'],
                    ['password', 'Neues Passwort', 'new-password'],
                    ['password_confirmation', 'Neues Passwort wiederholen', 'new-password'],
                ] as [$field, $label, $autocomplete])
                    <div class="field">
                        <label for="{{ $field }}">{{ $label }}</label>
                        <input id="{{ $field }}" type="password" name="{{ $field }}" class="input" required autocomplete="{{ $autocomplete }}"
                            @if ($errors->updatePassword->has($field)) aria-invalid="true" aria-describedby="{{ $field }}-error" @endif>
                        @if ($errors->updatePassword->has($field))
                            <span id="{{ $field }}-error" class="field-error">{{ $errors->updatePassword->first($field) }}</span>
                        @endif
                    </div>
                @endforeach

                <p class="setup-step__hint">Mindestens 8 Zeichen.</p>

                <button type="submit" class="btn btn-primary" style="align-self:flex-start;padding:14px 18px;font-size:15px">
                    Passwort speichern
                </button>
            </form>
        </section>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-secondary" style="min-height:44px">
                <x-icon name="log-out" :size="18" /> Abmelden
            </button>
        </form>
    </div>
</x-layouts.app>
