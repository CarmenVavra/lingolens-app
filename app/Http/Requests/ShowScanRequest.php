<?php

namespace App\Http\Requests;

use App\Enums\ErrorType;
use App\Models\Scan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Correction mode, optionally filtered to one error type (?type=Grammar).
 */
class ShowScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $scan = $this->route('scan');

        return $scan instanceof Scan && $this->user()?->can('view', $scan) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(ErrorType::class)],
        ];
    }

    public function errorType(): ?ErrorType
    {
        return ErrorType::tryFrom((string) $this->validated('type'));
    }
}
