<?php

namespace App\Http\Requests\Admin;

use App\Enums\SchoolType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The admin creates a student account. The student sets the password
 * and confirms the privacy policy via the invitation link.
 */
class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('admin');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'school_type' => ['required', Rule::enum(SchoolType::class)],
            'school_form' => ['nullable', 'required_if:school_type,BHS', Rule::in(config('lingolens.school_forms'))],
            'department' => ['nullable', 'required_if:school_type,BHS', Rule::in(config('lingolens.departments'))],
            'class_name' => ['nullable', 'string', 'max:20'],
            'grade' => ['nullable', 'integer', 'between:1,8'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Name',
            'email' => 'E-Mail',
            'school_type' => 'Schultyp',
            'school_form' => 'Schulform',
            'department' => 'Fachrichtung',
            'class_name' => 'Klasse',
            'grade' => 'Schulstufe',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Für diese E-Mail-Adresse gibt es schon ein Konto.',
        ];
    }

    /**
     * Validated data with BHS-only fields cleared for AHS students.
     *
     * @return array<string, mixed>
     */
    public function profile(): array
    {
        $data = $this->validated();

        if ($data['school_type'] === SchoolType::AHS->value) {
            $data['school_form'] = 'AHS';
            $data['department'] = null;
        }

        return $data;
    }
}
