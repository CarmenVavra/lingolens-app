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

    // Run the analysis directly after the upload response instead of waiting for
    // the queue (useful on shared hosting with PHP-FPM; the cron stays as fallback).
    'process_after_response' => (bool) env('LINGOLENS_PROCESS_AFTER_RESPONSE', false),

    // Cost protection: scans per student and day (0 = unlimited).
    'daily_scan_limit' => (int) env('LINGOLENS_DAILY_SCAN_LIMIT', 20),

    // If set, registration requires this access code (e.g. shared in class).
    'registration_code' => env('LINGOLENS_REGISTRATION_CODE'),

    // Date of the written English Matura, used for the countdown on the dashboard.
    'matura_date' => env('LINGOLENS_MATURA_DATE', '2027-05-05'),

    // Upload limit in kilobytes (10 MB) for photos, PDFs, Word and text files.
    'max_upload_kb' => 10240,

    // Texts shorter than this are treated as "Text konnte nicht gelesen werden".
    'min_ocr_characters' => 20,

    // Longer texts are rejected (about 2,000 words) to keep the AI correction focused.
    'max_text_characters' => 15000,

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
        'AHS' => ['Essay', 'Article', 'Report', 'Letter to the Editor', 'Blog Post'],
        'BHS' => ['Technical Report', 'Executive Summary', 'Formal Letter', 'Email', 'Essay', 'Report'],
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
