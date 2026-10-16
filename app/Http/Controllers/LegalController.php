<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Public legal pages: Impressum and Datenschutzerklärung.
 */
class LegalController extends Controller
{
    public function imprint(): View
    {
        $legal = config('legal');
        $updatedAt = $this->updatedAt();

        return view('legal.imprint', compact('legal', 'updatedAt'));
    }

    public function privacy(): View
    {
        $legal = config('legal');
        $updatedAt = $this->updatedAt();
        $uploadLimitMb = (int) round(config('lingolens.max_upload_kb') / 1024);
        $sessionHours = (int) round(config('session.lifetime') / 60);
        $logDays = (int) config('logging.channels.daily.days');
        $visionInEu = str_contains((string) config('services.google.vision_endpoint'), 'eu-vision');

        return view('legal.privacy', compact('legal', 'updatedAt', 'uploadLimitMb', 'sessionHours', 'logDays', 'visionInEu'));
    }

    private function updatedAt(): string
    {
        return Carbon::parse((string) config('legal.updated_at'))->locale('de')->isoFormat('D. MMMM YYYY');
    }
}
