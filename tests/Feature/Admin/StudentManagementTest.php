<?php

namespace Tests\Feature\Admin;

use App\Actions\InviteStudent;
use App\Enums\SchoolType;
use App\Models\Scan;
use App\Models\User;
use App\Notifications\StudentInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    private const BHS_STUDENT = [
        'name' => 'Lena Gruber',
        'email' => 'lena@example.com',
        'school_type' => 'BHS',
        'school_form' => 'HTL',
        'department' => 'Chemieingenieurwesen',
        'class_name' => '5CHC',
        'grade' => 5,
    ];

    private function admin(): User
    {
        return User::firstWhere('email', 'admin@example.com')
            ?? User::factory()->admin()->create(['email' => 'admin@example.com']);
    }

    public function test_self_registration_is_gone(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', self::BHS_STUDENT)->assertNotFound();

        $this->get(route('login'))
            ->assertDontSee('Jetzt registrieren')
            ->assertSee('Konten werden von der Administration angelegt');
    }

    public function test_only_admins_can_open_the_student_pages(): void
    {
        $this->get(route('admin.students.index'))->assertRedirect(route('login'));

        $student = User::factory()->create();
        $this->actingAs($student)->get(route('admin.students.index'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.students.create'))->assertForbidden();
        $this->actingAs($student)->post(route('admin.students.store'), self::BHS_STUDENT)->assertForbidden();
        $this->actingAs($student)->get(route('dashboard'))->assertDontSee('Schüler:innen');

        $this->assertDatabaseMissing('users', ['email' => 'lena@example.com']);
    }

    public function test_admin_sees_the_students_with_their_status(): void
    {
        User::factory()->create(['name' => 'Lena Gruber', 'email' => 'lena@example.com']);
        User::factory()->invited()->create(['name' => 'Max Huber', 'email' => 'max@example.com']);

        $this->actingAs($this->admin())
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('Schüler:innen')
            ->assertSeeInOrder(['Lena Gruber', 'lena@example.com', 'Aktiv', 'Max Huber', 'max@example.com', 'Einladung offen'])
            ->assertSee('Einladung erneut senden')
            ->assertDontSee('admin@example.com');

        $this->actingAs($this->admin())->get(route('dashboard'))->assertSee(route('admin.students.index'), false);
    }

    public function test_admin_creates_a_student_and_the_invitation_is_sent(): void
    {
        Notification::fake();

        $this->actingAs($this->admin())
            ->get(route('admin.students.create'))
            ->assertOk()
            ->assertSee('Chemieingenieurwesen')
            ->assertSee('name="_token"', false);

        $this->actingAs($this->admin())
            ->post(route('admin.students.store'), self::BHS_STUDENT + ['is_admin' => '1', 'password' => 'secret123'])
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHas('status', 'Konto für Lena Gruber angelegt. Die Einladung wurde an lena@example.com geschickt.');

        $student = User::firstWhere('email', 'lena@example.com');
        $this->assertFalse($student->isAdmin(), 'is_admin cannot be set through the form.');
        $this->assertFalse($student->hasAcceptedInvitation());
        $this->assertSame(SchoolType::BHS, $student->school_type);
        $this->assertSame('5CHC · HTL', $student->chipLabel());
        $this->assertFalse(password_verify('secret123', $student->password));

        Notification::assertSentTo($student, StudentInvitationNotification::class, function (StudentInvitationNotification $notification) use ($student) {
            $mail = $notification->toMail($student);

            return $mail->subject === 'Einladung zu LingoLens AI'
                && $mail->greeting === 'Servus Lena!'
                && $mail->actionText === 'Passwort festlegen'
                && str_contains($mail->actionUrl, '/einladung/'.$notification->token)
                && str_contains(implode(' ', $mail->outroLines), '7 Tage');
        });
    }

    public function test_ahs_students_do_not_keep_a_department(): void
    {
        Notification::fake();

        $this->actingAs($this->admin())->post(route('admin.students.store'), [
            'name' => 'Max Huber',
            'email' => 'max@example.com',
            'school_type' => 'AHS',
            'department' => 'Chemieingenieurwesen',
        ])->assertSessionHasNoErrors();

        $student = User::firstWhere('email', 'max@example.com');
        $this->assertSame(SchoolType::AHS, $student->school_type);
        $this->assertSame('AHS', $student->school_form);
        $this->assertNull($student->department);
    }

    public function test_invalid_student_data_is_rejected(): void
    {
        User::factory()->create(['email' => 'lena@example.com']);

        $this->actingAs($this->admin())
            ->post(route('admin.students.store'), ['department' => 'Zauberei'] + self::BHS_STUDENT)
            ->assertSessionHasErrors([
                'email' => 'Für diese E-Mail-Adresse gibt es schon ein Konto.',
                'department',
            ]);

        $this->assertSame(1, User::where('email', 'lena@example.com')->count());
    }

    public function test_the_invitation_can_be_sent_again_but_not_twice_a_minute(): void
    {
        Notification::fake();
        $student = User::factory()->invited()->create(['email' => 'max@example.com']);

        $this->actingAs($this->admin())
            ->post(route('admin.students.invite', $student))
            ->assertSessionHas('status', 'Die Einladung wurde an max@example.com geschickt.');

        $this->actingAs($this->admin())
            ->post(route('admin.students.invite', $student))
            ->assertSessionHas('error');

        Notification::assertSentToTimes($student, StudentInvitationNotification::class, 1);
    }

    public function test_a_mail_error_is_shown_instead_of_crashing(): void
    {
        $this->mock(InviteStudent::class)->shouldReceive('handle')->andThrow(new RuntimeException('SMTP down'));

        $this->actingAs($this->admin())
            ->followingRedirects()
            ->post(route('admin.students.store'), self::BHS_STUDENT)
            ->assertOk()
            ->assertSee('Die Einladung konnte nicht per E-Mail gesendet werden');

        $this->assertDatabaseHas('users', ['email' => 'lena@example.com']);
    }

    public function test_admin_deletes_a_student_with_all_texts(): void
    {
        $student = User::factory()->create(['name' => 'Lena Gruber', 'email' => 'lena@example.com']);
        $scan = Scan::factory()->for($student)->create();
        DB::table('invitation_tokens')->insert(['email' => 'lena@example.com', 'token' => 'x', 'created_at' => now()]);

        $this->actingAs($this->admin())
            ->delete(route('admin.students.destroy', $student))
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHas('status', 'Das Konto von Lena Gruber und alle Texte wurden gelöscht.');

        $this->assertModelMissing($student);
        $this->assertModelMissing($scan);
        $this->assertDatabaseMissing('invitation_tokens', ['email' => 'lena@example.com']);
    }

    public function test_admins_and_students_cannot_be_deleted_by_the_wrong_people(): void
    {
        $admin = $this->admin();
        $otherAdmin = User::factory()->admin()->create(['email' => 'chef@example.com']);
        $student = User::factory()->create();
        $classmate = User::factory()->create();

        $this->actingAs($admin)->delete(route('admin.students.destroy', $otherAdmin))->assertForbidden();
        $this->actingAs($student)->delete(route('admin.students.destroy', $classmate))->assertForbidden();
        $this->actingAs($student)->post(route('admin.students.invite', $classmate))->assertForbidden();

        $this->assertModelExists($otherAdmin);
        $this->assertModelExists($classmate);
    }
}
