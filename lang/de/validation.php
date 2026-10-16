<?php

/*
|--------------------------------------------------------------------------
| German validation messages (rules used by LingoLens AI)
|--------------------------------------------------------------------------
*/

return [
    'between' => [
        'numeric' => ':Attribute muss zwischen :min und :max liegen.',
        'string' => ':Attribute muss zwischen :min und :max Zeichen lang sein.',
    ],
    'boolean' => ':Attribute muss wahr oder falsch sein.',
    'confirmed' => ':Attribute stimmt nicht mit der Bestätigung überein.',
    'email' => ':Attribute muss eine gültige E-Mail-Adresse sein.',
    'enum' => 'Der gewählte Wert für :attribute ist ungültig.',
    'file' => ':Attribute muss eine Datei sein.',
    'image' => ':Attribute muss ein Bild sein.',
    'in' => 'Der gewählte Wert für :attribute ist ungültig.',
    'integer' => ':Attribute muss eine ganze Zahl sein.',
    'lowercase' => ':Attribute darf nur Kleinbuchstaben enthalten.',
    'max' => [
        'file' => ':Attribute darf höchstens :max Kilobyte groß sein.',
        'numeric' => ':Attribute darf höchstens :max sein.',
        'string' => ':Attribute darf höchstens :max Zeichen lang sein.',
    ],
    'mimes' => ':Attribute muss vom Typ :values sein.',
    'min' => [
        'numeric' => ':Attribute muss mindestens :min sein.',
        'string' => ':Attribute muss mindestens :min Zeichen lang sein.',
    ],
    'password' => [
        'letters' => ':Attribute muss mindestens einen Buchstaben enthalten.',
        'mixed' => ':Attribute muss Groß- und Kleinbuchstaben enthalten.',
        'numbers' => ':Attribute muss mindestens eine Zahl enthalten.',
        'symbols' => ':Attribute muss mindestens ein Sonderzeichen enthalten.',
        'uncompromised' => ':Attribute ist in einem Datenleck aufgetaucht. Bitte wähle ein anderes.',
    ],
    'required' => ':Attribute muss ausgefüllt werden.',
    'required_if' => ':Attribute muss ausgefüllt werden, wenn :other :value ist.',
    'required_unless' => ':Attribute muss ausgefüllt werden.',
    'string' => ':Attribute muss ein Text sein.',
    'unique' => ':Attribute ist bereits vergeben.',
    'uploaded' => ':Attribute konnte nicht hochgeladen werden.',

    'attributes' => [],
];
