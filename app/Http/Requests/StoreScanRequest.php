<?php

namespace App\Http\Requests;

use App\Enums\ScanSource;
use App\Enums\SchoolType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Setup & upload form ("Weichen-Formular").
 */
class StoreScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $schoolType = SchoolType::tryFrom((string) $this->input('school_type'));

        return [
            'school_type' => ['required', Rule::enum(SchoolType::class)],
            'department' => ['nullable', 'required_if:school_type,BHS', Rule::in(config('lingolens.departments'))],
            'text_type' => ['required', 'string', Rule::in($schoolType?->textTypes() ?? [])],
            'use_sample' => ['nullable', 'boolean'],
            // Content-based type check (photo, PDF, Word, TXT), not just the file name.
            'file' => [
                'nullable',
                'required_unless:use_sample,1',
                'file',
                'mimes:'.implode(',', ScanSource::allowedExtensions()),
                'max:'.config('lingolens.max_upload_kb'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'school_type' => 'Schultyp',
            'department' => 'Fachrichtung',
            'text_type' => 'Textsorte',
            'file' => 'Datei',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required_unless' => 'Bitte fotografiere deinen Text oder lade eine Datei hoch.',
            'file.mimes' => 'Erlaubt sind PDF, Fotos (JPG, PNG, HEIC), Word (.docx) und TXT.',
            'file.max' => 'Die Datei darf höchstens 10 MB groß sein.',
            'text_type.in' => 'Diese Textsorte passt nicht zum gewählten Schultyp.',
        ];
    }
}
