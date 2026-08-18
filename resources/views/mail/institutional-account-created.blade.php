<x-mail::message>
# New institutional account requires action

Hello {{ $adminName }},

{{ $organizationName }} has completed its institutional campaign setup and now requires support to configure its subscription.

<x-mail::panel>
**Organization:** {{ $organizationName }}  
**Workspace ID:** {{ $workspaceId }}  
**Campaign city/state:** {{ collect([$city, $state])->filter()->implode(', ') ?: 'Not provided' }}  
**Campaign:** {{ $campaignName ?: 'Not provided' }}  
**Campaign dates:** {{ $campaignStartDate ?: 'Not provided' }} — {{ $campaignEndDate ?: 'Not provided' }}  
**Campaign location:** {{ $campaignLocation ?: 'Not provided' }}  
**Target audience:** {{ $targetAudience ?: 'Not provided' }}  
**Booth required:** {{ $boothRequired ? 'Yes' : 'No' }}  
**Account owner:** {{ $ownerName }} ({{ $ownerEmail }})
</x-mail::panel>

<x-mail::button :url="$adminUrl">
Set Up Subscription
</x-mail::button>

The sponsor will remain on the support onboarding page until an active or trialing institutional subscription is available.

Thanks,  
{{ config('app.name') }}
</x-mail::message>
