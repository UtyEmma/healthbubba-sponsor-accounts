<?php

namespace App\Notifications\Channels;

use App\Contracts\SmsGateway;
use App\Notifications\AccountVerificationCodeNotification;
use Illuminate\Notifications\Notification;

final readonly class TermiiChannel
{
    public function __construct(private SmsGateway $gateway) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! $notification instanceof AccountVerificationCodeNotification) {
            return;
        }

        $recipient = $notifiable->routeNotificationFor('termii', $notification);

        if (! is_string($recipient) || $recipient === '') {
            return;
        }

        $this->gateway->send($recipient, $notification->smsMessage());
    }
}
