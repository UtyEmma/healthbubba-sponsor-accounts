<?php

namespace App\Listeners\InstitutionalOnboarding;

use App\Enums\Account\Roles;
use App\Enums\Account\Status;
use App\Events\InstitutionalOnboarding\InstitutionalAccountCreated;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Mail\InstitutionalAccountCreatedMail;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class NotifyAdministratorsOfInstitutionalAccount
{
    public function handle(InstitutionalAccountCreated $event): void
    {
        $workspace = Workspace::query()
            ->with('latestCampaign')
            ->find($event->workspaceId);
        $owner = User::query()->find($event->ownerId);

        if (! $workspace instanceof Workspace || ! $owner instanceof User) {
            Log::warning('Institutional account notification could not resolve its subject.', [
                'workspace_id' => $event->workspaceId,
                'owner_id' => $event->ownerId,
            ]);

            return;
        }

        $administrators = User::query()
            ->whereIn('role', [Roles::ADMIN, Roles::SUPER_ADMIN])
            ->where('status', Status::ACTIVE)
            ->orderBy('id')
            ->get(['id', 'name', 'email']);

        if ($administrators->isEmpty()) {
            Log::warning('No active administrators are available for institutional account notification.', [
                'workspace_id' => $workspace->getKey(),
            ]);

            return;
        }

        $adminUrl = SubscriptionResource::getUrl('create');
        $campaign = $workspace->latestCampaign;

        foreach ($administrators as $administrator) {
            Mail::to($administrator->email)->queue(new InstitutionalAccountCreatedMail(
                adminName: $administrator->name,
                organizationName: $workspace->name,
                city: $campaign->city ?? '',
                state: $campaign->state ?? '',
                campaignName: $campaign?->name,
                campaignLocation: $campaign?->location,
                targetAudience: $campaign?->target_audience,
                campaignStartDate: $campaign?->start_date?->toFormattedDateString(),
                campaignEndDate: $campaign?->end_date?->toFormattedDateString(),
                boothRequired: $campaign->booth_required ?? false,
                ownerName: $owner->name,
                ownerEmail: $owner->email,
                workspaceId: (int) $workspace->getKey(),
                adminUrl: $adminUrl,
            ));
        }
    }
}
