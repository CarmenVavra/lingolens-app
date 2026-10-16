<?php

namespace App\Services\Correction;

use App\Exceptions\ScanProcessingException;
use App\Models\Scan;
use Illuminate\Support\Facades\Http;

/**
 * AI correction via the OpenAI Chat Completions API (JSON mode).
 */
class OpenAiTextCorrector implements TextCorrector
{
    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly CorrectionPromptBuilder $prompts,
    ) {}

    public function correct(Scan $scan, string $text): array
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(120)
            ->retry(2, 1000, throw: false)
            ->post(self::ENDPOINT, [
                'model' => $this->model,
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.2,
                'messages' => [
                    ['role' => 'system', 'content' => $this->prompts->build($scan)],
                    ['role' => 'user', 'content' => $text],
                ],
            ])
            ->throw();

        $data = json_decode((string) $response->json('choices.0.message.content'), true);

        if (! is_array($data)) {
            throw new ScanProcessingException('Die KI-Antwort war kein gültiges JSON.');
        }

        return self::normalize($data);
    }

    /**
     * Validate and normalise the AI JSON so only well-formed data is stored.
     *
     * @param  array<mixed>  $data
     * @return array{title: string, summary: string, textsorten_check: string, matura_tipp: string, corrections: list<array{original: string, corrected: string, type: string, explanation: string}>}
     */
    public static function normalize(array $data): array
    {
        $corrections = [];

        foreach (is_array($data['corrections'] ?? null) ? $data['corrections'] : [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $original = trim((string) ($item['original'] ?? ''));
            $corrected = trim((string) ($item['corrected'] ?? ''));

            if ($original === '' || $corrected === '' || mb_strlen($original) > 255 || mb_strlen($corrected) > 255) {
                continue;
            }

            $corrections[] = [
                'original' => $original,
                'corrected' => $corrected,
                'type' => (string) ($item['type'] ?? ''),
                'explanation' => trim((string) ($item['explanation'] ?? '')),
            ];
        }

        return [
            'title' => mb_substr(trim((string) ($data['title'] ?? '')), 0, 255),
            'summary' => trim((string) ($data['summary'] ?? '')),
            'textsorten_check' => trim((string) ($data['textsorten_check'] ?? '')),
            'matura_tipp' => trim((string) ($data['matura_tipp'] ?? '')),
            'corrections' => $corrections,
        ];
    }
}
