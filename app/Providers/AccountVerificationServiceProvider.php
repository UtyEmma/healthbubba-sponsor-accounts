<?php

namespace App\Providers;

use App\Contracts\SmsGateway;
use App\Services\Sms\TermiiSmsGateway;
use Illuminate\Support\ServiceProvider;

final class AccountVerificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsGateway::class, static fn (): SmsGateway => new TermiiSmsGateway(
            baseUrl: (string) config('services.termii.base_url', ''),
            apiKey: (string) config('services.termii.api_key', ''),
            senderId: (string) config('services.termii.sender_id', ''),
            channel: (string) config('services.termii.channel', 'generic'),
            timeoutSeconds: (int) config('services.termii.timeout', 10),
            connectTimeoutSeconds: (int) config('services.termii.connect_timeout', 3),
        ));
    }
}
