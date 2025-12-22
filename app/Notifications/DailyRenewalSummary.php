<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyRenewalSummary extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $summary;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $summary)
    {
        $this->summary = $summary;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $totalProcessed = isset($this->summary['total_processed']) ? $this->summary['total_processed'] : 0;
        $successful = isset($this->summary['successful']) ? $this->summary['successful'] : 0;
        $failed = isset($this->summary['failed']) ? $this->summary['failed'] : 0;
        $totalRevenue = isset($this->summary['total_revenue']) ? $this->summary['total_revenue'] : 0;
        
        $message = (new MailMessage)
            ->subject('Daily Vegbox Renewal Summary - ' . now()->format('d/m/Y'))
            ->greeting('Daily Renewal Report')
            ->line('**Summary for ' . now()->format('d/m/Y') . ':**')
            ->line('Total Renewals Processed: ' . $totalProcessed)
            ->line('Successful: ' . $successful)
            ->line('Failed: ' . $failed)
            ->line('Total Revenue: £' . number_format($totalRevenue, 2));

        if ($failed > 0 && isset($this->summary['failed_subscriptions'])) {
            $message->line('**Failed Renewals:**');
            foreach ($this->summary['failed_subscriptions'] as $failedSub) {
                $subId = isset($failedSub['id']) ? $failedSub['id'] : 'N/A';
                $reason = isset($failedSub['reason']) ? $failedSub['reason'] : 'Unknown';
                $message->line('- Subscription #' . $subId . ': ' . $reason);
            }
        }

        $message->action('View Dashboard', config('app.url') . '/admin/vegbox-subscriptions');

        if ($failed > 0) {
            $message->line('Please review failed renewals and take appropriate action.');
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->summary;
    }
}
