@php
    $operator = $legal['operator'];
    $age = $legal['minimum_age'];
@endphp

<x-layouts.page title="Datenschutzerklärung">
    <span class="kicker">Rechtliches</span>
    <h1 class="page-title">Datenschutzerklärung</h1>
    <p class="legal__lead">
        Wir nehmen den Schutz deiner Daten ernst. Hier erfährst du, welche Daten LingoLens AI verarbeitet, wofür, wie lange
        und welche Rechte du hast (Art. 13 Datenschutz-Grundverordnung, DSGVO).
    </p>

    <nav class="legal__toc" aria-label="Inhalt">
        <ol>
            <li><a href="#verantwortlich">Verantwortliche Stelle</a></li>
            <li><a href="#daten">Welche Daten wir verarbeiten</a></li>
            <li><a href="#zwecke">Zwecke und Rechtsgrundlagen</a></li>
            <li><a href="#empfaenger">Dienstleister und Empfänger</a></li>
            <li><a href="#drittland">Übermittlung in Drittländer</a></li>
            <li><a href="#speicherdauer">Speicherdauer</a></li>
            <li><a href="#cookies">Cookies</a></li>
            <li><a href="#ki">Künstliche Intelligenz</a></li>
            <li><a href="#alter">Mindestalter</a></li>
            <li><a href="#rechte">Deine Rechte</a></li>
        </ol>
    </nav>

    <section id="verantwortlich">
        <h2>1. Verantwortliche Stelle</h2>
        <address>
            <strong><x-legal-value :value="$operator['name']" env="LEGAL_NAME" /></strong><br>
            <x-legal-value :value="$operator['street']" env="LEGAL_STREET" /><br>
            <x-legal-value :value="$operator['city']" env="LEGAL_CITY" />, {{ $operator['country'] }}<br>
            E-Mail:
            @if (filled($operator['email']))
                <a href="mailto:{{ $operator['email'] }}">{{ $operator['email'] }}</a>
            @else
                <x-legal-value :value="null" env="LEGAL_EMAIL" />
            @endif
        </address>
    </section>

    <section id="daten">
        <h2>2. Welche Daten wir verarbeiten</h2>
        <h3>Konto</h3>
        <p>
            Name, E-Mail-Adresse, Passwort (nur als sicherer Hash gespeichert, nie im Klartext), dein Schulprofil
            (Schultyp, Schulform, Fachrichtung, Klasse, Schulstufe) sowie der Zeitpunkt, zu dem du diese
            Datenschutzerklärung bestätigt hast.
        </p>
        <h3>Deine Texte</h3>
        <p>
            Die Fotos und Dateien, die du hochlädst (Foto, PDF, Word, TXT, max. {{ $uploadLimitMb }} MB), der daraus
            erkannte Text, Textsorte und Schulangaben zum Text, die Korrekturen, Erklärungen und Tipps der KI, welche
            Korrekturen du als „verstanden“ markiert hast sowie daraus berechnete Statistiken (Fehler-Verteilung,
            Matura-Ready-Score).
        </p>
        <p class="legal__hint">
            Tipp: Schreib keine persönlichen Angaben wie deinen Namen, deine Adresse oder Gesundheitsdaten in die Texte,
            die du korrigieren lässt.
        </p>
        <h3>Technische Daten</h3>
        <p>
            Beim Aufruf der Website verarbeitet der Server technisch notwendige Daten: IP-Adresse, Datum und Uhrzeit,
            aufgerufene Seite, Browser und Betriebssystem. Fehler werden in Protokolldateien festgehalten.
        </p>
    </section>

    <section id="zwecke">
        <h2>3. Zwecke und Rechtsgrundlagen</h2>
        <dl class="legal__facts legal__facts--wide">
            <dt>Bereitstellung der Lernplattform</dt>
            <dd>
                Konto, Texterkennung, KI-Korrektur, Verlauf und Statistiken – zur Erfüllung des Nutzungsverhältnisses
                (Art. 6 Abs. 1 lit. b DSGVO).
            </dd>
            <dt>Sicherheit und Missbrauchsschutz</dt>
            <dd>
                Begrenzung von Anmeldeversuchen und Texten pro Tag, Fehlerprotokolle, Schutz vor Angriffen – berechtigtes
                Interesse an einem sicheren und bezahlbaren Betrieb (Art. 6 Abs. 1 lit. f DSGVO).
            </dd>
        </dl>
        <p>Es gibt keine Werbung, kein Tracking und keine Weitergabe deiner Daten zu Marketingzwecken.</p>
    </section>

    <section id="empfaenger">
        <h2>4. Dienstleister und Empfänger</h2>
        <p>Wir setzen folgende Auftragsverarbeiter ein, die deine Daten nur nach unserer Weisung verarbeiten (Art. 28 DSGVO):</p>
        <dl class="legal__facts legal__facts--wide">
            <dt>Hosting</dt>
            <dd>{{ $legal['hosting'] }} – Betrieb des Servers, auf dem LingoLens AI und alle Daten gespeichert sind.</dd>

            <dt>Texterkennung (Google Cloud Vision)</dt>
            <dd>
                Google Cloud EMEA Limited, Dublin, Irland. Nur Fotos und gescannte PDFs werden zur Handschrift-Erkennung
                übermittelt, nicht dein Name oder deine E-Mail-Adresse.
                @if ($visionInEu)
                    Wir nutzen den EU-Standort des Dienstes:
                    Google speichert und verarbeitet diese Daten ausschließlich in der Europäischen Union.
                @endif
                Laut Google werden die Bilder nur zur Texterkennung verarbeitet und nicht zum Training verwendet.
                Am Laptop geschriebene PDFs, Word- und Textdateien werden direkt auf unserem Server ausgelesen und
                nicht an Google gesendet.
            </dd>

            <dt>KI-Korrektur (OpenAI)</dt>
            <dd>
                OpenAI Ireland Ltd., Dublin, Irland. Übermittelt werden der erkannte Text sowie Schultyp, Fachrichtung und
                Textsorte – nicht dein Name oder deine E-Mail-Adresse. Laut OpenAI werden über die API gesendete Daten
                nicht zum Training der Modelle verwendet; zur Missbrauchserkennung können sie bis zu 30 Tage gespeichert werden.
            </dd>
        </dl>
    </section>

    <section id="drittland">
        <h2>5. Übermittlung in Drittländer</h2>
        <p>
            OpenAI kann Daten auch in den USA verarbeiten. Diese Übermittlung ist durch Standardvertragsklauseln der
            EU-Kommission (Art. 46 Abs. 2 lit. c DSGVO) im Auftragsverarbeitungsvertrag mit OpenAI abgesichert.
            @unless ($visionInEu)
                Auch Google kann Daten außerhalb der EU verarbeiten; die Übermittlung ist ebenfalls durch
                Standardvertragsklauseln abgesichert.
            @endunless
        </p>
    </section>

    <section id="speicherdauer">
        <h2>6. Speicherdauer</h2>
        <ul>
            <li><strong>Hochgeladene Fotos und Dateien:</strong> werden sofort nach der Analyse gelöscht (in der Regel nach weniger als einer Minute).</li>
            <li><strong>Erkannter Text, Korrekturen und Statistiken:</strong> solange dein Konto besteht, damit du deinen Verlauf und Fortschritt sehen kannst.</li>
            <li><strong>Konto:</strong> bis du die Löschung verlangst. Mit dem Konto werden alle deine Texte und Korrekturen gelöscht.</li>
            <li><strong>Protokolldateien:</strong> höchstens {{ $logDays }} Tage.</li>
            <li><strong>Sitzungsdaten:</strong> verfallen nach {{ $sessionHours }} Stunden Inaktivität bzw. beim Abmelden.</li>
        </ul>
    </section>

    <section id="cookies">
        <h2>7. Cookies</h2>
        <p>
            LingoLens AI verwendet ausschließlich technisch notwendige Cookies: ein Sitzungs-Cookie, das dich angemeldet hält,
            und ein Sicherheits-Cookie (XSRF-TOKEN) zum Schutz von Formularen. Dafür ist keine Einwilligung nötig
            (§ 165 Abs. 3 TKG 2021). Schriften werden direkt von unserem Server geladen, es werden keine Inhalte von
            Drittanbietern wie Google Fonts eingebunden.
        </p>
    </section>

    <section id="ki">
        <h2>8. Künstliche Intelligenz</h2>
        <p>
            Die Korrekturen werden automatisch von einer KI erstellt. Sie dienen nur dem Lernen, haben keine rechtliche
            Wirkung und sind keine Beurteilung im Sinne der Schule. Es findet keine automatisierte Entscheidung im Sinne
            von Art. 22 DSGVO statt. KI-Korrekturen können Fehler enthalten.
        </p>
    </section>

    <section id="alter">
        <h2>9. Mindestalter</h2>
        <p>
            LingoLens AI richtet sich an Schüler:innen der Oberstufe. Wer jünger als {{ $age }} Jahre ist, darf die App nur
            mit Zustimmung der Erziehungsberechtigten nutzen (§ 4 Abs. 4 Datenschutzgesetz).
        </p>
    </section>

    <section id="rechte">
        <h2>10. Deine Rechte</h2>
        <p>Du hast das Recht auf</p>
        <ul>
            <li>Auskunft über deine gespeicherten Daten (Art. 15 DSGVO),</li>
            <li>Berichtigung unrichtiger Daten (Art. 16 DSGVO),</li>
            <li>Löschung (Art. 17 DSGVO) und Einschränkung der Verarbeitung (Art. 18 DSGVO),</li>
            <li>Datenübertragbarkeit (Art. 20 DSGVO),</li>
            <li>Widerspruch gegen Verarbeitungen auf Grundlage berechtigter Interessen (Art. 21 DSGVO).</li>
        </ul>
        <p>
            Schreib dafür einfach eine E-Mail an
            @if (filled($operator['email']))
                <a href="mailto:{{ $operator['email'] }}">{{ $operator['email'] }}</a>.
            @else
                <x-legal-value :value="null" env="LEGAL_EMAIL" />.
            @endif
        </p>
        <p>
            Wenn du meinst, dass die Verarbeitung deiner Daten gegen das Datenschutzrecht verstößt, kannst du dich bei der
            österreichischen Datenschutzbehörde beschweren: Barichgasse 40–42, 1030 Wien,
            <a href="mailto:dsb@dsb.gv.at">dsb@dsb.gv.at</a>, <a href="https://www.dsb.gv.at" rel="noopener">www.dsb.gv.at</a>.
        </p>
    </section>

    <p class="legal__updated">Stand: {{ $updatedAt }}</p>
</x-layouts.page>
