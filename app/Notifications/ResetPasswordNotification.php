<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * German e-mail with the link to set a new password.
 */
class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Neues Passwort für LingoLens AI')
            ->greeting('Servus!')
            ->line('Du hast angefordert, dein Passwort für LingoLens AI zurückzusetzen.')
            ->action('Neues Passwort festlegen', $url)
            ->line("Der Link ist {$minutes} Minuten gültig.")
            ->line('Wenn du das nicht warst, kannst du diese E-Mail ignorieren – dein Passwort bleibt unverändert.')
            ->salutation('Viel Erfolg bei der Matura, dein LingoLens-Team');
    }
}
