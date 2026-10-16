<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Creates the admin account (or promotes an existing account with that e-mail).
 *
 * New admins get a random password that nobody knows; the admin sets the real
 * one via "Passwort vergessen?". So no password ever has to be stored in a
 * file, the .env or a scheduled task.
 */
class EnsureAdminAccount
{
    public function handle(string $email): User
    {
        $email = Str::lower(trim($email));

        $admin = User::firstOrNew(['email' => $email]);

        if (! $admin->exists) {
            $admin->fill([
                'name' => 'Administration',
                'password' => Str::random(64),
                'school_form' => null,
            ]);
            $admin->forceFill(['privacy_accepted_at' => now()]);
        }

        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }
}
