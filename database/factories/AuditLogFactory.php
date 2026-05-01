<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        $actions = ['CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'FAILED_LOGIN'];
        $modules = ['employees', 'certificates', 'medical_checkups', 'payrolls', 'invoices', 'auth'];

        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement($actions),
            'module' => fake()->randomElement($modules),
            'subject_type' => fake()->randomElement([
                'App\Models\Employee',
                'App\Models\EmployeeCertificate',
                'App\Models\MedicalCheckup',
                null,
            ]),
            'subject_id' => fake()->randomNumber(),
            'old_values' => fake()->randomElement([null, ['name' => fake()->name()]]),
            'new_values' => fake()->randomElement([null, ['name' => fake()->name()]]),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
