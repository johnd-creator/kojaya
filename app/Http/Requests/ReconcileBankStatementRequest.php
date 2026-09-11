<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReconcileBankStatementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user || ! $user->can('manage_bank_reconciliation')) {
            return false;
        }

        if ($user->can('view_invoice_all')) {
            return true;
        }

        return ! empty($user->organization_id);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'statement_csv' => ['required', 'string'],
        ];
    }
}
