<?php

namespace App\Notifications;

use App\Models\EmployeeContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractExpiring extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected EmployeeContract $contract,
        protected int $daysUntilExpiry
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $employee = $this->contract->employee;
        $expiryDate = $this->contract->end_date->format('d M Y');

        return (new MailMessage)
            ->subject("Contract Expiring in {$this->daysUntilExpiry} Days")
            ->greeting("Hello {$notifiable->name},")
            ->line("The contract for employee **{$employee->name}** will expire on **{$expiryDate}**.")
            ->line("Days until expiry: **{$this->daysUntilExpiry}**")
            ->action('View Contract', route('employees.show', $employee->id))
            ->line('Please take necessary action to renew the contract if needed.');
    }

    public function toDatabase(object $notifiable): array
    {
        $employee = $this->contract->employee;

        return [
            'title' => "Contract Expiring in {$this->daysUntilExpiry} Days",
            'message' => "Contract for {$employee->name} expires on {$this->contract->end_date->format('d M Y')}",
            'contract_id' => $this->contract->id,
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'expiry_date' => $this->contract->end_date->toDateString(),
            'days_until_expiry' => $this->daysUntilExpiry,
            'type' => 'contract_expiry',
        ];
    }
}
