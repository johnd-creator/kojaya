<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CooperativeReceipt>
 */
class CooperativeReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $member = \App\Models\CooperativeMember::factory()->active()->create();
        $type = \App\Models\CooperativeContributionType::query()->create([
            'code' => fake()->unique()->bothify('WAJIB-###'),
            'name' => 'Simpanan Wajib',
            'category' => 'WAJIB',
            'default_amount' => 100000,
            'frequency' => 'MONTHLY',
            'is_active' => true,
        ]);
        $invoice = \App\Models\CooperativeDuesInvoice::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_contribution_type_id' => $type->id,
            'period' => now()->format('Y-m'),
            'amount' => 100000,
            'paid_amount' => 100000,
            'due_date' => now()->toDateString(),
            'status' => 'PAID',
        ]);
        $payment = \App\Models\CooperativePayment::query()->create([
            'cooperative_member_id' => $member->id,
            'cooperative_dues_invoice_id' => $invoice->id,
            'user_id' => $member->user_id,
            'amount' => 100000,
            'payment_method' => 'TRANSFER',
            'paid_at' => now()->toDateString(),
            'status' => 'APPROVED',
        ]);

        return [
            'receipt_no' => 'RC-'.now()->format('Ym').'-'.fake()->unique()->numerify('######'),
            'cooperative_payment_id' => $payment->id,
            'cooperative_member_id' => $member->id,
            'pdf_path' => 'cooperative/receipts/'.fake()->uuid().'.pdf',
            'issued_at' => now(),
            'issued_by' => \App\Models\User::factory(),
        ];
    }
}
