<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * The admin changes an existing student account (same fields as when creating it).
 */
class UpdateStudentRequest extends StoreStudentRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage-student', $this->student());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->student())],
        ] + parent::rules();
    }

    private function student(): User
    {
        /** @var User $student */
        $student = $this->route('student');

        return $student;
    }
}
