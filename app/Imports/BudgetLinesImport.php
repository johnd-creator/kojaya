<?php

namespace App\Imports;

use App\Models\Budget;
use App\Models\BudgetLine;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class BudgetLinesImport implements ToModel, WithHeadingRow, WithValidation
{
    protected Budget $budget;

    public function __construct(Budget $budget)
    {
        $this->budget = $budget;
    }

    public function model(array $row)
    {
        // Use updateOrCreate to allow updating allocated amounts if the line exists
        // based on the unique combination of GL Account, Project, and Cost Center.
        return BudgetLine::updateOrCreate(
            [
                'budget_id' => $this->budget->id,
                'gl_account' => $row['gl_account'],
                'project_id' => $row['project_id'] ?? null,
                'cost_center' => $row['cost_center'] ?? null,
            ],
            [
                'category' => $row['category'],
                'allocated_amount' => $row['allocated_amount'],
                // We do not update committed/realized amounts here as they are actuals
            ]
        );
    }

    public function rules(): array
    {
        return [
            'gl_account' => ['required', 'string', 'max:50'],
            'category' => ['required', Rule::in(['OPEX', 'CAPEX'])],
            'allocated_amount' => ['required', 'numeric', 'min:0'],
            'project_id' => ['nullable', 'uuid', 'exists:projects,id'],
            'cost_center' => ['nullable', 'string', 'max:50'],
        ];
    }
}
