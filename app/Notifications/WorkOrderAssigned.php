<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkOrderAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected $workOrder
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $client = $this->workOrder->client;
        $priority = $this->workOrder->priority;
        $dueDate = $this->workOrder->due_date->format('d M Y');

        return (new MailMessage)
            ->subject('New Work Order Assigned')
            ->greeting("Hello {$notifiable->name},")
            ->line('A new work order has been assigned to you.')
            ->line("Client: **{$client->name}**")
            ->line("Priority: **{$priority}**")
            ->line("Due date: **{$dueDate}**")
            ->line("Issue: {$this->workOrder->issue_description}")
            ->action('View Work Order', route('work-orders.show', $this->workOrder->id))
            ->line('Please acknowledge and start working on this task.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Work Order Assigned',
            'message' => "Work Order #{$this->workOrder->wo_number} assigned to you",
            'work_order_id' => $this->workOrder->id,
            'wo_number' => $this->workOrder->wo_number,
            'client_id' => $this->workOrder->client_id,
            'client_name' => $this->workOrder->client->name,
            'priority' => $this->workOrder->priority,
            'due_date' => $this->workOrder->due_date->toDateString(),
            'issue_description' => $this->workOrder->issue_description,
            'type' => 'work_order_assigned',
        ];
    }
}
