<?php

namespace App\Http\Requests;

use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if (! $user->can(PermissionEnum::ATTENDANCE_APPROVE->value)) {
            return false;
        }

        if ($user->can(PermissionEnum::ATTENDANCE_VIEW_ALL->value)) {
            return true;
        }

        if ($user->can(PermissionEnum::ATTENDANCE_VIEW_UNIT->value) && ! empty($user->organization_id)) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer'],
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'date' => ['required', 'date'],
            'clock_in' => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i', 'after:clock_in'],
            'status' => ['required', 'in:PRESENT,ABSENT,SICK,LEAVE,OFF'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Karyawan wajib dipilih.',
            'date.required' => 'Tanggal absensi wajib diisi.',
            'status.required' => 'Status absensi wajib dipilih.',
        ];
    }
}
