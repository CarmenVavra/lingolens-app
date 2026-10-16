<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes a student account with all texts, corrections and open links
 * (right to erasure, Art. 17 GDPR).
 */
class DeleteStudent
{
    public function handle(User $student): void
    {
        DB::transaction(function () use ($student) {
            // Uploads are normally deleted right after the analysis; remove leftovers.
            $student->scans()->whereNotNull('file_path')->pluck('file_path')
                ->each(fn (string $path) => Storage::disk('local')->delete($path));

            DB::table('password_reset_tokens')->where('email', $student->email)->delete();
            DB::table('invitation_tokens')->where('email', $student->email)->delete();

            // Scans, feedback and corrections are removed by the cascading foreign keys.
            $student->delete();
        });
    }
}
