<?php

/*
|--------------------------------------------------------------------------
| SRDP-Bewertungsraster B1 (Version September 2023, ab SRDP-Herbsttermin 2024)
|--------------------------------------------------------------------------
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

    'name' => 'SRDP-Bewertungsraster B1',

    'max_per_criterion' => 10,

    // Allowed deviation from the required word count before the penalty applies.
    'word_count_tolerance' => 0.10,

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
                10 => 'Inhalte sind sehr klar und systematisch präsentiert (Kohärenz); durchgehend guter Einsatz von häufigen Kohäsionsmitteln (Kohäsion); Text besteht durchgehend aus einfachen, logischen Absätzen.',
                8 => 'Inhalte sind klar und systematisch präsentiert; fast durchgehend guter Einsatz von häufigen Kohäsionsmitteln; Text besteht fast durchgehend aus einfachen, logischen Absätzen.',
                6 => 'Inhalte sind einigermaßen systematisch präsentiert; eingeschränkter, aber vorwiegend passender Einsatz von häufigen Kohäsionsmitteln; Text besteht großteils aus einfachen, logischen Absätzen.',
                4 => 'Inhalte sind wenig systematisch präsentiert (mangelhafte Kohärenz); sehr eingeschränkter oder zum Teil unpassender Einsatz von häufigen Kohäsionsmitteln (mangelhafte Kohäsion); Text ist mangelhaft gegliedert, Absätze sind wenig logisch aufgebaut.',
                2 => 'Inhalte sind unsystematisch präsentiert; unpassender oder fehlender Einsatz von Kohäsionsmitteln; Text ist nicht zufriedenstellend gegliedert.',
                0 => 'Keine Struktur erkennbar; nicht genügend Sprache für eine Beurteilung.',
            ],
        ],

        'range' => [
            'label' => 'Spektrum sprachlicher Mittel',
            'short' => 'Spektrum',
            'bands' => [
                10 => 'Hinreichend breites Spektrum an lexikalischen und strukturellen Mitteln, um die Aufgabenstellung zu erfüllen; kaum Formulierungsschwierigkeiten und/oder Wiederholungen, die auf begrenzte sprachliche Mittel hinweisen.',
                8 => 'Ausreichendes Spektrum an lexikalischen und strukturellen Mitteln, um die Aufgabenstellung mit eingeschränkter Genauigkeit zu erfüllen; gelegentlich Formulierungsschwierigkeiten und/oder Wiederholungen, die auf begrenzte sprachliche Mittel hinweisen.',
                6 => 'Genügend lexikalische und strukturelle Mittel, um die Aufgabenstellung im Wesentlichen zu erfüllen; einige Formulierungsschwierigkeiten und/oder Wiederholungen aufgrund eingeschränkter sprachlicher Mittel.',
                4 => 'Nicht genügend lexikalische und strukturelle Mittel, um die Aufgabenstellung im Wesentlichen zu erfüllen; viele Formulierungsschwierigkeiten und/oder Wiederholungen aufgrund eingeschränkter sprachlicher Mittel und/oder Wendungen/Sätze aus der Aufgabenstellung entnommen.',
                2 => 'Nicht genügend lexikalische und strukturelle Mittel, um sich verständlich ausdrücken zu können; Text besteht großteils aus übernommenen Wendungen/Sätzen aus der Aufgabenstellung.',
                0 => 'Nicht genügend Sprache für eine Beurteilung.',
            ],
        ],

        'accuracy' => [
            'label' => 'Sprachrichtigkeit',
            'short' => 'Richtigkeit',
            'bands' => [
                10 => 'Sehr gute Beherrschung des Grundwortschatzes sowie häufiger Wendungen; sehr gute Beherrschung der grammatischen Strukturen, zwar kommen Fehler vor, aber es bleibt klar, was ausgedrückt werden soll; Text ist sprachlich durchgehend gut verständlich.',
                8 => 'Gute Beherrschung des Grundwortschatzes sowie häufiger Wendungen, Fehler kommen dann vor, wenn es darum geht, komplexere Sachverhalte auszudrücken; gute Beherrschung der grammatischen Strukturen, es bleibt großteils klar, was ausgedrückt werden soll; Text ist sprachlich meist gut verständlich.',
                6 => 'Ausreichend gute Beherrschung des Grundwortschatzes sowie häufiger Wendungen, aber elementare Fehler bei komplexeren Sachverhalten; ausreichend gute Beherrschung der grammatischen Strukturen, noch Fehler, durch die teilweise unklar ist, was ausgedrückt werden soll; Text ist sprachlich ausreichend gut verständlich.',
                4 => 'Mangelhafte Beherrschung des Grundwortschatzes, elementare Fehler selbst beim Formulieren einfacher Sachverhalte; mangelhafte Beherrschung der grammatischen Strukturen, häufig unklar, was ausgedrückt werden soll; Text ist sprachlich nicht ausreichend verständlich.',
                2 => 'Systematisch elementare Fehler bei der Verwendung des Grundwortschatzes und grammatischer Strukturen; Text ist kaum verständlich.',
                0 => 'Nicht genügend Sprache für eine Beurteilung; Text ist überhaupt nicht verständlich.',
            ],
        ],

    ],

];
