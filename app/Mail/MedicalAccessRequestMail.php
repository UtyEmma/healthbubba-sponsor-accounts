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

final class MedicalAccessRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly string $beneficiaryName,
        public readonly string $workspaceName,
        public readonly string $dataType,
        public readonly ?string $reason,
        public readonly string $reviewUrl,
        public readonly string $expiresAt,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->workspaceName} requested access to your medical data");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.medical-access-request');
    }

    /** @return array<int, mixed> */
    public function attachments(): array
    {
        return [];
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Medical access request email failed.', [
            'beneficiary' => $this->beneficiaryName,
            'workspace' => $this->workspaceName,
            'data_type' => $this->dataType,
            'error' => $exception?->getMessage(),
        ]);
    }
}
