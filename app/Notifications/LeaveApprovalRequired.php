<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveApprovalRequired extends Notification
{
    use Queueable;

    public function __construct(
        protected $leaveRequest
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $employee = $this->leaveRequest->employee;
        $startDate = $this->leaveRequest->start_date->format('d M Y');
        $endDate = $this->leaveRequest->end_date->format('d M Y');

        return (new MailMessage)
            ->subject('Leave Approval Required')
            ->greeting("Hello {$notifiable->name},")
            ->line("Employee **{$employee->name}** has requested leave from **{$startDate}** to **{$endDate}**.")
            ->line("Leave type: **{$this->leaveRequest->leave_type}**")
            ->line("Reason: {$this->leaveRequest->reason}")
            ->action('Review Request', route('leaves.show', $this->leaveRequest->id))
            ->line('Please review and approve or reject this request.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $employee = $this->leaveRequest->employee;

        return [
            'title' => 'Leave Approval Required',
            'message' => "{$employee->name} requested leave from {$this->leaveRequest->start_date->format('d M Y')} to {$this->leaveRequest->end_date->format('d M Y')}",
            'leave_request_id' => $this->leaveRequest->id,
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'leave_type' => $this->leaveRequest->leave_type,
            'start_date' => $this->leaveRequest->start_date->toDateString(),
            'end_date' => $this->leaveRequest->end_date->toDateString(),
            'type' => 'leave_approval',
        ];
    }
}
