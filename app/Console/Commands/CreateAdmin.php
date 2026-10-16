<?php

namespace App\Console\Commands;

use App\Actions\EnsureAdminAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Creates the admin account. The password is set afterwards on the login page
 * with "Passwort vergessen?" (link by e-mail), so it never appears in a file.
 */
class CreateAdmin extends Command
{
    protected $signature = 'lingolens:admin {email? : E-Mail of the admin (default: LINGOLENS_ADMIN_EMAIL)}';

    protected $description = 'Create the LingoLens AI admin account (idempotent)';

    public function handle(EnsureAdminAccount $ensureAdmin): int
    {
        $email = (string) ($this->argument('email') ?: config('lingolens.admin_email'));

        if (Validator::make(compact('email'), ['email' => ['required', 'email', 'max:255']])->fails()) {
            $this->error('Bitte eine gültige E-Mail angeben oder LINGOLENS_ADMIN_EMAIL in der .env setzen.');

            return self::FAILURE;
        }

        $admin = $ensureAdmin->handle($email);

        if ($admin->wasRecentlyCreated) {
            $this->info("Admin-Konto {$admin->email} angelegt.");
            $this->line('Passwort festlegen: Login-Seite → "Passwort vergessen?" → Link aus der E-Mail öffnen.');
        } else {
            $this->info("{$admin->email} ist Admin.");
        }

        return self::SUCCESS;
    }
}
