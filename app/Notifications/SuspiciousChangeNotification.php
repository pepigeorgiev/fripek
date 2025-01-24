<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\DailyTransaction;

class SuspiciousChangeNotification extends Notification
{
    use Queueable;

    protected $transaction;

    public function __construct(DailyTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $changes = $this->transaction->getDirty();
        $original = $this->transaction->getOriginal();
        
        return (new MailMessage)
            ->subject('Сомнителна промена во трансакција')
            ->line('Детектирана е голема промена во трансакција:')
            ->line('ID на трансакција: ' . $this->transaction->id)
            ->line('Компанија: ' . $this->transaction->company->name)
            ->line('Датум: ' . $this->transaction->transaction_date->format('d.m.Y'))
            ->line('Корисник: ' . auth()->user()->name)
            ->line('Стари вредности:')
            ->line('- Испорачано: ' . ($original['delivered'] ?? 'N/A'))
            ->line('- Вратено: ' . ($original['returned'] ?? 'N/A'))
            ->line('Нови вредности:')
            ->line('- Испорачано: ' . ($this->transaction->delivered))
            ->line('- Вратено: ' . ($this->transaction->returned))
            ->action('Преглед на трансакција', url('/transactions/' . $this->transaction->id))
            ->line('Ве молиме проверете ја оваа промена!');
    }
}
