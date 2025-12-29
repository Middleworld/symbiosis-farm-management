<?php

namespace App\Notifications;

use App\Models\VegboxSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentMethodMigrationRequired extends Notification implements ShouldQueue
{
    use Queueable;

    protected $subscription;
    protected $oldProvider;

    public function __construct(VegboxSubscription $subscription, string $oldProvider = 'WooCommerce Payments')
    {
        $this->subscription = $subscription;
        $this->oldProvider = $oldProvider;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $paymentMethodsUrl = config('app.url') . '/my-account/payment-methods/';
        
        return (new MailMessage)
            ->subject('Action Required: Update Your Payment Card')
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line('We hope you\'re enjoying your vegbox subscription!')
            ->line('We\'re writing to let you know that we\'re moving away from our old card processing system (' . $this->oldProvider . ') to a more secure and reliable payment platform (Stripe).')
            ->line('**Due to card security regulations (PCI-DSS), we\'re not legally permitted to transfer your saved card details to the new system.** This is to protect your financial information.')
            ->line('**What you need to do:**')
            ->line('Please take a moment to re-add your payment card using the link below. It only takes a minute, and your subscription will continue as normal.')
            ->action('Update Payment Card', $paymentMethodsUrl)
            ->line('Your current subscription details:')
            ->line('• Amount: £' . number_format($this->subscription->price, 2) . ' per ' . $this->subscription->billing_period)
            ->line('• Next billing: ' . $this->subscription->next_billing_at?->format('d M Y'))
            ->line('Once you\'ve added your card, your next payment will process automatically. If you have any questions or need help, just reply to this email!')
            ->line('Thanks for being a valued customer and friend of Middle World Farms.')
            ->salutation('Best wishes, The Middle World Farms Team');
    }

    public function toArray($notifiable)
    {
        return [
            'subscription_id' => $this->subscription->id,
            'old_provider' => $this->oldProvider,
            'amount' => $this->subscription->price,
            'next_billing_at' => $this->subscription->next_billing_at?->toDateTimeString(),
        ];
    }
}
