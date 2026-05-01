<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayrollApprovalRequired extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected $payroll
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $period = $this->payroll->period->format('F Y');
        $totalAmount = number_format($this->payroll->total_net, 0, ',', '.');

        return (new MailMessage)
            ->subject('Payroll Approval Required')
            ->greeting("Hello {$notifiable->name},")
            ->line("Payroll for period **{$period}** is ready for approval.")
            ->line("Total amount: **Rp {$totalAmount}**")
            ->line("Number of employees: **{$this->payroll->employee_count}**")
            ->action('Review Payroll', route('payrolls.show', $this->payroll->id))
            ->line('Please review and approve this payroll.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Payroll Approval Required',
            'message' => "Payroll for {$this->payroll->period->format('F Y')} is ready for approval",
            'payroll_id' => $this->payroll->id,
            'period' => $this->payroll->period->toDateString(),
            'total_amount' => $this->payroll->total_net,
            'employee_count' => $this->payroll->employee_count,
            'type' => 'payroll_approval',
        ];
    }
}
