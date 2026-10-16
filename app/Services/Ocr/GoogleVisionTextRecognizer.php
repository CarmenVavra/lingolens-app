<?php

namespace App\Services\Ocr;

use App\Models\Scan;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * OCR via Google Cloud Vision (DOCUMENT_TEXT_DETECTION handles handwriting).
 */
class GoogleVisionTextRecognizer implements TextRecognizer
{
    private const IMAGE_ENDPOINT = 'https://vision.googleapis.com/v1/images:annotate';

    /** Synchronous file annotation; Vision reads the first five pages of a PDF. */
    private const FILE_ENDPOINT = 'https://vision.googleapis.com/v1/files:annotate';

    public function __construct(private readonly string $apiKey) {}

    public function recognizeImage(Scan $scan, string $imageContents): string
    {
        $response = $this->post(self::IMAGE_ENDPOINT, [
            'image' => ['content' => base64_encode($imageContents)],
            'features' => [['type' => 'DOCUMENT_TEXT_DETECTION']],
            'imageContext' => ['languageHints' => ['en']],
        ]);

        return trim((string) $response->json('responses.0.fullTextAnnotation.text', ''));
    }

    public function recognizePdf(Scan $scan, string $pdfContents): string
    {
        $response = $this->post(self::FILE_ENDPOINT, [
            'inputConfig' => ['content' => base64_encode($pdfContents), 'mimeType' => 'application/pdf'],
            'features' => [['type' => 'DOCUMENT_TEXT_DETECTION']],
            'imageContext' => ['languageHints' => ['en']],
        ]);

        return $response->collect('responses.0.responses')
            ->map(fn (mixed $page) => trim((string) data_get($page, 'fullTextAnnotation.text', '')))
            ->filter()
            ->implode("\n\n");
    }

    /**
     * @param  array<string, mixed>  $request
     */
    private function post(string $endpoint, array $request): Response
    {
        return Http::timeout(90)
            ->retry(2, 500, throw: false)
            ->withQueryParameters(['key' => $this->apiKey])
            ->post($endpoint, ['requests' => [$request]])
            ->throw();
    }
}
