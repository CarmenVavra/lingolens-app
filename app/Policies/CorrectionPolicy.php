<?php

namespace App\Policies;

use App\Models\Correction;
use App\Models\User;

class CorrectionPolicy
{
    /**
     * A correction may only be marked as understood by the owner of its scan.
     */
    public function update(User $user, Correction $correction): bool
    {
        $correction->loadMissing('feedback.scan');

        return $correction->feedback->scan->user_id === $user->id;
    }
}
