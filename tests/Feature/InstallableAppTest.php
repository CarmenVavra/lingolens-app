<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LingoLens can be installed on phones as a standalone app (WebAPK on Android,
 * home screen app on iOS) – without the Chrome badge of a plain bookmark.
 */
class InstallableAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_layout_links_the_manifest_and_the_app_icon(): void
    {
        $pages = [
            $this->get(route('login')),
            $this->get(route('legal.privacy')),
            $this->actingAs(User::factory()->create())->get(route('dashboard')),
        ];

        foreach ($pages as $page) {
            $page->assertOk()
                ->assertSee('<link rel="manifest" href="'.asset('manifest.webmanifest').'">', false)
                ->assertSee('<link rel="apple-touch-icon" href="'.asset('icons/apple-touch-icon.png').'">', false)
                ->assertSee('<meta name="theme-color"', false);
        }
    }

    public function test_the_manifest_meets_the_install_criteria(): void
    {
        $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('LingoLens AI', $manifest['name']);
        $this->assertSame('LingoLens', $manifest['short_name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/dashboard', $manifest['start_url']);
        $this->assertTrue(app('router')->has('dashboard'));
        $this->assertSame('/', $manifest['scope']);

        $icons = collect($manifest['icons']);
        foreach (['192x192' => 'any', '512x512' => 'any'] as $sizes => $purpose) {
            $this->assertTrue($icons->contains(fn (array $icon) => $icon['sizes'] === $sizes && $icon['purpose'] === $purpose), "Icon {$sizes} fehlt.");
        }
        $this->assertTrue($icons->contains('purpose', 'maskable'), 'Android needs a maskable icon for a full-size launcher icon.');

        foreach ($icons as $icon) {
            $path = public_path(ltrim($icon['src'], '/'));
            $this->assertFileExists($path);
            [$width, $height, $type] = getimagesize($path) ?: [0, 0, 0];
            $this->assertSame($icon['sizes'], "{$width}x{$height}", "{$icon['src']} hat die falsche Größe.");
            $this->assertSame(IMAGETYPE_PNG, $type);
        }

        [$width] = getimagesize(public_path('icons/apple-touch-icon.png')) ?: [0];
        $this->assertSame(180, $width);
    }

    public function test_the_service_worker_only_caches_the_offline_page(): void
    {
        $worker = (string) file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("addEventListener('fetch'", $worker);
        $this->assertStringContainsString("const OFFLINE_URL = '/offline.html'", $worker);
        $this->assertStringContainsString("event.request.mode !== 'navigate'", $worker);
        $this->assertStringNotContainsString('cache.put', $worker, 'Personal pages must never land in the cache.');

        $this->assertFileExists(public_path('offline.html'));
        $this->assertStringContainsString('Keine Internetverbindung', (string) file_get_contents(public_path('offline.html')));
    }
}
