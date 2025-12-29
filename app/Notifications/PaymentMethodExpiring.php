<?php

namespace App\Notifications;

use App\Models\UserPaymentMethod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentMethodExpiring extends Notification implements ShouldQueue
{
    use Queueable;

    protected UserPaymentMethod $paymentMethod;

    public function __construct(UserPaymentMethod $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiryDate = sprintf(
            '%02d/%04d',
            $this->paymentMethod->card_exp_month,
            $this->paymentMethod->card_exp_year
        );

        return (new MailMessage)
            ->subject('Payment Method Expiring Soon - Middleworld Farms')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your payment method is about to expire.')
            ->line("**Card Details:**")
            ->line("Type: " . ucfirst($this->paymentMethod->card_brand))
            ->line("Last 4 digits: •••• {$this->paymentMethod->card_last4}")
            ->line("Expires: {$expiryDate}")
            ->line("To ensure uninterrupted service, please update your payment method before it expires.")
            ->action('Update Payment Method', url('/my-account/payment-methods'))
            ->line('If you\'ve already updated your card with your bank, it may be automatically updated. Otherwise, please add a new payment method.')
            ->salutation('Best regards, Middleworld Farms Team');
    }
}