<?php

use App\Mail\WorkspaceBeneficiaryInvitationMail;
use Tests\TestCase;

uses(TestCase::class);

it('renders HealthBubba branding and logo in markdown email messages', function (): void {
    config([
        'app.url' => 'https://sponsors.example.test',
        'mail.brand.name' => 'HealthBubba',
        'mail.brand.tagline' => 'Sponsor Portal',
        'mail.brand.logo_url' => 'https://sponsors.example.test/images/logo.svg',
    ]);

    $html = (new WorkspaceBeneficiaryInvitationMail(
        inviteeName: 'Ada Patient',
        workspaceName: 'Hope Alive Foundation',
        invitationUrl: 'https://sponsors.example.test/invitations/example',
        expiresAt: '20 October 2026',
    ))->render();

    expect($html)
        ->toContain('https://sponsors.example.test/images/logo.svg')
        ->toContain('HealthBubba')
        ->toContain('Sponsor Portal');
});
