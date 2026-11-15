<?php

/*
|--------------------------------------------------------------------------
| Bewertung nach den SRDP-Kriterien – IMMER auf Niveau B2
|--------------------------------------------------------------------------
|
| Structure from the SRDP-Bewertungsraster (version September 2023). The
| teacher wants every text assessed at B2 (Matura level), whatever level a
| worksheet mentions. The task descriptors are level-independent; the other
| three criteria are worded for B2 (summarised in our own words – replace
| them with the official B2 raster text once available).
|
| Four criteria, 0–10 points each (max. 40). Descriptors exist for the even
| levels; the odd levels (9, 7, 5, 3, 1) lie in between. LingoLens asks the AI
| for a score per criterion and then applies the two fixed rules itself:
|
| - word count more than 10 % above/below the requirement → "Erfüllung der
|   Aufgabenstellung" one level lower
| - task missed ("Aufgabenstellung verfehlt") → that criterion 0, all other
|   criteria are not assessed
|
*/

return [

    'name' => 'SRDP-Bewertung B2',

    'level' => 'B2',

    'max_per_criterion' => 10,

    // Allowed deviation from the required word count before the penalty applies.
    'word_count_tolerance' => 0.10,

    /*
    | Grade estimate (Austrian grades 1–5) from the share of points reached.
    | There is no official key for a single text – adjust to your own key.
    | A missed task ("Themenverfehlung") is always "Nicht genügend".
    */
    'grades' => [
        // minimum percentage => grade
        90 => 1,
        78 => 2,
        64 => 3,
        50 => 4,
        0 => 5,
    ],

    'grade_labels' => [
        1 => 'Sehr gut',
        2 => 'Gut',
        3 => 'Befriedigend',
        4 => 'Genügend',
        5 => 'Nicht genügend',
    ],

    'criteria' => [

        'task' => [
            'label' => 'Erfüllung der Aufgabenstellung',
            'short' => 'Aufgabe',
            'bands' => [
                10 => 'Ziel der Aufgabenstellung und Anforderungen der Textsorte vollständig erfüllt; alle Inhaltspunkte so ausführlich behandelt wie möglich, mit relevanten Details/Beispielen für alle Inhaltspunkte; Titel/Betreff/Anrede/Beginn/Einleitung/Schluss/Grußzeile sehr passend.',
                8 => 'Ziel und Anforderungen der Textsorte fast vollständig erfüllt; alle Inhaltspunkte ziemlich ausführlich behandelt, mit relevanten Details/Beispielen für fast alle Inhaltspunkte; Titel/Betreff/Anrede/Beginn/Einleitung/Schluss/Grußzeile passend.',
                6 => 'Ziel und Anforderungen der Textsorte im Wesentlichen erfüllt; alle Inhaltspunkte einigermaßen ausführlich behandelt, aber nur mit wenigen relevanten Details/Beispielen ODER zwei von drei Inhaltspunkten behandelt, jedoch mit ausreichend relevanten Details/Beispielen; Titel/Betreff/Anrede/Beginn/Einleitung/Schluss/Grußzeile einigermaßen passend.',
                4 => 'Ziel und Anforderungen der Textsorte im Wesentlichen nicht erfüllt; alle Inhaltspunkte angeführt, jedoch überwiegend irrelevante Details/Beispiele/Inhalte ODER zwei von drei Inhaltspunkten angeführt, aber wenig ausführlich behandelt, teils irrelevante Details/Beispiele/Inhalte; Titel/Betreff/Anrede/Beginn/Einleitung/Schluss/Grußzeile nicht passend/unvollständig.',
                2 => 'Ziel und Anforderungen der Textsorte nicht erfüllt; nur ein Inhaltspunkt angeführt, wenig ausführlich behandelt, oft irrelevante Details/Beispiele/Inhalte; Titel/Betreff/Anrede/Beginn/Einleitung/Schluss/Grußzeile nicht vorhanden.',
                0 => 'Aufgabenstellung verfehlt; nicht genügend Sprache für eine Beurteilung; unleserlich, daher Kommunikation nicht erfolgreich.',
            ],
        ],

        'coherence' => [
            'label' => 'Kohärenz und Kohäsion',
            'short' => 'Kohärenz',
            'bands' => [
                10 => 'Inhalte sehr klar, logisch und systematisch präsentiert; vielfältiger und treffsicherer Einsatz von Kohäsionsmitteln, auch komplexerer (z.B. however, whereas, as a result, not only … but also); durchgehend klar gegliederte, logisch aufgebaute Absätze.',
                8 => 'Inhalte klar und systematisch präsentiert; guter Einsatz verschiedener Kohäsionsmittel mit kleinen Unsicherheiten; fast durchgehend logisch aufgebaute Absätze.',
                6 => 'Inhalte einigermaßen systematisch präsentiert; Kohäsionsmittel eher einfach oder eintönig (and, but, because), aber vorwiegend passend; Text großteils in logischen Absätzen.',
                4 => 'Inhalte wenig systematisch präsentiert (mangelhafte Kohärenz); sehr eingeschränkter oder teils unpassender Einsatz von Kohäsionsmitteln (mangelhafte Kohäsion); Text mangelhaft gegliedert, Absätze wenig logisch aufgebaut.',
                2 => 'Inhalte unsystematisch präsentiert; unpassender oder fehlender Einsatz von Kohäsionsmitteln; Text nicht zufriedenstellend gegliedert.',
                0 => 'Keine Struktur erkennbar; nicht genügend Sprache für eine Beurteilung.',
            ],
        ],

        'range' => [
            'label' => 'Spektrum sprachlicher Mittel',
            'short' => 'Spektrum',
            'bands' => [
                10 => 'Breites Spektrum an lexikalischen und strukturellen Mitteln auf B2-Niveau: präziser, variantenreicher Wortschatz, passende Kollokationen und Wendungen, sichere Verwendung komplexer Satzstrukturen; kaum Wiederholungen oder Formulierungsschwierigkeiten.',
                8 => 'Gutes Spektrum, um sich klar und weitgehend präzise auszudrücken; einige komplexe Strukturen; gelegentlich Umschreibungen, Ungenauigkeiten oder Wiederholungen.',
                6 => 'Ausreichendes Spektrum, um die Aufgabe im Wesentlichen zu erfüllen, aber überwiegend einfache Strukturen und Alltagswortschatz; einige Formulierungsschwierigkeiten und/oder Wiederholungen.',
                4 => 'Eingeschränktes Spektrum, kaum über Grundwortschatz und einfache Sätze hinaus; viele Formulierungsschwierigkeiten und/oder Wiederholungen und/oder Wendungen/Sätze aus der Aufgabenstellung übernommen.',
                2 => 'Sehr begrenzte lexikalische und strukturelle Mittel, um sich verständlich auszudrücken; Text besteht großteils aus übernommenen Wendungen/Sätzen aus der Aufgabenstellung.',
                0 => 'Nicht genügend Sprache für eine Beurteilung.',
            ],
        ],

        'accuracy' => [
            'label' => 'Sprachrichtigkeit',
            'short' => 'Richtigkeit',
            'bands' => [
                10 => 'Hohe Korrektheit in Wortschatz, Grammatik und Rechtschreibung, auch bei komplexen Strukturen; vereinzelte Fehler beeinträchtigen die Verständlichkeit nicht.',
                8 => 'Gute Beherrschung von Wortschatz und Grammatik; Fehler vor allem bei komplexeren Strukturen, es bleibt klar, was ausgedrückt werden soll.',
                6 => 'Ausreichende Beherrschung; Fehler auch bei einfacheren Strukturen und im Grundwortschatz, stellenweise ist unklar, was ausgedrückt werden soll; Text insgesamt ausreichend verständlich.',
                4 => 'Mangelhafte Beherrschung; häufige elementare Fehler selbst bei einfachen Sachverhalten, oft unklar, was ausgedrückt werden soll; Text sprachlich nicht ausreichend verständlich.',
                2 => 'Systematisch elementare Fehler bei Grundwortschatz und grammatischen Strukturen; Text kaum verständlich.',
                0 => 'Nicht genügend Sprache für eine Beurteilung; Text überhaupt nicht verständlich.',
            ],
        ],

    ],

];
