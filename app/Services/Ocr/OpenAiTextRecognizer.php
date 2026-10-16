<?php

namespace App\Services\Ocr;

use App\Models\Scan;
use App\Services\Correction\OpenAiTextCorrector;
use Illuminate\Support\Facades\Http;

/**
 * Handwriting recognition with an OpenAI vision model (Chat Completions).
 *
 * Reads cursive handwriting far better than classic OCR, keeps the reading
 * order and ignores everything around the sheet (desk, screen, browser).
 * The model is told to transcribe verbatim, so the student's mistakes stay
 * in the text and can be corrected afterwards.
 */
class OpenAiTextRecognizer implements TextRecognizer
{
    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    private const PROMPT = 'Du bist eine Texterkennung (OCR) für handgeschriebene oder gedruckte englische Schülertexte.'
        .' Transkribiere den Text des Schülers exakt so, wie er geschrieben wurde – Zeichen für Zeichen.'
        .' Korrigiere NICHTS: Rechtschreib-, Grammatik- und Zeichensetzungsfehler müssen unverändert erhalten bleiben,'
        .' auch wenn du das richtige Wort erkennst.'
        .' Lass weg: vom Schüler durchgestrichene Wörter, Korrekturen in anderer Farbe (z.B. von der Lehrkraft),'
        .' aufgedruckte Texte des Blattes und alles außerhalb des Blattes (z.B. Bildschirm, Browser, Tisch).'
        .' Behalte die Zeilen des Originals bei und trenne Absätze mit einer Leerzeile.'
        .' Antworte nur mit dem transkribierten Text, ohne Kommentar und ohne Formatierung.'
        .' Ist kein Schülertext erkennbar, antworte mit einer leeren Nachricht.';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    public function recognizeImage(Scan $scan, string $imageContents): string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($imageContents) ?: 'image/jpeg';

        return $this->transcribe([
            'type' => 'image_url',
            'image_url' => ['url' => "data:{$mime};base64,".base64_encode($imageContents), 'detail' => 'high'],
        ]);
    }

    public function recognizePdf(Scan $scan, string $pdfContents): string
    {
        return $this->transcribe([
            'type' => 'file',
            'file' => ['filename' => 'scan.pdf', 'file_data' => 'data:application/pdf;base64,'.base64_encode($pdfContents)],
        ]);
    }

    /**
     * @param  array<string, mixed>  $content  Image or file part of the user message
     */
    private function transcribe(array $content): string
    {
        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => self::PROMPT],
                ['role' => 'user', 'content' => [$content]],
            ],
        ];

        if (OpenAiTextCorrector::supportsTemperature($this->model)) {
            $payload['temperature'] = 0;
        }

        $text = (string) Http::withToken($this->apiKey)
            ->timeout(120)
            ->retry(2, 1000, throw: false)
            ->post(self::ENDPOINT, $payload)
            ->throw()
            ->json('choices.0.message.content', '');

        // Some models wrap the answer in a code block despite the instruction.
        return trim((string) preg_replace('/^```[a-z]*\s*|\s*```$/i', '', trim($text)));
    }
}
