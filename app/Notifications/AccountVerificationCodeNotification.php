<?php

namespace App\Notifications;

use App\Enums\VerificationChannel;
use App\Models\AccountVerificationChallenge;
use App\Notifications\Channels\TermiiChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AccountVerificationCodeNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [5, 30, 90];

    public function __construct(
        public readonly int $challengeId,
        public readonly VerificationChannel $channel,
        private readonly string $code,
    ) {
        $this->afterCommit();
        $this->onQueue($channel === VerificationChannel::Email ? 'mail' : 'sms');
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return $this->channel === VerificationChannel::Email
            ? ['mail']
            : [TermiiChannel::class];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return AccountVerificationChallenge::query()
            ->whereKey($this->challengeId)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your HealthBubba sponsor account')
            ->greeting('Verify your account')
            ->line('Use this one-time code to finish creating your Institutional Sponsor account:')
            ->line($this->code)
            ->line('This code expires in 10 minutes. If you did not create this account, you can ignore this message.');
    }

    public function smsMessage(): string
    {
        return "Your HealthBubba verification code is {$this->code}. It expires in 10 minutes.";
    }
}
