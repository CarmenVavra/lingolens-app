<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Change the password while logged in (requires the current password).
 */
class UpdatePasswordRequest extends FormRequest
{
    /**
     * Errors go to their own bag so they show up next to the password form.
     */
    protected $errorBag = 'updatePassword';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'current_password' => 'Aktuelles Passwort',
            'password' => 'Neues Passwort',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.current_password' => 'Das aktuelle Passwort ist nicht korrekt.',
            'password.different' => 'Das neue Passwort muss sich vom aktuellen unterscheiden.',
        ];
    }
}
