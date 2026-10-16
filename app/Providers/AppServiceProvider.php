<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Correction\CorrectionPromptBuilder;
use App\Services\Correction\DemoTextCorrector;
use App\Services\Correction\OpenAiTextCorrector;
use App\Services\Correction\TextCorrector;
use App\Services\Ocr\GoogleVisionTextRecognizer;
use App\Services\Ocr\OpenAiTextRecognizer;
use App\Services\Ocr\TextRecognizer;
use App\Support\LingoLens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // OCR is only called outside demo mode (the job uses sample texts there).
        // LINGOLENS_OCR_PROVIDER switches between OpenAI (default) and Google Cloud Vision.
        $this->app->bind(TextRecognizer::class, fn () => LingoLens::ocrProvider() === 'google'
            ? new GoogleVisionTextRecognizer(
                (string) config('services.google.vision_key'),
                (string) config('services.google.vision_endpoint'),
            )
            : new OpenAiTextRecognizer(
                (string) config('services.openai.key'),
                (string) config('lingolens.ocr_model'),
            ));

        // Real AI correction when keys are configured, sample feedback in demo mode.
        $this->app->bind(TextCorrector::class, fn ($app) => LingoLens::demoMode()
            ? new DemoTextCorrector
            : new OpenAiTextCorrector(
                (string) config('services.openai.key'),
                (string) config('lingolens.openai_model'),
                $app->make(CorrectionPromptBuilder::class),
            ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        // Generate https:// links when the app runs under an https APP_URL.
        URL::forceHttps(str_starts_with((string) config('app.url'), 'https://'));

        Carbon::setLocale('de');

        // Only admins manage the student accounts.
        Gate::define('admin', fn (User $user) => $user->isAdmin());
        Gate::define('manage-student', fn (User $user, User $student) => $user->isAdmin() && ! $student->isAdmin());
    }
}
