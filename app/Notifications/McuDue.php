<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class McuDue extends Notification
{
    use Queueable;

    public function __construct(
        protected $mcu,
        protected int $daysUntilDue
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
        $employee = $this->mcu->employee;
        $dueDate = $this->mcu->next_checkup_date->format('d M Y');

        return (new MailMessage)
            ->subject("Medical Check-up Due in {$this->daysUntilDue} Days")
            ->greeting("Hello {$notifiable->name},")
            ->line("Medical check-up for employee **{$employee->name}** is due on **{$dueDate}**.")
            ->line("Days until due: **{$this->daysUntilDue}**")
            ->action('Schedule MCU', route('employees.mcu.create', $employee->id))
            ->line('Please schedule the medical check-up before the due date.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $employee = $this->mcu->employee;

        return [
            'title' => "Medical Check-up Due in {$this->daysUntilDue} Days",
            'message' => "MCU for {$employee->name} is due on {$this->mcu->next_checkup_date->format('d M Y')}",
            'mcu_id' => $this->mcu->id,
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'due_date' => $this->mcu->next_checkup_date->toDateString(),
            'days_until_due' => $this->daysUntilDue,
            'type' => 'mcu_due',
        ];
    }
}
