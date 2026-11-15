<?php

namespace App\Services\Correction;

use App\Enums\TaskFit;
use App\Exceptions\ScanProcessingException;
use App\Models\Scan;
use App\Services\Rubric\SrdpRubric;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * AI correction via the OpenAI Chat Completions API (JSON mode), followed by
 * a separate call that scores the text along the SRDP criteria.
 */
class OpenAiTextCorrector implements TextCorrector
{
    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly CorrectionPromptBuilder $prompts,
        private readonly SrdpRubric $rubric,
    ) {}

    public function correct(Scan $scan, string $text): array
    {
        $data = $this->chat($this->prompts->build($scan), $text, 0.2);

        if ($data === null) {
            throw new ScanProcessingException('Die KI-Antwort war kein gültiges JSON.');
        }

        $result = self::normalize($data);
        $result['rubric'] = $this->assess($scan, $text, $result['corrections']);

        return $result;
    }

    /**
     * Second call: SRDP scores with the corrections as evidence. A failure here
     * never costs the student the correction – the scores are simply missing.
     *
     * @param  list<array{original: string, corrected: string, type: string, explanation: string}>  $corrections
     * @return array<string, array{score: int|null, reason: string}>|null
     */
    private function assess(Scan $scan, string $text, array $corrections): ?array
    {
        try {
            return SrdpRubric::normalize($this->chat($this->rubric->assessmentPrompt($scan, $text, $corrections), $text, 0.0));
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * @return array<mixed>|null The decoded JSON answer
     */
    private function chat(string $system, string $user, float $temperature): ?array
    {
        $payload = [
            'model' => $this->model,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
        ];

        if (self::supportsTemperature($this->model)) {
            $payload['temperature'] = $temperature;
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(120)
            ->retry(2, 1000, throw: false)
            ->post(self::ENDPOINT, $payload)
            ->throw();

        $data = json_decode((string) $response->json('choices.0.message.content'), true);

        return is_array($data) ? $data : null;
    }

    /**
     * Only the classic GPT-4 / GPT-3.5 models accept a custom temperature.
     * Reasoning models (o-series, GPT-5 and later) reject the parameter with
     * a 400 error, so OPENAI_MODEL can be switched without code changes.
     */
    public static function supportsTemperature(string $model): bool
    {
        return (bool) preg_match('/^(gpt-4|gpt-3\.5|chatgpt-4o)/i', trim($model));
    }

    /**
     * Validate and normalise the AI JSON so only well-formed data is stored.
     *
     * @param  array<mixed>  $data
     * @return array{title: string, summary: string, textsorten_check: string, matura_tipp: string, corrections: list<array{original: string, corrected: string, type: string, explanation: string}>, task_check: array{fit: string, feedback: string, tips: list<array{tip: string, example: string}>}|null, rubric: array<string, array{score: int|null, reason: string}>|null}
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
            'task_check' => self::normalizeTaskCheck($data['aufgaben_check'] ?? null),
            'rubric' => null,
        ];
    }

    /**
     * Verdict on the task prompt – only present when the student added one.
     *
     * @return array{fit: string, feedback: string, tips: list<array{tip: string, example: string}>}|null
     */
    private static function normalizeTaskCheck(mixed $check): ?array
    {
        if (! is_array($check)) {
            return null;
        }

        $tips = collect(is_array($check['tipps'] ?? null) ? $check['tipps'] : [])
            ->map(fn (mixed $tip) => is_array($tip)
                ? ['tip' => (string) ($tip['tipp'] ?? ''), 'example' => (string) ($tip['beispiel'] ?? '')]
                : ['tip' => is_string($tip) ? $tip : '', 'example' => ''])
            ->map(fn (array $tip) => ['tip' => mb_substr(trim($tip['tip']), 0, 500), 'example' => mb_substr(trim($tip['example']), 0, 600)])
            ->filter(fn (array $tip) => $tip['tip'] !== '')
            ->take(5)
            ->values()
            ->all();

        return [
            'fit' => TaskFit::fromAi((string) ($check['bewertung'] ?? ''))->value,
            'feedback' => trim((string) ($check['begruendung'] ?? '')),
            'tips' => $tips,
        ];
    }
}
