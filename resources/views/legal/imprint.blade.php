@php
    $operator = $legal['operator'];
    $business = array_filter($legal['business']);
@endphp

<x-layouts.page title="Impressum">
    <span class="kicker">Rechtliches</span>
    <h1 class="page-title">Impressum</h1>
    <p class="legal__lead">Informationen und Offenlegung gemäß § 5 E-Commerce-Gesetz (ECG), § 14 Unternehmensgesetzbuch (UGB) und § 25 Mediengesetz (MedienG).</p>

    <section>
        <h2>Medieninhaber:in und Betreiber:in</h2>
        <address>
            <strong><x-legal-value :value="$operator['name']" env="LEGAL_NAME" /></strong><br>
            <x-legal-value :value="$operator['street']" env="LEGAL_STREET" /><br>
            <x-legal-value :value="$operator['city']" env="LEGAL_CITY" /><br>
            {{ $operator['country'] }}
        </address>
    </section>

    <section>
        <h2>Kontakt</h2>
        <dl class="legal__facts">
            <dt>E-Mail</dt>
            <dd>
                @if (filled($operator['email']))
                    <a href="mailto:{{ $operator['email'] }}">{{ $operator['email'] }}</a>
                @else
                    <x-legal-value :value="null" env="LEGAL_EMAIL" />
                @endif
            </dd>
            @if (filled($operator['phone']))
                <dt>Telefon</dt>
                <dd><a href="tel:{{ preg_replace('/[^\d+]/', '', $operator['phone']) }}">{{ $operator['phone'] }}</a></dd>
            @endif
        </dl>
    </section>

    @if ($business !== [])
        <section>
            <h2>Unternehmensangaben</h2>
            <dl class="legal__facts">
                @foreach ([
                    'purpose' => 'Unternehmensgegenstand',
                    'vat_id' => 'UID-Nummer',
                    'register_number' => 'Firmenbuchnummer',
                    'register_court' => 'Firmenbuchgericht',
                    'authority' => 'Aufsichts-/Gewerbebehörde',
                    'chamber' => 'Kammer',
                ] as $key => $label)
                    @if (isset($business[$key]))
                        <dt>{{ $label }}</dt>
                        <dd>{{ $business[$key] }}</dd>
                    @endif
                @endforeach
            </dl>
        </section>
    @endif

    <section>
        <h2>Grundlegende Richtung</h2>
        <p>{{ $legal['editorial_line'] }}</p>
    </section>

    <section>
        <h2>Hinweis zu KI-Korrekturen</h2>
        <p>
            Die Korrekturen, Erklärungen und Tipps in LingoLens AI werden automatisch mit Hilfe künstlicher Intelligenz erstellt.
            Sie können Fehler enthalten und ersetzen nicht die Beurteilung durch eine Lehrkraft. Der Matura-Ready-Score ist eine
            Orientierungshilfe und keine Note.
        </p>
    </section>

    <section>
        <h2>Haftung für Links</h2>
        <p>
            Für Inhalte externer Websites, auf die verlinkt wird, sind ausschließlich deren Betreiber:innen verantwortlich.
            Bei Bekanntwerden von Rechtsverletzungen werden entsprechende Links umgehend entfernt.
        </p>
    </section>

    <section>
        <h2>Urheberrecht</h2>
        <p>
            Die Gestaltung und Programmierung dieser Website sind urheberrechtlich geschützt. Hochgeladene Texte bleiben
            Eigentum der jeweiligen Schüler:innen und werden ausschließlich für ihre Korrektur verwendet.
        </p>
    </section>

    <p class="legal__updated">Stand: {{ $updatedAt }}</p>
</x-layouts.page>
