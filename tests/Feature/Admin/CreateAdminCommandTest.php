<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_admin_is_created_without_a_known_password(): void
    {
        $this->artisan('lingolens:admin', ['email' => 'Admin@Caryssa.at'])
            ->expectsOutput('Admin-Konto admin@caryssa.at angelegt.')
            ->assertSuccessful();

        $admin = User::firstWhere('email', 'admin@caryssa.at');
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse(Hash::check('password', $admin->password));
        $this->assertSame('Administration', $admin->chipLabel());

        // Running it again changes nothing.
        $this->artisan('lingolens:admin', ['email' => 'admin@caryssa.at'])->assertSuccessful();
        $this->assertSame(1, User::where('email', 'admin@caryssa.at')->count());
    }

    public function test_an_existing_account_is_promoted(): void
    {
        $user = User::factory()->create(['email' => 'admin@caryssa.at']);

        $this->artisan('lingolens:admin', ['email' => 'admin@caryssa.at'])
            ->expectsOutput('admin@caryssa.at ist Admin.')
            ->assertSuccessful();

        $this->assertTrue($user->refresh()->isAdmin());
        $this->assertTrue(Hash::check('password', $user->password), 'The password is kept.');
    }

    public function test_the_email_comes_from_the_env_and_must_be_valid(): void
    {
        $this->artisan('lingolens:admin')->assertFailed();
        $this->artisan('lingolens:admin', ['email' => 'kein-mail'])->assertFailed();

        config(['lingolens.admin_email' => 'admin@caryssa.at']);
        $this->artisan('lingolens:install')->assertSuccessful();

        $this->assertTrue(User::firstWhere('email', 'admin@caryssa.at')->isAdmin());
    }

    public function test_the_admin_sets_the_password_via_forgot_password(): void
    {
        $this->artisan('lingolens:admin', ['email' => 'admin@caryssa.at']);

        $this->post(route('password.email'), ['email' => 'admin@caryssa.at'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'admin@caryssa.at']);
    }
}
