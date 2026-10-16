<?php

namespace App\Support;

/**
 * Sample texts and corrections from the design handoff.
 *
 * Used in demo mode (no API keys), for the "Beispielfoto verwenden" option
 * and by the database seeder. Markers [[key]] in the text are replaced by the
 * wrong passage of the matching correction.
 */
class DemoSamples
{
    /** Text types that get the essay sample, everything else gets the lab report. */
    private const ESSAY_LIKE = ['Essay', 'Article', 'Letter to the Editor', 'Blog Post'];

    /**
     * @return array{title: string, text: string, summary: string, textsorten_check: string, matura_tipp: string, corrections: list<array{original: string, corrected: string, type: string, explanation: string}>}
     */
    public static function forTextType(string $textType): array
    {
        return self::build(in_array($textType, self::ESSAY_LIKE, true) ? 'essay' : 'lab_report');
    }

    /**
     * @return array{title: string, text: string, summary: string, textsorten_check: string, matura_tipp: string, corrections: list<array{original: string, corrected: string, type: string, explanation: string}>}
     */
    public static function build(string $key): array
    {
        $sample = self::samples()[$key];
        $corrections = [];

        $text = preg_replace_callback('/\[\[(\w+)\]\]/', function (array $match) use ($sample, &$corrections) {
            [$wrong, $correct, $type, $explanation] = $sample['errors'][$match[1]];
            $corrections[] = ['original' => $wrong, 'corrected' => $correct, 'type' => $type, 'explanation' => $explanation];

            return $wrong;
        }, $sample['text']);

        return [
            'title' => $sample['title'],
            'text' => (string) $text,
            'summary' => $sample['summary'],
            'textsorten_check' => $sample['check'],
            'matura_tipp' => $sample['tip'],
            'corrections' => $corrections,
        ];
    }

