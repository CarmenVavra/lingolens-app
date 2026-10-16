<x-layouts.guest title="Registrieren">
    <div class="page-intro">
        <span class="kicker">Neues Konto</span>
        <h1 class="page-title">Registrieren</h1>
        <p>Dein Schulprofil bestimmt, wie LingoLens deine Texte korrigiert.</p>
    </div>

    @if ($errors->any())
        <div class="alert-error" role="alert">
            <strong>Bitte überprüfe deine Angaben.</strong>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" novalidate x-data="{ school: @js(old('school_type', 'BHS')) }">
        @csrf

        @foreach ([
            ['name', 'Name', 'text', 'name'],
            ['email', 'E-Mail', 'email', 'username'],
        ] as [$field, $label, $type, $autocomplete])
            <div class="field">
                <label for="{{ $field }}">{{ $label }}</label>
                <input id="{{ $field }}" type="{{ $type }}" name="{{ $field }}" value="{{ old($field) }}" class="input" required autocomplete="{{ $autocomplete }}"
                    @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                @error($field)
                    <span id="{{ $field }}-error" class="field-error">{{ $message }}</span>
                @enderror
            </div>
        @endforeach

        <div class="field-row">
            <div class="field">
                <label for="password">Passwort</label>
                <input id="password" type="password" name="password" class="input" required autocomplete="new-password"
                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                @error('password')
                    <span id="password-error" class="field-error">{{ $message }}</span>
                @enderror
            </div>
            <div class="field">
                <label for="password_confirmation">Passwort wiederholen</label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password">
            </div>
        </div>

        <fieldset class="field" style="border:0;padding:0;margin:0">
            <legend class="field-label" style="margin-bottom:8px">Schultyp</legend>
            <div class="radio-grid">
                @foreach (App\Enums\SchoolType::cases() as $type)
                    <label class="radio-card">
                        <input type="radio" name="school_type" value="{{ $type->value }}" x-model="school">
                        <span class="dot" aria-hidden="true"></span>
                        <span>
                            <span class="radio-card__title">{{ $type->value }}</span>
                            <span class="radio-card__sub">{{ $type->description() }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="field-row" x-show="school === 'BHS'">
            <div class="field">
                <label for="school_form">Schulform</label>
                <select id="school_form" name="school_form" class="select" :disabled="school !== 'BHS'"
                    @error('school_form') aria-invalid="true" aria-describedby="school_form-error" @enderror>
                    @foreach ($schoolForms as $form)
                        <option value="{{ $form }}" @selected(old('school_form') === $form)>{{ $form }}</option>
                    @endforeach
                </select>
                @error('school_form')
                    <span id="school_form-error" class="field-error">{{ $message }}</span>
                @enderror
            </div>
            <div class="field">
                <label for="department">Fachrichtung</label>
                <select id="department" name="department" class="select" :disabled="school !== 'BHS'"
                    @error('department') aria-invalid="true" aria-describedby="department-error" @enderror>
                    @foreach ($departments as $department)
                        <option value="{{ $department }}" @selected(old('department') === $department)>{{ $department }}</option>
                    @endforeach
                </select>
                @error('department')
                    <span id="department-error" class="field-error">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="class_name">Klasse (optional)</label>
                <input id="class_name" type="text" name="class_name" value="{{ old('class_name') }}" class="input" placeholder="z. B. 5CHC" maxlength="20"
                    @error('class_name') aria-invalid="true" aria-describedby="class_name-error" @enderror>
                @error('class_name')
                    <span id="class_name-error" class="field-error">{{ $message }}</span>
                @enderror
            </div>
            <div class="field">
                <label for="grade">Schulstufe (optional)</label>
                <select id="grade" name="grade" class="select"
                    @error('grade') aria-invalid="true" aria-describedby="grade-error" @enderror>
                    <option value="">–</option>
                    @foreach (range(1, 8) as $grade)
                        <option value="{{ $grade }}" @selected((string) old('grade') === (string) $grade)>{{ $grade }}.</option>
                    @endforeach
                </select>
                @error('grade')
                    <span id="grade-error" class="field-error">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            <span class="btn-label">Konto erstellen</span>
            <x-icon name="arrow-right" />
        </button>
    </form>

    <p style="font-size:15px">Schon registriert? <a href="{{ route('login') }}">Anmelden</a></p>
</x-layouts.guest>
