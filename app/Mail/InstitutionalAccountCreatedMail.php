<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class InstitutionalAccountCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly string $adminName,
        public readonly string $organizationName,
        public readonly string $city,
        public readonly string $state,
        public readonly ?string $campaignName,
        public readonly ?string $campaignLocation,
        public readonly ?string $targetAudience,
        public readonly ?string $campaignStartDate,
        public readonly ?string $campaignEndDate,
        public readonly bool $boothRequired,
        public readonly string $ownerName,
        public readonly string $ownerEmail,
        public readonly int $workspaceId,
        public readonly string $adminUrl,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Action required: {$this->organizationName} created an institutional account",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.institutional-account-created');
    }

    /** @return array<int, mixed> */
    public function attachments(): array
    {
        return [];
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Institutional account administrator email failed.', [
            'workspace_id' => $this->workspaceId,
            'organization' => $this->organizationName,
            'recipient' => $this->adminName,
            'error' => $exception?->getMessage(),
        ]);
    }
}
