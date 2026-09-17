<?php

namespace App\Http\Requests\Cooperative;

use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

class PreviewMemberImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::COOPERATIVE_MEMBER_MANAGE->value) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10 MB
                'mimes:csv,txt',
            ],
            'organization_id' => [
                'nullable',
                'string',
            ],
            'import_date' => [
                'required',
                'date_format:Y-m-d',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Berkas CSV wajib diunggah.',
            'file.file' => 'Berkas yang diunggah tidak valid.',
            'file.max' => 'Ukuran berkas CSV maksimal 10 MB.',
            'file.mimes' => 'Format berkas harus berupa CSV (.csv). Berkas XLSX atau format lain tidak didukung.',
            'import_date.required' => 'Tanggal impor wajib diisi.',
            'import_date.date_format' => 'Format tanggal impor harus berupa tanggal kalender yang valid dengan format YYYY-MM-DD.',
        ];
    }
}
