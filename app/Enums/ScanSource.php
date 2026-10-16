<?php

namespace App\Enums;

use App\Support\LingoLens;
use Illuminate\Http\UploadedFile;

/**
 * Where the text of a scan comes from: a photo or an uploaded document.
 */
enum ScanSource: string
{
    case Photo = 'photo';
    case Pdf = 'pdf';
    case Word = 'word';
    case Text = 'text';

    /** File extensions accepted by the upload, per source. */
    private const EXTENSIONS = [
        'photo' => ['jpg', 'jpeg', 'png', 'heic', 'heif'],
        'pdf' => ['pdf'],
        'word' => ['docx'],
        'text' => ['txt'],
    ];

    /**
     * @return list<string>
     */
    public static function allowedExtensions(): array
    {
        $extensions = array_merge(...array_values(self::EXTENSIONS));

        // OpenAI cannot read HEIC. Without it in the file picker, iPhones convert photos to JPEG.
        return LingoLens::ocrProvider() === 'google'
            ? $extensions
            : array_values(array_diff($extensions, ['heic', 'heif']));
    }

    /**
     * Photo formats for hints and error messages, e.g. "JPG, PNG".
     */
    public static function photoFormatsLabel(): string
    {
        return LingoLens::ocrProvider() === 'google' ? 'JPG, PNG, HEIC' : 'JPG, PNG';
    }

    /**
     * Determine the source from the (already validated) content type of the upload.
     */
    public static function fromUpload(UploadedFile $file): self
    {
        $extension = strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension()));

        foreach (self::EXTENSIONS as $source => $extensions) {
            if (in_array($extension, $extensions, true)) {
                return self::from($source);
            }
        }

        return self::Photo;
    }

    public function label(): string
    {
        return match ($this) {
            self::Photo => 'Foto',
            self::Pdf => 'PDF',
            self::Word => 'Word',
            self::Text => 'TXT',
        };
    }

    public function isPhoto(): bool
    {
        return $this === self::Photo;
    }

    /**
     * Second step of the processing screen: how the text is read.
     *
     * @return array{label: string, sub: string}
     */
    public function readingStep(): array
    {
        return match ($this) {
            self::Photo => ['label' => 'Handschrift erkennen', 'sub' => LingoLens::ocrLabel()],
            self::Pdf => ['label' => 'Text aus PDF lesen', 'sub' => 'Textebene oder Handschrift-Erkennung bei Scans'],
            self::Word => ['label' => 'Text aus Word-Datei lesen', 'sub' => 'Absätze und Überschriften übernehmen'],
            self::Text => ['label' => 'Textdatei lesen', 'sub' => 'Absätze übernehmen'],
        };
    }

    /**
     * Headline of the processing screen while the text is being read.
     */
    public function readingTitle(): string
    {
        return $this->isPhoto() ? 'LingoLens liest deine Handschrift …' : 'LingoLens liest deinen Text …';
    }
}
