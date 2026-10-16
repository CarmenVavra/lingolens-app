<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    private const NEUTRAL_STATUS = 'Wenn ein Konto mit dieser E-Mail-Adresse existiert, haben wir dir einen Link zum Zurücksetzen geschickt. Er ist 60 Minuten gültig.';

    public function test_account_page_shows_profile_and_password_form(): void
    {
        $user = User::factory()->create(['name' => 'Lena Gruber', 'email' => 'lena@example.com']);

        $this->actingAs($user)
            ->get(route('account.show'))
            ->assertOk()
            ->assertSee('Mein Konto')
            ->assertSee('lena@example.com')
            ->assertSee('Passwort ändern')
            ->assertSee('name="_token"', false);
    }

    public function test_students_can_change_their_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('account.password'), [
                'current_password' => 'password',
                'password' => 'neues-geheimes-pw',
                'password_confirmation' => 'neues-geheimes-pw',
            ])
            ->assertRedirect(route('account.show'))
            ->assertSessionHas('status', 'Dein Passwort wurde geändert.');

        $this->assertTrue(Hash::check('neues-geheimes-pw', $user->refresh()->password));

        $this->actingAs($user)
            ->followingRedirects()
            ->put(route('account.password'), [
                'current_password' => 'neues-geheimes-pw',
                'password' => 'noch-ein-neues-pw',
                'password_confirmation' => 'noch-ein-neues-pw',
            ])
            ->assertSee('Dein Passwort wurde geändert.');
    }

    public function test_the_current_password_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('account.password'), [
                'current_password' => 'falsch',
                'password' => 'neues-geheimes-pw',
                'password_confirmation' => 'neues-geheimes-pw',
            ])
            ->assertSessionHasErrorsIn('updatePassword', ['current_password' => 'Das aktuelle Passwort ist nicht korrekt.']);

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_the_new_password_must_be_confirmed_and_long_enough(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('account.password'), [
                'current_password' => 'password',
                'password' => 'kurz',
                'password_confirmation' => 'anders',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'password');
    }

    public function test_login_page_links_to_password_reset(): void
    {
        $this->get(route('login'))->assertSee(route('password.request'), false)->assertSee('Passwort vergessen?');
        $this->get(route('password.request'))->assertOk()->assertSee('Neues Passwort anfordern');
    }

    public function test_a_reset_link_is_sent_by_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'lena@example.com']);

        $this->post(route('password.email'), ['email' => 'lena@example.com'])
            ->assertSessionHas('status', self::NEUTRAL_STATUS);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
            $mail = $notification->toMail($user);

            return $mail->subject === 'Neues Passwort für LingoLens AI'
                && $mail->actionText === 'Neues Passwort festlegen'
                && str_contains($mail->actionUrl, '/passwort-zuruecksetzen/'.$notification->token)
                && str_contains($mail->actionUrl, 'email=lena%40example.com');
        });
    }

    public function test_unknown_addresses_get_the_same_answer(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'niemand@example.com'])
            ->assertSessionHas('status', self::NEUTRAL_STATUS)
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_the_password_can_be_reset_with_the_token(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'lena@example.com']);
        $this->post(route('password.email'), ['email' => 'lena@example.com']);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
            $this->get(route('password.reset', ['token' => $notification->token, 'email' => $user->email]))
                ->assertOk()
                ->assertSee('Neues Passwort festlegen')
                ->assertSee('value="lena@example.com"', false);

            $this->post(route('password.store'), [
                'token' => $notification->token,
                'email' => 'lena@example.com',
                'password' => 'neues-geheimes-pw',
                'password_confirmation' => 'neues-geheimes-pw',
            ])
                ->assertRedirect(route('login'))
                ->assertSessionHas('status', 'Dein Passwort wurde geändert. Du kannst dich jetzt anmelden.');

            return true;
        });

        $this->assertTrue(Hash::check('neues-geheimes-pw', $user->refresh()->password));

        $this->post(route('login'), ['email' => 'lena@example.com', 'password' => 'neues-geheimes-pw'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'lena@example.com']);

        $this->from(route('password.reset', ['token' => 'falsch']))
            ->post(route('password.store'), [
                'token' => 'falsch',
                'email' => 'lena@example.com',
                'password' => 'neues-geheimes-pw',
                'password_confirmation' => 'neues-geheimes-pw',
            ])
            ->assertSessionHasErrors(['email' => 'Der Link zum Zurücksetzen ist ungültig oder abgelaufen. Bitte fordere einen neuen an.']);

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }
}
