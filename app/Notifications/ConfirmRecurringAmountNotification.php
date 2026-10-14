<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmRecurringAmountNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Transaction $transaction,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        return (new MailMessage)
            ->subject(__('Confirm the amount of :title', ['title' => $this->transaction->note]))
            ->line(__(':title is due today, we added it with the expected amount of :amount :currency.', [
                'title' => $this->transaction->note,
                'amount' => number_format($this->transaction->amount, 2),
                'currency' => __('EGP'),
            ]))
            ->line(__('Confirm the actual amount so it counts in your totals.'))
            ->action(__('Confirm amount'), route('website.finance.transactions.index'));
    }
}
