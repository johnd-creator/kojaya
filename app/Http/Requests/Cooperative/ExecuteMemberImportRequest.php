<?php

declare(strict_types=1);

namespace App\Http\Requests\Cooperative;

use App\Enums\PermissionEnum;
use App\Models\CooperativeMember;
use Illuminate\Foundation\Http\FormRequest;

class ExecuteMemberImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if (! $user->can(PermissionEnum::COOPERATIVE_MEMBER_IMPORT->value)) {
            return false;
        }

        return $user->can('executeImport', CooperativeMember::class);
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
            'preview_proof' => [
                'required',
                'string',
            ],
            'confirm_import' => [
                'required',
                'accepted',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Berkas CSV wajib diunggah untuk eksekusi impor.',
            'file.file' => 'Berkas yang diunggah tidak valid.',
            'file.max' => 'Ukuran berkas CSV maksimal 10 MB.',
            'file.mimes' => 'Format berkas harus berupa CSV (.csv). Berkas XLSX atau format lain tidak didukung.',
            'import_date.required' => 'Tanggal impor efektif wajib diisi.',
            'import_date.date_format' => 'Format tanggal impor harus berupa tanggal kalender yang valid dengan format YYYY-MM-DD.',
            'preview_proof.required' => 'Bukti pratinjau (preview proof) wajib disertakan.',
            'confirm_import.required' => 'Konfirmasi eksekusi impor wajib disetujui.',
            'confirm_import.accepted' => 'Konfirmasi eksekusi impor harus disetujui.',
        ];
    }
}
