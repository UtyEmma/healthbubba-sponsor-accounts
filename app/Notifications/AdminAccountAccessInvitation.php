<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AdminAccountAccessInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $token,
        private readonly string $workspaceName,
    ) {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if (! $notifiable instanceof User) {
            throw new \LogicException('Account access invitations can only be sent to users.');
        }

        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Set up your HealthBubba sponsor account')
            ->greeting("Welcome to {$this->workspaceName}")
            ->line('An administrator has created your HealthBubba sponsor account.')
            ->line('Use the secure link below to choose your password and access the account.')
            ->action('Set up password', $url)
            ->line('This link is single-use and expires automatically.');
    }
}
