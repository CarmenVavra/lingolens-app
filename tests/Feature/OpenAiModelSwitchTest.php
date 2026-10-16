<?php

namespace Tests\Feature;

use App\Models\Scan;
use App\Services\Correction\OpenAiTextCorrector;
use App\Services\Correction\TextCorrector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiModelSwitchTest extends TestCase
{
    use RefreshDatabase;

    private function correctWith(string $model): void
    {
        config([
            'services.google.vision_key' => 'test-vision-key',
            'services.openai.key' => 'test-openai-key',
            'lingolens.openai_model' => $model,
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode([
                'title' => 'Report',
                'summary' => 'Gut.',
                'textsorten_check' => 'Passt.',
                'matura_tipp' => 'Weiter so.',
                'corrections' => [],
            ])]]]]),
        ]);

        $result = app(TextCorrector::class)->correct(Scan::factory()->create(), 'We was in the lab.');

        $this->assertSame('Report', $result['title']);
    }

    public function test_classic_models_get_a_low_temperature(): void
    {
        $this->correctWith('gpt-4o');

        Http::assertSent(fn (Request $request) => $request['model'] === 'gpt-4o'
            && $request['temperature'] === 0.2
            && $request['response_format'] === ['type' => 'json_object']);
    }

    public function test_newer_models_are_called_without_temperature(): void
    {
        $this->correctWith('gpt-5-mini');

        Http::assertSent(fn (Request $request) => $request['model'] === 'gpt-5-mini'
            && ! array_key_exists('temperature', $request->data())
            && $request['response_format'] === ['type' => 'json_object']);
    }

    public function test_temperature_support_is_detected_by_model_name(): void
    {
        foreach (['gpt-4o', 'gpt-4o-mini', 'gpt-4.1', 'gpt-4.1-mini', 'GPT-4o', 'gpt-3.5-turbo'] as $model) {
            $this->assertTrue(OpenAiTextCorrector::supportsTemperature($model), $model);
        }

        foreach (['gpt-5', 'gpt-5-mini', 'gpt-6-luna', 'o3', 'o4-mini'] as $model) {
            $this->assertFalse(OpenAiTextCorrector::supportsTemperature($model), $model);
        }
    }
}
