<?php

namespace App\Notifications;

use App\Models\VegboxSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class FinalPaymentWarning extends Notification implements ShouldQueue
{
    use Queueable;

    protected VegboxSubscription $subscription;

    public function __construct(VegboxSubscription $subscription)
    {
        $this->subscription = $subscription;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $cancellationDate = $this->subscription->grace_period_ends_at 
            ?? now()->addDays(3);

        return (new MailMessage)
            ->subject('URGENT: Subscription Will Be Cancelled - Middleworld Farms')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('**Your subscription will be cancelled soon if we cannot process your payment.**')
            ->line("We've tried multiple times to process your subscription payment but have been unsuccessful.")
            ->line("**Important Details:**")
            ->line("Plan: " . ($this->subscription->plan->name ?? 'Unknown'))
            ->line("Amount: £" . number_format($this->subscription->price, 2))
            ->line("Cancellation date: " . $cancellationDate->format('d/m/Y'))
            ->line("Days remaining: " . now()->diffInDays($cancellationDate, false))
            ->line("**What You Need To Do:**")
            ->line("1. Update your payment method immediately")
            ->line("2. Ensure sufficient funds are available")
            ->line("3. Contact your bank if you're having issues")
            ->line("If you wish to cancel your subscription, you can do so in your account. Otherwise, please update your payment method to avoid automatic cancellation.")
            ->action('Update Payment Method Now', url('/my-account/payment-methods'))
            ->line('Need help? Contact us at middleworldfarms@gmail.com or reply to this email.')
            ->salutation('Urgent regards, Middleworld Farms Team');
    }
}