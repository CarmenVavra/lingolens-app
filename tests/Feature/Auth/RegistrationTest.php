<?php

namespace Tests\Feature\Auth;

use App\Enums\SchoolType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_can_be_rendered(): void
    {
        $this->get(route('register'))
            ->assertStatus(200)
            ->assertSee('Registrieren')
            ->assertSee('Chemieingenieurwesen');
    }

    public function test_bhs_students_can_register_with_their_school_profile(): void
    {
        $this->post(route('register'), [
            'name' => 'Lena Gruber',
            'email' => 'lena@example.com',
            'password' => 'geheim-passwort',
            'password_confirmation' => 'geheim-passwort',
            'school_type' => 'BHS',
            'school_form' => 'HTL',
            'department' => 'Chemieingenieurwesen',
            'class_name' => '5CHC',
            'grade' => 5,
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        $user = User::firstWhere('email', 'lena@example.com');
        $this->assertSame(SchoolType::BHS, $user->school_type);
        $this->assertSame('Chemieingenieurwesen', $user->department);
        $this->assertSame('5CHC · HTL', $user->chipLabel());
    }

    public function test_ahs_students_do_not_keep_a_department(): void
    {
        $this->post(route('register'), [
            'name' => 'Max Huber',
            'email' => 'max@example.com',
            'password' => 'geheim-passwort',
            'password_confirmation' => 'geheim-passwort',
            'school_type' => 'AHS',
            'department' => 'Chemieingenieurwesen',
        ])->assertRedirect(route('dashboard'));

        $user = User::firstWhere('email', 'max@example.com');
        $this->assertSame(SchoolType::AHS, $user->school_type);
        $this->assertNull($user->department);
    }

    public function test_bhs_students_must_choose_a_valid_department(): void
    {
        $this->post(route('register'), [
            'name' => 'Lena Gruber',
            'email' => 'lena@example.com',
            'password' => 'geheim-passwort',
            'password_confirmation' => 'geheim-passwort',
            'school_type' => 'BHS',
            'school_form' => 'HTL',
            'department' => 'Zauberei',
        ])->assertSessionHasErrors('department');

        $this->assertGuest();
    }
}
