<?php

namespace App\Notifications;

use App\Models\VegboxSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionPaymentFailed extends Notification implements ShouldQueue
{
    use Queueable;

    protected VegboxSubscription $subscription;
    protected string $reason;
    protected ?float $currentBalance;

    /**
     * Create a new notification instance.
     */
    public function __construct(VegboxSubscription $subscription, string $reason, ?float $currentBalance = null)
    {
        $this->subscription = $subscription;
        $this->reason = $reason;
        $this->currentBalance = $currentBalance;
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
        $amountNeeded = $this->subscription->price;
        
        $message = (new MailMessage)
            ->subject('Action Required: Vegbox Subscription Payment Failed')
            ->level('error')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We were unable to process your vegbox subscription payment.')
            ->line('**Subscription Details:**')
            ->line('Plan: ' . (isset($this->subscription->plan->name) ? $this->subscription->plan->name : 'N/A'))
            ->line('Amount Required: £' . number_format($amountNeeded, 2));

        if ($this->currentBalance !== null) {
            $message->line('Current Balance: £' . number_format($this->currentBalance, 2));
            
            if ($this->currentBalance < $amountNeeded) {
                $shortfall = $amountNeeded - $this->currentBalance;
                $message->line('**Please add £' . number_format($shortfall, 2) . ' to your account to continue your subscription.**');
            }
        }

        $message->line('Reason: ' . $this->reason)
            ->line('We will automatically retry the payment in the next few days. To avoid interruption of service, please ensure you have sufficient funds in your account.')
            ->action('Add Funds', 'https://middleworldfarms.org/my-account/')
            ->line('If you have any questions, please contact us at middleworldfarms@gmail.com');

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
            'amount_required' => $this->subscription->price,
            'current_balance' => $this->currentBalance,
            'reason' => $this->reason,
            'next_retry' => now()->addDays(2)->toDateTimeString(),
        ];
    }
}
