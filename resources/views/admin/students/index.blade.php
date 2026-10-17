<x-layouts.app title="Schüler:innen" active="students">
    <div class="stack-20 narrow">
        <div class="page-intro">
            <span class="kicker">Verwaltung</span>
            <h1 class="page-title">Schüler:innen</h1>
            <p>Nur du kannst Konten anlegen. Jede:r bekommt eine Einladung per E-Mail und legt das Passwort selbst fest.</p>
        </div>

        <a href="{{ route('admin.students.create') }}" class="btn btn-primary" style="font-size:16px;padding:16px 20px;gap:10px;align-self:flex-start">
            Schüler:in anlegen<x-icon name="arrow-right" />
        </a>

        @if ($students->isEmpty())
            <div class="empty-stats">
                <strong>Noch keine Schüler:innen.</strong>
                <span>Lege das erste Konto an – die Einladung geht automatisch per E-Mail hinaus.</span>
            </div>
        @else
            <ul class="scan-list scan-list--ruled" role="list" aria-label="Konten">
                @foreach ($students as $student)
                    @php
                        $accepted = $student->hasAcceptedInvitation();
                        $meta = collect([$student->email, $student->chipLabel(), $student->scans_count.' '.($student->scans_count === 1 ? 'Text' : 'Texte')])
                            ->filter()->implode(' · ');
                    @endphp
                    <li class="student-row">
                        <span class="scan-row__icon scan-row__icon--{{ $accepted ? 'completed' : 'processing' }}" aria-hidden="true">{{ $student->initials() }}</span>
                        <span class="scan-row__body">
                            <span class="scan-row__title">{{ $student->name }}</span>
                            <span class="scan-row__meta">{{ $meta }}</span>
                        </span>
                        <span class="tag status-tag--{{ $accepted ? 'completed' : 'processing' }}">{{ $accepted ? 'Aktiv' : 'Einladung offen' }}</span>

                        <div class="student-row__actions">
                            <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-secondary" aria-label="{{ $student->name }} bearbeiten">
                                <x-icon name="pencil" :size="18" />Bearbeiten
                            </a>
                            @unless ($accepted)
                                <form method="POST" action="{{ route('admin.students.invite', $student) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary" aria-label="Einladung an {{ $student->name }} erneut senden">
                                        Einladung erneut senden
                                    </button>
                                </form>
                            @endunless
                            <form method="POST" action="{{ route('admin.students.destroy', $student) }}"
                                x-data x-on:submit="if (! confirm(@js('Konto von '.$student->name.' mit allen Texten endgültig löschen?'))) $event.preventDefault()">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" aria-label="Konto von {{ $student->name }} löschen">
                                    <x-icon name="x" :size="18" />Löschen
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        <p class="setup-step__hint">
            Einladungslinks sind {{ $invitationDays }} Tage gültig.
            Wer sein Passwort vergisst, kann es auf der Login-Seite selbst zurücksetzen.
        </p>
    </div>
</x-layouts.app>
