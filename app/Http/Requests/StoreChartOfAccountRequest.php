<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'parent_id' => ['nullable', 'uuid', 'exists:chart_of_accounts,id'],
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:255'],
            'account_type' => ['required', 'in:ASSET,LIABILITY,EQUITY,REVENUE,EXPENSE'],
            'normal_balance' => ['required', 'in:DEBIT,CREDIT'],
            'category' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
