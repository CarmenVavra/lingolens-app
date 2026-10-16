<x-layouts.app title="Schüler:in anlegen" active="students">
    <div class="stack-24 narrow">
        <a href="{{ route('admin.students.index') }}" class="btn btn-ghost btn-back">
            <x-icon name="chevron-left" :size="18" />Alle Schüler:innen
        </a>

        <div class="page-intro">
            <span class="kicker">Verwaltung</span>
            <h1 class="page-title">Schüler:in anlegen</h1>
            <p>
                Nach dem Speichern bekommt die Schülerin bzw. der Schüler eine E-Mail mit einem Link. Damit legt sie bzw. er
                selbst ein Passwort fest und bestätigt die Datenschutzerklärung.
            </p>
        </div>

        @if ($errors->any())
            <div class="alert-error" role="alert">
                <strong>Bitte überprüfe die Angaben.</strong>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.students.store') }}" class="setup-card" novalidate
            x-data="{ school: @js(old('school_type', 'BHS')) }">
            @csrf

            <div class="setup-step">
                <h2 class="account-title">Person</h2>

                @foreach ([
                    ['name', 'Name', 'text', 'off'],
                    ['email', 'E-Mail', 'email', 'off'],
                ] as [$field, $label, $type, $autocomplete])
                    <div class="field">
                        <label for="{{ $field }}">{{ $label }}</label>
                        <input id="{{ $field }}" type="{{ $type }}" name="{{ $field }}" value="{{ old($field) }}" class="input" required autocomplete="{{ $autocomplete }}"
                            @if ($loop->first) autofocus @endif
                            @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                        @error($field)
                            <span id="{{ $field }}-error" class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                @endforeach
            </div>

            <div class="setup-step">
                <h2 class="account-title">Schulprofil</h2>
                <p class="setup-step__hint">Bestimmt, wie LingoLens die Texte korrigiert (Textsorten, Fachbegriffe).</p>

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
                    @error('school_type')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
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
            </div>

            <div class="setup-step">
                <button type="submit" class="btn btn-primary" style="align-self:flex-start;padding:14px 18px;font-size:15px;gap:10px">
                    Anlegen &amp; Einladung senden<x-icon name="arrow-right" :size="18" />
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
