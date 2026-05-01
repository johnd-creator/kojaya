<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OvertimeApprovalRequired extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected $overtimeRequest
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $employee = $this->overtimeRequest->employee;
        $date = $this->overtimeRequest->date->format('d M Y');
        $hours = $this->overtimeRequest->hours;

        return (new MailMessage)
            ->subject('Overtime Approval Required')
            ->greeting("Hello {$notifiable->name},")
            ->line("Employee **{$employee->name}** has requested overtime on **{$date}**.")
            ->line("Duration: **{$hours} hours**")
            ->line("Reason: {$this->overtimeRequest->reason}")
            ->action('Review Request', route('overtime.show', $this->overtimeRequest->id))
            ->line('Please review and approve or reject this request.');
    }

    public function toArray(object $notifiable): array
    {
        $employee = $this->overtimeRequest->employee;

        return [
            'title' => 'Overtime Approval Required',
            'message' => "{$employee->name} requested overtime on {$this->overtimeRequest->date->format('d M Y')} for {$this->overtimeRequest->hours} hours",
            'overtime_request_id' => $this->overtimeRequest->id,
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'date' => $this->overtimeRequest->date->toDateString(),
            'hours' => $this->overtimeRequest->hours,
            'type' => 'overtime_approval',
        ];
    }
}
