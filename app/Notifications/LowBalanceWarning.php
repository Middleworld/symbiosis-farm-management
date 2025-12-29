<?php

namespace App\Notifications;

use App\Models\VegboxSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowBalanceWarning extends Notification implements ShouldQueue
{
    use Queueable;

    protected VegboxSubscription $subscription;
    protected float $currentBalance;
    protected float $amountNeeded;

    /**
     * Create a new notification instance.
     */
    public function __construct(VegboxSubscription $subscription, float $currentBalance, float $amountNeeded)
    {
        $this->subscription = $subscription;
        $this->currentBalance = $currentBalance;
        $this->amountNeeded = $amountNeeded;
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
        $shortfall = $this->amountNeeded - $this->currentBalance;
        $daysUntilRenewal = now()->diffInDays($this->subscription->next_billing_at, false);
        
        return (new MailMessage)
            ->subject('Low Balance Warning - Vegbox Subscription')
            ->level('warning')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your account balance is running low and may not be sufficient for your next vegbox subscription renewal.')
            ->line('**Account Status:**')
            ->line('Current Balance: £' . number_format($this->currentBalance, 2))
            ->line('Next Payment Due: £' . number_format($this->amountNeeded, 2))
            ->line('Shortfall: £' . number_format($shortfall, 2))
            ->line('Next Renewal Date: ' . $this->subscription->next_billing_at->format('d/m/Y') . ' (' . ($daysUntilRenewal > 0 ? $daysUntilRenewal . ' days' : 'today') . ')')
            ->line('**Please add £' . number_format($shortfall, 2) . ' or more to your account to ensure uninterrupted service.**')
            ->action('Add Funds Now', 'https://middleworldfarms.org/my-account/')
            ->line('Thank you for your continued support!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'current_balance' => $this->currentBalance,
            'amount_needed' => $this->amountNeeded,
            'shortfall' => $this->amountNeeded - $this->currentBalance,
            'next_billing_at' => $this->subscription->next_billing_at->toDateTimeString(),
        ];
    }
}
