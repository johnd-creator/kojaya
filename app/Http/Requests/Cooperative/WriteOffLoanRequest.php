<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class WriteOffLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required_without_all:note,notes', 'nullable', 'string', 'max:1000'],
            'note' => ['required_without_all:reason,notes', 'nullable', 'string', 'max:1000'],
            'notes' => ['required_without_all:reason,note', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required_without_all' => 'Alasan write-off pinjaman wajib diisi.',
            'note.required_without_all' => 'Catatan write-off pinjaman wajib diisi.',
            'notes.required_without_all' => 'Catatan write-off pinjaman wajib diisi.',
        ];
    }

    public function writeOffNote(): string
    {
        $value = $this->validated('reason') ?? $this->validated('notes') ?? $this->validated('note');

        return trim((string) $value);
    }
}
