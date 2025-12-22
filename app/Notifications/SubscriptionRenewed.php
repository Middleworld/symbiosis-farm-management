<?php

namespace App\Notifications;

use App\Models\VegboxSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionRenewed extends Notification implements ShouldQueue
{
    use Queueable;

    protected VegboxSubscription $subscription;
    protected array $paymentDetails;

    /**
     * Create a new notification instance.
     */
    public function __construct(VegboxSubscription $subscription, array $paymentDetails)
    {
        $this->subscription = $subscription;
        $this->paymentDetails = $paymentDetails;
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
        $amount = isset($this->paymentDetails['amount']) ? $this->paymentDetails['amount'] : $this->subscription->price;
        $newBalance = isset($this->paymentDetails['new_balance']) ? $this->paymentDetails['new_balance'] : null;
        $transactionId = isset($this->paymentDetails['transaction_id']) ? $this->paymentDetails['transaction_id'] : 'N/A';

        return (new MailMessage)
            ->subject('Vegbox Subscription Renewed - Middle World Farms')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your vegbox subscription has been successfully renewed.')
            ->line('**Subscription Details:**')
            ->line('Plan: ' . (isset($this->subscription->plan->name) ? $this->subscription->plan->name : 'N/A'))
            ->line('Amount Charged: £' . number_format($amount, 2))
            ->line('Transaction ID: ' . $transactionId)
            ->line('Next Billing Date: ' . $this->subscription->next_billing_at->format('d/m/Y'))
            ->when($newBalance !== null, function ($message) use ($newBalance) {
                return $message->line('Remaining Balance: £' . number_format($newBalance, 2));
            })
            ->line('Your next delivery will be on ' . ucfirst($this->subscription->delivery_day) . ' (' . ucfirst($this->subscription->delivery_time) . ').')
            ->action('View Subscription', config('app.url') . '/admin/vegbox-subscriptions/' . $this->subscription->id)
            ->line('Thank you for supporting Middle World Farms!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'plan_name' => isset($this->subscription->plan->name) ? $this->subscription->plan->name : 'N/A',
            'amount' => isset($this->paymentDetails['amount']) ? $this->paymentDetails['amount'] : $this->subscription->price,
            'transaction_id' => isset($this->paymentDetails['transaction_id']) ? $this->paymentDetails['transaction_id'] : null,
            'next_billing_at' => $this->subscription->next_billing_at->toDateTimeString(),
        ];
    }
}
