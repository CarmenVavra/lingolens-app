<?php

/*
|--------------------------------------------------------------------------
| Impressum & Datenschutzerklärung
|--------------------------------------------------------------------------
|
| Operator data comes from the server's .env so personal details are not
| stored in the repository. Empty optional fields are simply not shown.
|
*/

return [

    // Medieninhaber:in / Betreiber:in (§ 5 ECG, § 25 MedienG, Art. 13 DSGVO)
    'operator' => [
        'name' => env('LEGAL_NAME'),
        'street' => env('LEGAL_STREET'),
        'city' => env('LEGAL_CITY'), // e.g. "1010 Wien"
        'country' => env('LEGAL_COUNTRY', 'Österreich'),
        'email' => env('LEGAL_EMAIL'),
        'phone' => env('LEGAL_PHONE'),
    ],

    // Only needed when LingoLens is run commercially (company, trade licence).
    'business' => [
        'purpose' => env('LEGAL_BUSINESS_PURPOSE'), // Unternehmensgegenstand
        'vat_id' => env('LEGAL_VAT_ID'), // UID-Nummer
        'register_number' => env('LEGAL_REGISTER_NUMBER'), // Firmenbuchnummer
        'register_court' => env('LEGAL_REGISTER_COURT'), // Firmenbuchgericht
        'authority' => env('LEGAL_AUTHORITY'), // Gewerbebehörde
        'chamber' => env('LEGAL_CHAMBER'), // Kammer / Berufsverband
    ],

    // Grundlegende Richtung (§ 25 Abs. 4 MedienG)
    'editorial_line' => env(
        'LEGAL_EDITORIAL_LINE',
        'Lernplattform zur Vorbereitung auf die schriftliche Englisch-Matura an AHS und BHS.'
    ),

    // Date shown as "Stand" on both pages
    'updated_at' => env('LEGAL_UPDATED_AT', '2026-10-16'),

    // Processors named in the privacy policy
    'hosting' => env('LEGAL_HOSTING', 'netcup GmbH, Karlsruhe, Deutschland'),

    'minimum_age' => 14,
];
