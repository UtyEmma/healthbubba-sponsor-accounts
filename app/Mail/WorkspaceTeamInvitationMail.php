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

final class WorkspaceTeamInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly string $inviteeName,
        public readonly string $workspaceName,
        public readonly string $role,
        public readonly string $invitationUrl,
        public readonly string $expiresAt,
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "You're invited to join {$this->workspaceName} on HealthBubba");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.workspace-team-invitation');
    }

    /** @return array<int, mixed> */
    public function attachments(): array
    {
        return [];
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Workspace team invitation email failed.', [
            'workspace' => $this->workspaceName,
            'invitee' => $this->inviteeName,
            'error' => $exception?->getMessage(),
        ]);
    }
}
