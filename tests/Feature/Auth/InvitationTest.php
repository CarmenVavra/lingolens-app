<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\StudentInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin creates the student; returns the token from the invitation e-mail.
     */
    private function inviteLena(): string
    {
        Notification::fake();

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.students.store'), [
            'name' => 'Lena Gruber',
            'email' => 'lena@example.com',
            'school_type' => 'AHS',
        ]);
        auth()->logout();

        $token = '';
        Notification::assertSentTo(User::firstWhere('email', 'lena@example.com'), StudentInvitationNotification::class,
            function (StudentInvitationNotification $notification) use (&$token) {
                $token = $notification->token;

                return true;
            });

        return $token;
    }

    /**
     * @return array<string, string>
     */
    private function acceptData(string $token, bool $privacy = true): array
    {
        return [
            'token' => $token,
            'email' => 'lena@example.com',
            'password' => 'mein-neues-pw',
            'password_confirmation' => 'mein-neues-pw',
        ] + ($privacy ? ['privacy' => '1'] : []);
    }

    public function test_invited_students_cannot_log_in_before_accepting(): void
    {
        $this->inviteLena();

        $this->post(route('login'), ['email' => 'lena@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_students_accept_the_invitation_and_are_logged_in(): void
    {
        $token = $this->inviteLena();

        $this->get(route('invitation.show', ['token' => $token, 'email' => 'lena@example.com']))
            ->assertOk()
            ->assertSee('Willkommen bei LingoLens AI')
            ->assertSee('value="lena@example.com"', false)
            ->assertSee('Datenschutzerklärung</a>', false);

        $this->post(route('invitation.store'), $this->acceptData($token, privacy: false))
            ->assertSessionHasErrors(['privacy' => 'Bitte bestätige, dass du die Datenschutzerklärung gelesen hast.']);
        $this->assertGuest();

        $this->post(route('invitation.store'), $this->acceptData($token))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', 'Willkommen bei LingoLens AI! Dein Passwort ist gespeichert.');

        $student = User::firstWhere('email', 'lena@example.com');
        $this->assertAuthenticatedAs($student);
        $this->assertTrue($student->hasAcceptedInvitation());
        $this->assertTrue(Hash::check('mein-neues-pw', $student->password));

        // The link works only once.
        auth()->logout();
        $this->post(route('invitation.store'), $this->acceptData($token))->assertSessionHasErrors('email');
    }

    public function test_an_invalid_link_shows_a_helpful_message(): void
    {
        $this->inviteLena();

        $this->post(route('invitation.store'), $this->acceptData('falsch'))
            ->assertSessionHasErrors(['email' => 'Der Einladungslink ist ungültig oder abgelaufen. Bitte die Administration, dir eine neue Einladung zu schicken.']);
        $this->assertGuest();
    }

    public function test_the_invitation_is_valid_for_seven_days(): void
    {
        $token = $this->inviteLena();

        $this->travel(8)->days();
        $this->post(route('invitation.store'), $this->acceptData($token))->assertSessionHasErrors('email');
        $this->travelBack();

        // A new invitation (resending is possible after one minute).
        $this->travel(2)->minutes();
        $token = $this->inviteLenaAgain();
        $this->travel(6)->days();
        $this->post(route('invitation.store'), $this->acceptData($token))->assertRedirect(route('dashboard'));
    }

    public function test_password_reset_tokens_do_not_work_as_invitations(): void
    {
        $this->inviteLena();
        $resetToken = Password::broker()->createToken(User::firstWhere('email', 'lena@example.com'));

        $this->post(route('invitation.store'), $this->acceptData($resetToken))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    private function inviteLenaAgain(): string
    {
        Notification::fake();
        $student = User::firstWhere('email', 'lena@example.com');

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.students.invite', $student));
        auth()->logout();

        $token = '';
        Notification::assertSentTo($student, StudentInvitationNotification::class,
            function (StudentInvitationNotification $notification) use (&$token) {
                $token = $notification->token;

                return true;
            });

        return $token;
    }
}
