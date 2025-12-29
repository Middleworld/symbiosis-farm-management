<?php

namespace App\Notifications;

use App\Models\VegboxSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    protected VegboxSubscription $subscription;
    protected string $reason;
    protected bool $wasAutomatic;

    /**
     * Create a new notification instance.
     */
    public function __construct(VegboxSubscription $subscription, string $reason = 'Manual cancellation', bool $wasAutomatic = false)
    {
        $this->subscription = $subscription;
        $this->reason = $reason;
        $this->wasAutomatic = $wasAutomatic;
    }

    /**
     * Get the notification's delivery channels.
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
        $message = (new MailMessage)
            ->subject('Vegbox Subscription Cancelled - Middle World Farms')
            ->greeting('Hello ' . $notifiable->name . ',');

        if ($this->wasAutomatic) {
            $message->line('Your vegbox subscription has been automatically cancelled due to repeated payment failures.')
                ->line('We\'re sorry to see you go!');
        } else {
            $message->line('Your vegbox subscription has been cancelled.');
        }

        $message->line('**Subscription Details:**')
            ->line('Plan: ' . (isset($this->subscription->plan->name) ? $this->subscription->plan->name : 'N/A'))
            ->line('Cancellation Date: ' . now()->format('d/m/Y'))
            ->line('Reason: ' . $this->reason);

        if ($this->wasAutomatic) {
            $message->line('If this was unintentional, you can reactivate your subscription at any time.')
                ->action('Reactivate Subscription', 'https://middleworldfarms.org/my-account/');
        } else {
            $message->line('You can reactivate your subscription at any time from your account.');
        }

        $message->line('Thank you for being part of the Middle World Farms community. We hope to see you again soon!');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'plan_name' => isset($this->subscription->plan->name) ? $this->subscription->plan->name : 'N/A',
            'reason' => $this->reason,
            'was_automatic' => $this->wasAutomatic,
            'cancelled_at' => now()->toDateTimeString(),
        ];
    }
}
