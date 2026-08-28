<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final readonly class TermiiSmsGateway implements SmsGateway
{
    public function __construct(
        private string $baseUrl,
        private string $apiKey,
        private string $senderId,
        private string $channel,
        private int $timeoutSeconds,
        private int $connectTimeoutSeconds,
    ) {}

    public function send(string $recipient, string $message): void
    {
        if ($this->baseUrl === '' || $this->apiKey === '' || $this->senderId === '') {
            throw new RuntimeException('Termii SMS is not configured.');
        }

        Http::baseUrl(rtrim($this->baseUrl, '/'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout($this->connectTimeoutSeconds)
            ->timeout($this->timeoutSeconds)
            ->retry([200, 500])
            ->post('/api/sms/send', [
                'api_key' => $this->apiKey,
                'to' => $recipient,
                'from' => $this->senderId,
                'sms' => $message,
                'type' => 'plain',
                'channel' => $this->channel,
            ])
            ->throw();
    }
}
