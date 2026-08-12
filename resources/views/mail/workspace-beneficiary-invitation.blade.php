<x-mail::message>
# You have been invited

Hello {{ $inviteeName }},

{{ $workspaceName }} has invited you to receive healthcare sponsorship through HealthBubba.

<x-mail::button :url="$invitationUrl">
View invitation
</x-mail::button>

This invitation expires {{ $expiresAt }}. You can accept or decline it from the invitation page.

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
