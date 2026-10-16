<?php

namespace App\Http\Requests\Auth;

use App\Enums\SchoolType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
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
            'password' => 'Passwort',
            'school_type' => 'Schultyp',
            'school_form' => 'Schulform',
            'department' => 'Fachrichtung',
            'class_name' => 'Klasse',
            'grade' => 'Schulstufe',
        ];
    }

    /**
     * Validated data with BHS-only fields cleared for AHS students.
     *
     * @return array<string, mixed>
     */
    public function profile(): array
    {
        $data = $this->safe()->except('password_confirmation');

        if ($data['school_type'] === SchoolType::AHS->value) {
            $data['school_form'] = 'AHS';
            $data['department'] = null;
        }

        return $data;
    }
}
