<x-mail::message>
# You have been invited

Hello {{ $inviteeName }},

{{ $workspaceName }} has invited you to join its HealthBubba sponsor team as {{ $role }}.

<x-mail::button :url="$invitationUrl">
Review invitation
</x-mail::button>

This invitation expires {{ $expiresAt }}.

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
