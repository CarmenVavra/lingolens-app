<?php

namespace App\Http\Requests;

use App\Models\Correction;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Marks a correction as understood ("✓ Verstanden") or resets it.
 */
class UpdateCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $correction = $this->route('correction');

        return $correction instanceof Correction && $this->user()?->can('update', $correction) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'understood' => ['required', 'boolean'],
        ];
    }
}
