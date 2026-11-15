<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo mode
    |--------------------------------------------------------------------------
    |
    | Without API keys LingoLens runs in demo mode: OCR and AI correction are
    | replaced by the sample texts from the design handoff. Leave the value
    | empty to switch automatically (demo mode when a key is missing).
    |
    */

    'demo_mode' => env('LINGOLENS_DEMO_MODE'),

    // Artificial pause between the processing steps in demo mode (milliseconds).
    'demo_step_delay_ms' => (int) env('LINGOLENS_DEMO_STEP_DELAY_MS', 1200),

    'openai_model' => env('OPENAI_MODEL', 'gpt-4o'),

    // Handwriting recognition for photos and scanned PDFs:
    // "openai" (vision model, reads cursive well) or "google" (Cloud Vision, EU endpoint).
    'ocr_provider' => env('LINGOLENS_OCR_PROVIDER', 'openai'),
    'ocr_model' => env('OPENAI_OCR_MODEL', 'gpt-4o'),

    // Run the analysis directly after the upload response instead of waiting for
    // the queue (useful on shared hosting with PHP-FPM; the cron stays as fallback).
    'process_after_response' => (bool) env('LINGOLENS_PROCESS_AFTER_RESPONSE', false),

    // Cost protection: scans per student and day (0 = unlimited).
    'daily_scan_limit' => (int) env('LINGOLENS_DAILY_SCAN_LIMIT', 20),

    // There is no self-registration: this admin creates the student accounts.
    // "php artisan lingolens:install" creates the account if it does not exist;
    // the password is then set via "Passwort vergessen?" on the login page.
    'admin_email' => env('LINGOLENS_ADMIN_EMAIL'),

    // Date of the written English Matura, used for the countdown on the dashboard.
    'matura_date' => env('LINGOLENS_MATURA_DATE', '2027-05-05'),

    // Upload limit in kilobytes (10 MB) for photos, PDFs, Word and text files.
    'max_upload_kb' => 10240,

    // Texts shorter than this are treated as "Text konnte nicht gelesen werden".
    'min_ocr_characters' => 20,

    // Longer texts are rejected (about 2,000 words) to keep the AI correction focused.
    'max_text_characters' => 15000,

    // Optional task prompt (photographed or typed): shorter texts are ignored, longer ones rejected.
    'min_task_characters' => 15,
    'max_task_characters' => 5000,

    // A PDF with fewer letters in its text layer is treated as a scan and read via OCR.
    'min_text_layer_letters' => 50,

    'chemistry_department' => 'Chemieingenieurwesen',

    'departments' => [
        'Chemieingenieurwesen',
        'Maschinenbau',
        'Elektrotechnik',
        'Informationstechnologie',
        'Wirtschaftsingenieurwesen',
        'Bautechnik',
        'HAK – Wirtschaft',
        'HLW – Wirtschaftliche Berufe',
    ],

    'school_forms' => ['HTL', 'HAK', 'HLW', 'AHS'],

    'text_types' => [
        'AHS' => ['Essay', 'Article', 'Report', 'Letter to the Editor', 'Blog Entry', 'Blog Comment', 'Leaflet'],
        'BHS' => [
            'Technical Report', 'Executive Summary', 'Report', 'Article', 'Essay', 'Blog Entry', 'Blog Comment', 'Leaflet', 'CV',
            'Formal Letter', 'Email', 'Formal Email', 'Circular Email', 'Email of Application', 'Email of Motivation',
            'Email of Complaint', 'Email of Inquiry/Request', 'Email to the Editor',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Text type conventions for the "Textsorten-Check" of the AI correction
    |--------------------------------------------------------------------------
    |
    | Summarised (in our own words) from the writing reference used in class
    | (The New Best Shots 4/5, HTL/HUM). Text types without an entry are
    | checked against the general Matura conventions the model knows.
    |
    */

    'text_type_guides' => [
        'Article' => 'Einprägsamer Titel, klare Gliederung (Einleitung, Hauptteil, Schluss), Leser:innen direkt ansprechen (z.B. mit Fragen), informiert über ein Thema mit Fakten und Beispielen, der Schluss fasst die Hauptpunkte zusammen. Register je nach Zielgruppe: für Gleichaltrige (Schülerzeitung, Schulwebsite) eher informell, bei ernstem Thema für unbekanntes Publikum formell.',
        'Blog Entry' => 'Einprägsamer Titel, (User-)Name sowie Datum und Uhrzeit der Veröffentlichung, Absätze mit Einleitung, Hauptteil und Schluss. Persönlicher Ton: eigene Gedanken, Erfahrungen und Meinungen teilen; Leser:innen direkt ansprechen (z.B. \'Hi everyone!\') und am Ende zum Kommentieren auffordern. Register formell oder informell je nach Zweck und Leserschaft.',
        'Blog Comment' => '(User-)Name sowie Datum und Uhrzeit, Absätze mit Einleitung, Hauptteil und Schluss. Nimmt erkennbar Bezug auf den konkreten Blogeintrag bzw. Artikel und dessen Autor:in, vertritt eine klare Meinung mit überzeugenden Argumenten, spricht die Leser:innen direkt an und lädt am Ende zum Weiterdiskutieren ein (z.B. Dank an die Autorin bzw. den Autor, Frage an die anderen). Kein Briefformat. Register je nach Zweck und Leserschaft.',
        'Leaflet' => 'Einprägsam, interessant und informativ: auffälliger Titel, Zwischenüberschriften für jeden Abschnitt, rhetorische Fragen, konkrete Fakten (Ort, Datum, Angebot), überzeugende Sprache mit direkter Ansprache und Aufforderungen, kurze und einfache Sätze, Kontaktdaten am Ende (Website, E-Mail, Social Media). Register je nach Zielgruppe: für Gleichaltrige informeller, bei ernstem Thema für unbekanntes Publikum formell.',
        'Report' => 'Kopf mit From, To, Date und Subject; Einleitung nennt Ziel bzw. Zweck des Berichts; Absätze mit Zwischenüberschriften; Schluss mit Schlussfolgerungen bzw. Empfehlungen. Präzise, sachlich und neutral, kein persönliches \'I\', stattdessen Passivkonstruktionen; keine Kurzformen, keine Umgangssprache, keine Verallgemeinerungen. Bei Umfragen bzw. Grafiken die Zahlen korrekt und vergleichend wiedergeben.',
        'CV' => 'Abschnitte für persönliche Daten und Kontakt, Ausbildung und Qualifikationen, Berufserfahrung bzw. Praktika, relevante Fähigkeiten, Interessen und Erfolge sowie Referenzen. Formell, klar, knapp und aktuell; nur relevante Angaben, keine Kurzformen und keine Umgangssprache. Stichpunkte statt ganzer Sätze sind erwünscht.',
        'Formal Email' => 'Kopfzeile mit Absender-/Empfängeradresse, Datum und aussagekräftiger Betreffzeile, passende Anrede, nach der Anrede Großbuchstabe, Hauptteil in Absätzen, Grußformel und Name. Höflich und sachlich, keine Kurzformen, keine Umgangssprache, keine Question Tags. Grußformel: \'Yours faithfully\' bei unbekanntem Namen (\'Dear Sir or Madam\'), \'Yours sincerely\' bei bekanntem Namen (\'Dear Mr/Ms …\').',
        'Circular Email' => 'Kopfzeile mit Absender-/Empfängeradresse, Datum und aussagekräftiger Betreffzeile, passende Anrede, nach der Anrede Großbuchstabe, Hauptteil in Absätzen, Grußformel und Name. Höflich und sachlich, keine Kurzformen, keine Umgangssprache, keine Question Tags. Grußformel: \'Yours faithfully\' bei unbekanntem Namen (\'Dear Sir or Madam\'), \'Yours sincerely\' bei bekanntem Namen (\'Dear Mr/Ms …\'). Rundmail an alle Kund:innen bzw. Mitarbeiter:innen (z.B. \'Dear all\', \'Dear valued customers\'): informiert über eine Änderung oder ein wichtiges Ereignis, erklärt Zweck und Details (Termin, Kosten, Anmeldung) und fordert zur Teilnahme bzw. Reaktion auf; hier ist auch \'Kind regards\' bzw. \'Best wishes\' passend.',
        'Email of Application' => 'Kopfzeile mit Absender-/Empfängeradresse, Datum und aussagekräftiger Betreffzeile, passende Anrede, nach der Anrede Großbuchstabe, Hauptteil in Absätzen, Grußformel und Name. Höflich und sachlich, keine Kurzformen, keine Umgangssprache, keine Question Tags. Grußformel: \'Yours faithfully\' bei unbekanntem Namen (\'Dear Sir or Madam\'), \'Yours sincerely\' bei bekanntem Namen (\'Dear Mr/Ms …\'). Bewerbung: nennt die Stelle und wo sie ausgeschrieben war, fasst Ausbildung, fachliche und persönliche Fähigkeiten sowie Erfahrung kurz zusammen, überzeugt, warum man geeignet ist, verweist auf den beigelegten CV und bittet um ein Vorstellungsgespräch.',
        'Email of Motivation' => 'Kopfzeile mit Absender-/Empfängeradresse, Datum und aussagekräftiger Betreffzeile, passende Anrede, nach der Anrede Großbuchstabe, Hauptteil in Absätzen, Grußformel und Name. Höflich und sachlich, keine Kurzformen, keine Umgangssprache, keine Question Tags. Grußformel: \'Yours faithfully\' bei unbekanntem Namen (\'Dear Sir or Madam\'), \'Yours sincerely\' bei bekanntem Namen (\'Dear Mr/Ms …\'). Motivationsschreiben: erklärt ausführlich die Gründe (Motivation) für die Bewerbung um eine bestimmte Stelle, ein Praktikum, Freiwilligenarbeit oder ein Studium und verbindet sie mit passenden Fähigkeiten, Erfahrungen und Persönlichkeit, um zu überzeugen.',
        'Email of Complaint' => 'Kopfzeile mit Absender-/Empfängeradresse, Datum und aussagekräftiger Betreffzeile, passende Anrede, nach der Anrede Großbuchstabe, Hauptteil in Absätzen, Grußformel und Name. Höflich und sachlich, keine Kurzformen, keine Umgangssprache, keine Question Tags. Grußformel: \'Yours faithfully\' bei unbekanntem Namen (\'Dear Sir or Madam\'), \'Yours sincerely\' bei bekanntem Namen (\'Dear Mr/Ms …\'). Beschwerde: bleibt trotz Ärger höflich und sachlich, nennt konkret, womit man unzufrieden ist, beschreibt die entstandenen Unannehmlichkeiten und fordert eine konkrete Maßnahme (z.B. Rückerstattung, Ersatz, Verbesserung).',
        'Email of Inquiry/Request' => 'Kopfzeile mit Absender-/Empfängeradresse, Datum und aussagekräftiger Betreffzeile, passende Anrede, nach der Anrede Großbuchstabe, Hauptteil in Absätzen, Grußformel und Name. Höflich und sachlich, keine Kurzformen, keine Umgangssprache, keine Question Tags. Grußformel: \'Yours faithfully\' bei unbekanntem Namen (\'Dear Sir or Madam\'), \'Yours sincerely\' bei bekanntem Namen (\'Dear Mr/Ms …\'). Anfrage: erklärt den Grund der Anfrage und stellt die Fragen höflich und als indirekte Fragen (\'I would like to know whether …\', \'Could you please …\'); bittet um rasche Antwort.',
        'Email to the Editor' => 'Kopfzeile mit Absender-/Empfängeradresse, Datum und aussagekräftiger Betreffzeile, passende Anrede, nach der Anrede Großbuchstabe, Hauptteil in Absätzen, Grußformel und Name. Höflich und sachlich, keine Kurzformen, keine Umgangssprache, keine Question Tags. Grußformel: \'Yours faithfully\' bei unbekanntem Namen (\'Dear Sir or Madam\'), \'Yours sincerely\' bei bekanntem Namen (\'Dear Mr/Ms …\'). Leserbrief per E-Mail an Zeitung bzw. Magazin (z.B. \'Dear Editor\'): bezieht sich auf einen konkreten Artikel (Titel), vertritt eine Position dafür oder dagegen und verbindet Fakten mit persönlicher Meinung, endet mit einem Appell oder Lösungsvorschlag.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Reference matrix: chemistry vocabulary (BHS Chemieingenieurwesen)
    |--------------------------------------------------------------------------
    */

    'chemistry_terms' => [
        'distilate' => 'fractional distillation',
        'the chemical reaction makes heat' => 'exothermic reaction',
        'clean the liquid with paper' => 'vacuum filtration',
        'chemical amount' => 'molar concentration',
    ],

    /*
    |--------------------------------------------------------------------------
    | Weekly focus card on the dashboard, derived from the top error type.
    | Placeholders: :count, :total, :wrong, :correct
    |--------------------------------------------------------------------------
    */

    'focus' => [
        'Technical Term' => [
            'title' => 'Fachbegriffe statt Alltagssprache',
            'text' => ':count deiner :total Fehler sind Wörter wie „:wrong“ statt „:correct“. Übe genau diese Stellen in deinem letzten Scan.',
            'button' => 'Fachbegriffe üben',
        ],
        'Grammar' => [
            'title' => 'Grammatik sicher machen',
            'text' => ':count deiner :total Fehler sind Grammatikfehler wie „:wrong“ statt „:correct“. Übe genau diese Stellen in deinem letzten Scan.',
            'button' => 'Grammatik üben',
        ],
        'Vocabulary' => [
            'title' => 'Präziser Wortschatz',
            'text' => ':count deiner :total Fehler sind unpassende Wörter wie „:wrong“ statt „:correct“. Übe genau diese Stellen in deinem letzten Scan.',
            'button' => 'Vokabular üben',
        ],
        'Style' => [
            'title' => 'Formelles Register treffen',
            'text' => ':count deiner :total Fehler sind Stilbrüche wie „:wrong“ statt „:correct“. Übe genau diese Stellen in deinem letzten Scan.',
            'button' => 'Stil üben',
        ],
    ],

];
