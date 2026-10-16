<?php

namespace Tests;

use App\Actions\StoreScanFeedback;
use App\Models\Scan;
use App\Models\User;
use App\Support\DemoSamples;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
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
