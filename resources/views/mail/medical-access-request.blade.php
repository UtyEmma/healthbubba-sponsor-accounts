<x-mail::message>
# Medical access request

Hello {{ $beneficiaryName }},

{{ $workspaceName }} has requested access to your {{ $dataType }} on HealthBubba.

@if ($reason)
**Reason:** {{ $reason }}
@endif

<x-mail::button :url="$reviewUrl">
Review request
</x-mail::button>

This consent request expires {{ $expiresAt }}. If approved, access remains valid for 30 days from the time you approve it.

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
