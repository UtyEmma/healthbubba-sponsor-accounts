import { Link } from '@inertiajs/react';
import { CalendarDaysIcon, MapPin } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import campaigns from '@/routes/campaigns';
import type { Campaign, CampaignStatus } from '@/types';
import CampaignAudienceProgress from './campaign-audience-progress';

const dateFormatter = new Intl.DateTimeFormat('en-NG', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    timeZone: 'UTC',
});

export default function CampaignItem({ campaign }: { campaign: Campaign }) {
    const location = [campaign.location, campaign.city, campaign.state]
        .filter((value): value is string => Boolean(value))
        .join(', ');
    const campaignDate = formatDateRange(campaign);

    return (
        <Link href={campaigns.show({ campaign: campaign }).url}>
            <Card>
                <CardContent className="p-6">
                    <div className="flex items-start justify-between gap-4">
                        <div className="flex min-w-0 gap-2">
                            <div className="min-w-0">
                                <h2 className="truncate font-semibold">
                                    {campaign.name}
                                </h2>
                                <div className="flex flex-wrap items-center gap-x-4 gap-y-1 pt-1 text-sm text-muted-foreground">
                                    {campaignDate && (
                                        <span className="inline-flex items-center gap-1.5">
                                            <CalendarDaysIcon
                                                className="size-3.5"
                                                aria-hidden="true"
                                            />
                                            {campaignDate}
                                        </span>
                                    )}
                                    {location && (
                                        <span className="inline-flex items-center gap-1.5">
                                            <MapPin
                                                className="size-3.5"
                                                aria-hidden="true"
                                            />
                                            {location}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                        <div className="flex items-center gap-4">
                            <CampaignStatusBadge
                                status={campaign.status}
                                label={campaign.statusLabel}
                            />
                        </div>
                    </div>
                    <div className="pt-6">
                        <CampaignAudienceProgress campaign={campaign} />
                    </div>
                </CardContent>
            </Card>
        </Link>
    );
}

function CampaignStatusBadge({
    status,
    label,
}: {
    status: CampaignStatus;
    label: string;
}) {
    if (status === 'IN_PROGRESS') {
        return <Badge variant="success">{label}</Badge>;
    }

    if (status === 'PENDING') {
        return <Badge variant="warning">{label}</Badge>;
    }

    return <Badge variant="secondary">{label}</Badge>;
}

function formatDateRange(campaign: Campaign): string | null {
    if (!campaign.startDate || !campaign.endDate) {
        return null;
    }

    return `${dateFormatter.format(new Date(campaign.startDate))} – ${dateFormatter.format(new Date(campaign.endDate))}`;
}
