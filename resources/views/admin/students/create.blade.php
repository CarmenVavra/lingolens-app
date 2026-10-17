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

            @include('admin.students.partials.fields')

            <div class="setup-step">
                <button type="submit" class="btn btn-primary" style="align-self:flex-start;padding:14px 18px;font-size:15px;gap:10px">
                    Anlegen &amp; Einladung senden<x-icon name="arrow-right" :size="18" />
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
