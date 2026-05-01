<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SioExpiring extends Notification
{
    use Queueable;

    public function __construct(
        protected $certificate,
        protected int $daysUntilExpiry
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
        $employee = $this->certificate->employee;
        $expiryDate = $this->certificate->expiry_date->format('d M Y');

        return (new MailMessage)
            ->subject("SIO Certificate Expiring in {$this->daysUntilExpiry} Days")
            ->greeting("Hello {$notifiable->name},")
            ->line("The SIO certificate for employee **{$employee->name}** will expire on **{$expiryDate}**.")
            ->line("Certificate number: **{$this->certificate->certificate_number}**")
            ->line("Days until expiry: **{$this->daysUntilExpiry}**")
            ->action('View Certificate', route('employees.certificates.show', [$employee->id, $this->certificate->id]))
            ->line('Please take necessary action to renew the certificate.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $employee = $this->certificate->employee;

        return [
            'title' => "SIO Certificate Expiring in {$this->daysUntilExpiry} Days",
            'message' => "SIO certificate for {$employee->name} expires on {$this->certificate->expiry_date->format('d M Y')}",
            'certificate_id' => $this->certificate->id,
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'certificate_number' => $this->certificate->certificate_number,
            'expiry_date' => $this->certificate->expiry_date->toDateString(),
            'days_until_expiry' => $this->daysUntilExpiry,
            'type' => 'sio_expiry',
        ];
    }
}
