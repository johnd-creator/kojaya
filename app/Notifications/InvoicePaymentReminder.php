<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoicePaymentReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected $invoice,
        protected int $daysUntilDue
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dueDate = $this->invoice->due_date->format('d M Y');
        $amount = number_format($this->invoice->total_amount, 0, ',', '.');
        $client = $this->invoice->client;

        return (new MailMessage)
            ->subject("Invoice Payment Due in {$this->daysUntilDue} Days")
            ->greeting("Hello {$notifiable->name},")
            ->line("Invoice **{$this->invoice->invoice_number}** for **{$client->name}** is due in **{$this->daysUntilDue} days**.")
            ->line("Amount: **Rp {$amount}**")
            ->line("Due date: **{$dueDate}**")
            ->action('View Invoice', route('invoices.show', $this->invoice->id))
            ->line('Please follow up on payment before the due date.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Invoice Payment Due in {$this->daysUntilDue} Days",
            'message' => "Invoice {$this->invoice->invoice_number} due on {$this->invoice->due_date->format('d M Y')}",
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'client_id' => $this->invoice->client_id,
            'client_name' => $this->invoice->client->name,
            'amount' => $this->invoice->total_amount,
            'due_date' => $this->invoice->due_date->toDateString(),
            'days_until_due' => $this->daysUntilDue,
            'type' => 'invoice_payment_reminder',
        ];
    }
}
