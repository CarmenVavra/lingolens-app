<?php

namespace App\Http\Controllers\Admin;

use App\Actions\DeleteStudent;
use App\Actions\InviteStudent;
use App\Actions\UpdateStudent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentRequest;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Models\User;
use App\Support\LingoLens;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * Admin area: only the admin creates student accounts (no self-registration).
 */
class StudentController extends Controller
{
    public function index(): View
    {
        $students = User::query()
            ->where('is_admin', false)
            ->withCount('scans')
            ->orderBy('name')
            ->get();
        $invitationDays = LingoLens::invitationDays();

        return view('admin.students.index', compact('students', 'invitationDays'));
    }

    public function create(): View
    {
        $departments = config('lingolens.departments');
        $schoolForms = array_values(array_diff(config('lingolens.school_forms'), ['AHS']));

        return view('admin.students.create', compact('departments', 'schoolForms'));
    }

    public function store(StoreStudentRequest $request, InviteStudent $invite): RedirectResponse
    {
        // Random password nobody knows – the student sets the real one via the invitation link.
        $student = User::create($request->profile() + ['password' => Str::random(64)]);

        return $this->sendInvitation($invite, $student, "Konto für {$student->name} angelegt.");
    }

    public function edit(User $student): View
    {
        $departments = config('lingolens.departments');
        $schoolForms = array_values(array_diff(config('lingolens.school_forms'), ['AHS']));

        return view('admin.students.edit', compact('student', 'departments', 'schoolForms'));
    }

    public function update(UpdateStudentRequest $request, User $student, UpdateStudent $updateStudent, InviteStudent $invite): RedirectResponse
    {
        $emailChanged = $updateStudent->handle($student, $request->profile());
        $message = "Änderungen für {$student->name} gespeichert.";

        // An open invitation went to the old address – send it to the new one.
        if ($emailChanged && ! $student->hasAcceptedInvitation()) {
            return $this->sendInvitation($invite, $student, $message);
        }

        if ($emailChanged) {
            $message .= " Ab jetzt meldet sich {$student->name} mit {$student->email} an.";
        }

        return redirect()->route('admin.students.index')->with('status', $message);
    }

    public function invite(User $student, InviteStudent $invite): RedirectResponse
    {
        return $this->sendInvitation($invite, $student, '');
    }

    public function destroy(User $student, DeleteStudent $deleteStudent): RedirectResponse
    {
        $deleteStudent->handle($student);

        return redirect()->route('admin.students.index')
            ->with('status', "Das Konto von {$student->name} und alle Texte wurden gelöscht.");
    }

    private function sendInvitation(InviteStudent $invite, User $student, string $prefix): RedirectResponse
    {
        $redirect = redirect()->route('admin.students.index');
        $prefix = $prefix === '' ? '' : $prefix.' ';

        try {
            $status = $invite->handle($student);
        } catch (Throwable $exception) {
            report($exception);

            return $redirect->with('error', $prefix.'Die Einladung konnte nicht per E-Mail gesendet werden. Prüfe die Mail-Einstellungen in der .env und sende sie erneut.');
        }

        if ($status === Password::RESET_THROTTLED) {
            return $redirect->with('error', $prefix.'Die Einladung wurde gerade erst gesendet. Bitte warte eine Minute, bevor du sie erneut schickst.');
        }

        return $redirect->with('status', $prefix."Die Einladung wurde an {$student->email} geschickt.");
    }
}
