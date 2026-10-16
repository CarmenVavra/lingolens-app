<?php

namespace App\Providers;

use App\Services\Correction\CorrectionPromptBuilder;
use App\Services\Correction\DemoTextCorrector;
use App\Services\Correction\OpenAiTextCorrector;
use App\Services\Correction\TextCorrector;
use App\Services\Ocr\GoogleVisionTextRecognizer;
use App\Services\Ocr\TextRecognizer;
use App\Support\LingoLens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // OCR is only called outside demo mode (the job uses sample texts there).
        $this->app->bind(TextRecognizer::class, fn () => new GoogleVisionTextRecognizer(
            (string) config('services.google.vision_key')
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

        Carbon::setLocale('de');
    }
}
