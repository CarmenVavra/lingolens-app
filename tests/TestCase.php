<?php

namespace Tests;

use App\Actions\StoreScanFeedback;
use App\Models\Scan;
use App\Models\User;
use App\Support\DemoSamples;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Never call the paid APIs with a real key from .env: on Windows an empty
        // <env> value in phpunit.xml unsets the variable, so .env would fill it again.
        // Tests that exercise the real clients set a fake key and Http::fake().
        config([
            'services.openai.key' => null,
            'services.google.vision_key' => null,
            'lingolens.demo_mode' => null,
            'lingolens.openai_model' => 'gpt-4o',
            'lingolens.ocr_model' => 'gpt-4o',
            'lingolens.ocr_provider' => 'openai',
            'lingolens.admin_email' => null,
        ]);
        Http::preventStrayRequests();
    }

    /**
     * A completed scan with the demo feedback and located corrections.
     */
    protected function completedScan(User $user, string $textType = 'Technical Report'): Scan
    {
        $scan = Scan::factory()->for($user)->completed()->create(['text_type' => $textType]);

        app(StoreScanFeedback::class)->handle($scan, DemoSamples::forTextType($textType));

        return $scan;
    }
}
