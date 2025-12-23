<?php

namespace App\Notifications;

use App\Models\VegboxSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRetryReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected VegboxSubscription $subscription;
    protected int $attemptNumber;

    public function __construct(VegboxSubscription $subscription, int $attemptNumber)
    {
        $this->subscription = $subscription;
        $this->attemptNumber = $attemptNumber;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $daysUntilCancellation = $this->subscription->grace_period_ends_at 
            ? now()->diffInDays($this->subscription->grace_period_ends_at, false)
            : 7;

        $message = (new MailMessage)
            ->subject('Payment Retry Reminder - Middleworld Farms')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line("We tried to process your subscription payment but it was unsuccessful.");
        
        if ($this->attemptNumber === 1) {
            $message->line("Don't worry - we'll automatically try again in a few hours.")
                ->line("If the payment continues to fail, please update your payment method to avoid any interruption to your subscription.");
        } elseif ($this->attemptNumber === 2) {
            $message->line("This is our second attempt to process your payment.")
                ->line("We'll try once more, but if the payment fails again, please update your payment method as soon as possible.");
        } else {
            $message->line("**This is our final reminder.**")
                ->line("Your subscription will be cancelled in {$daysUntilCancellation} days if we can't process your payment.")
                ->line("Please update your payment method immediately to keep your subscription active.");
        }

        $message->line("**Subscription Details:**")
            ->line("Plan: " . ($this->subscription->plan->name ?? 'Unknown'))
            ->line("Amount: £" . number_format($this->subscription->price, 2))
            ->line("Failed attempts: " . $this->subscription->failed_payment_count);

        if ($this->subscription->last_payment_error) {
            $message->line("Reason: " . $this->subscription->last_payment_error);
        }

        $message->action('Update Payment Method', url('/my-account/payment-methods'))
            ->line('If you have any questions, please don\'t hesitate to contact us.')
            ->salutation('Best regards, Middleworld Farms Team');

        return $message;
    }
}