<?php

namespace App\Actions;

use App\Models\User;
use App\Notifications\StudentInvitationNotification;
use Illuminate\Support\Facades\Password;

/**
 * Sends (or re-sends) the invitation link with which a student sets the password.
 */
class InviteStudent
{
    /**
     * @return string Password broker status (RESET_LINK_SENT or RESET_THROTTLED)
     */
    public function handle(User $student): string
    {
        return Password::broker('invitations')->sendResetLink(
            ['email' => $student->email],
            function (User $user, string $token): void {
                $user->notify(new StudentInvitationNotification($token));
            },
        );
    }
}