    /**
     * @return array<string, array{title: string, text: string, errors: array<string, array{0: string, 1: string, 2: string, 3: string}>, summary: string, check: string, tip: string}>
     */
    private static function samples(): array
    {
        return [
            'lab_report' => [
                'title' => 'Lab Report: Distillation of an Ethanol–Water Mixture',
                'text' => "1  Introduction\n\nIn this report [[e1]] how we [[e2]] to separate ethanol and water by [[e3]]. The aim was to collect [[e4]] about the boiling points of both liquids.\n\n"
                    ."2  Method\n\nFirst, we mixed 50 ml of ethanol with 50 ml of water. When we added a few drops of sulfuric acid, [[e5]]. The mixture was heated [[e6]] and we had to [[e7]] the temperature very carefully. Afterwards we had to [[e8]] to remove [[e9]] solid particles.\n\n"
                    ."3  Results\n\nWe measured the [[e10]] of ethanol in the distillate. [[e11]], because we reached a purity of 89 %.",
                'errors' => [
                    'e1' => ['we describes', 'we describe', 'Grammar', 'Nach „we“ steht das Verb ohne -s. Das -s gibt es nur bei he / she / it (3. Person Singular).'],
                    'e2' => ['make an experiment', 'conducted an experiment', 'Vocabulary', '„Ein Experiment machen“ ist eine Germanismus-Falle. Im Englischen: conduct oder carry out an experiment – und Past Simple, weil es abgeschlossen ist.'],
                    'e3' => ['distilate', 'fractional distillation', 'Technical Term', 'Falsch geschrieben und unpräzise. Für die Trennung zweier Flüssigkeiten mit unterschiedlichen Siedepunkten lautet der Fachbegriff fractional distillation.'],
                    'e4' => ['informations', 'information', 'Grammar', 'information ist im Englischen unzählbar – kein Plural-s. Zum Zählen: pieces of information.'],
                    'e5' => ['the chemical reaction makes heat', 'an exothermic reaction occurred', 'Technical Term', '„makes heat“ ist Alltagssprache. Eine Reaktion, die Wärme freisetzt, heißt exothermic reaction.'],
                    'e6' => ['since 20 minutes', 'for 20 minutes', 'Grammar', 'Bei einer Zeitspanne steht for. since nur mit einem Zeitpunkt (since 9 a.m., since Monday).'],
                    'e7' => ['controll', 'monitor', 'Vocabulary', 'controll ist falsch geschrieben – und control heißt „steuern“. Für das laufende Beobachten eines Messwerts passt monitor.'],
                    'e8' => ['clean the liquid with paper', 'purify the liquid by vacuum filtration', 'Technical Term', '„Mit Papier reinigen“ ist zu ungenau. Das Laborverfahren mit Filterpapier und Unterdruck heißt vacuum filtration.'],
                    'e9' => ['a lot of', 'a considerable amount of', 'Style', 'a lot of ist umgangssprachlich. Ein Technical Report verlangt ein formelles Register: a considerable amount of, numerous.'],
                    'e10' => ['chemical amount', 'molar concentration', 'Technical Term', '„chemical amount“ ist kein Fachbegriff. Gemeint ist die Stoffmengenkonzentration – molar concentration (mol/L).'],
                    'e11' => ['I think the results are pretty good', 'The results indicate a high degree of purity', 'Style', 'Technical Reports sind objektiv: keine Ich-Form, keine Wertungen wie pretty good. Lass die Daten sprechen.'],
                ],
                'summary' => 'Dein Report ist klar gegliedert und die Methode gut nachvollziehbar. Größte Baustelle sind Fachbegriffe: An vier Stellen verwendest du Alltagssprache statt chemischer Terminologie.',
                'check' => 'Introduction, Method und Results sind vorhanden. Es fehlt ein Abschnitt „Conclusion“. Schreib den Method-Teil durchgehend im Passiv (The mixture was heated …).',
                'tip' => 'Bei Technical Reports zählt „Lexical Range“. Lerne zehn Laborverben: conduct, monitor, purify, dilute, titrate, heat, cool, filter, measure, record.',
            ],
            'essay' => [
                'title' => 'Should Smartphones Be Banned in Schools?',
                'text' => "Nowadays almost every student [[a1]] a smartphone. [[a2]] this is both a chance and a problem.\n\n"
                    ."Many teachers say that phones [[a3]] the lessons. Students are [[a4]] check messages instead of listening, and some even use the phone to [[a5]] during class.\n\n"
                    .'[[a6]], phones can be useful for research. If schools [[a7]] phones completely, students could not learn how to use this [[a8]] responsibly.',
                'errors' => [
                    'a1' => ['have', 'has', 'Grammar', 'every steht mit Singular: every student has. Das Verb bekommt das -s der 3. Person.'],
                    'a2' => ['In my opinion I think', 'In my opinion,', 'Style', 'Doppelt gemoppelt: In my opinion und I think sagen dasselbe. Entscheide dich für eine Formulierung.'],
                    'a3' => ['disturb', 'disrupt', 'Vocabulary', 'disturb heißt „belästigen“. Wenn der Unterricht unterbrochen wird, ist disrupt das präzisere Wort.'],
                    'a4' => ['gonna', 'likely to', 'Style', 'gonna ist gesprochene Sprache und hat im Essay nichts verloren. Formell: are likely to / tend to.'],
                    'a5' => ['make homeworks', 'do homework', 'Vocabulary', 'Hausübungen „macht“ man mit do – und homework ist unzählbar, also ohne -s.'],
                    'a6' => ['On the other hand side', 'On the other hand', 'Vocabulary', 'Mischung aus „on the other hand“ und „auf der anderen Seite“. Die feste Wendung lautet On the other hand.'],
                    'a7' => ['would ban', 'banned', 'Grammar', 'Im if-Satz (2nd conditional) steht kein would: If schools banned …, students could not …'],
                    'a8' => ['stuff', 'technology', 'Style', 'stuff ist sehr umgangssprachlich. Nenne konkret, worum es geht: technology, these devices.'],
                ],
                'summary' => 'Deine Argumentation ist ausgewogen, du nennst Pro und Contra. Achte auf feste Wendungen und vermeide gesprochene Sprache wie gonna oder stuff.',
                'check' => 'Für ein Essay fehlen eine klare These in der Einleitung und eine Conclusion. Gliedere in Einleitung, zwei Hauptabsätze und Schluss.',
                'tip' => 'Die Matura-Aufgabe nennt meist drei Inhaltspunkte. Hake sie vor der Abgabe einzeln ab – fehlende Punkte kosten im Kriterium „Task Achievement“.',
            ],
        ];
    }
}
