<x-layouts.app title="Schüler:in bearbeiten" active="students">
    <div class="stack-24 narrow">
        <a href="{{ route('admin.students.index') }}" class="btn btn-ghost btn-back">
            <x-icon name="chevron-left" :size="18" />Alle Schüler:innen
        </a>

        <div class="page-intro">
            <span class="kicker">Verwaltung</span>
            <h1 class="page-title">{{ $student->name }} bearbeiten</h1>
            <p>
                Das Passwort legt die Schülerin bzw. der Schüler selbst fest – es kann hier nicht geändert werden.
                @unless ($student->hasAcceptedInvitation())
                    Änderst du die E-Mail-Adresse, geht die Einladung automatisch an die neue Adresse.
                @endunless
            </p>
        </div>

        @if ($errors->any())
            <div class="alert-error" role="alert">
                <strong>Bitte überprüfe die Angaben.</strong>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.students.update', $student) }}" class="setup-card" novalidate
            x-data="{ school: @js(old('school_type', $student->school_type->value)) }">
            @csrf
            @method('PUT')

            @include('admin.students.partials.fields', ['student' => $student])

            <div class="setup-step">
                <button type="submit" class="btn btn-primary" style="align-self:flex-start;padding:14px 18px;font-size:15px;gap:10px">
                    Änderungen speichern<x-icon name="check" :size="18" />
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
