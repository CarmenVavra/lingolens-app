<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The admin corrects a student's name, e-mail address or school profile.
 */
class UpdateStudent
{
    /**
     * @param  array<string, mixed>  $profile  Validated data from UpdateStudentRequest::profile()
     * @return bool Whether the e-mail address changed
     */
    public function handle(User $student, array $profile): bool
    {
        $oldEmail = $student->email;

        return DB::transaction(function () use ($student, $profile, $oldEmail) {
            $student->update($profile);

            if ($student->email === $oldEmail) {
                return false;
            }

            // Links sent to the old address must stop working.
            DB::table('password_reset_tokens')->where('email', $oldEmail)->delete();
            DB::table('invitation_tokens')->where('email', $oldEmail)->delete();

            return true;
        });
    }
}
