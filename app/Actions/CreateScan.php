<?php

namespace App\Actions;

use App\Enums\ScanSource;
use App\Enums\SchoolType;
use App\Jobs\ProcessTextScan;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Creates a scan in status "processing", stores the photo or document
 * privately and hands the heavy work (reading + AI) to the queue.
 */
class CreateScan
{
    /**
     * @param  array{school_type: string, department?: string|null, text_type: string}  $data
     */
    public function handle(User $user, array $data, ?UploadedFile $file): Scan
    {
        $schoolType = SchoolType::from($data['school_type']);

        $scan = $user->scans()->create([
            'school_type' => $schoolType,
            'department' => $schoolType === SchoolType::BHS ? ($data['department'] ?? null) : null,
            'text_type' => $data['text_type'],
            'source' => $file ? ScanSource::fromUpload($file) : ScanSource::Photo,
            'uses_sample' => $file === null,
            'file_path' => $file?->store('scans/'.$user->id, 'local'),
            'original_name' => $file ? Str::limit($file->getClientOriginalName(), 200, '') : null,
        ]);

        ProcessTextScan::dispatch($scan);

        return $scan;
    }
}
