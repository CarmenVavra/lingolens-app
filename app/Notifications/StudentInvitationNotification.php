<?php

namespace App\Notifications;

use App\Models\User;
use App\Support\LingoLens;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * German invitation e-mail: the student opens the link and sets a password.
 */
class StudentInvitationNotification extends Notification
{
    public function __construct(public readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $days = LingoLens::invitationDays();
        $validity = $days === 1 ? '1 Tag' : "{$days} Tage";

        $url = route('invitation.show', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Einladung zu LingoLens AI')
            ->greeting("Servus {$notifiable->firstName()}!")
            ->line('Für dich wurde ein Konto bei LingoLens AI angelegt – deinem Coach für die Englisch-Matura.')
            ->line('Fotografiere oder lade deine englischen Texte hoch: LingoLens korrigiert sie und erklärt dir jeden Fehler.')
            ->action('Passwort festlegen', $url)
            ->line("Der Link ist {$validity} gültig. Danach kannst du um eine neue Einladung bitten.")
            ->salutation('Viel Erfolg bei der Matura, dein LingoLens-Team');
    }
}
