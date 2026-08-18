import { Head, Link } from '@inertiajs/react';
import { ArrowLeftIcon, CalendarDaysIcon } from 'lucide-react';

import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { DashboardLayout } from '@/layouts/dashboard';
import institutional from '@/routes/institutional';
import type {
    Campaign,
    CampaignStatus,
    InstitutionalCampaignShowPageProps,
} from '@/types';

const dateFormatter = new Intl.DateTimeFormat('en-NG', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    timeZone: 'UTC',
});

export default function InstitutionalCampaignDetailsPage({
    organization,
    campaign,
}: InstitutionalCampaignShowPageProps) {
    const campaignArea = [
        campaign.location,
        campaign.city,
        campaign.state,
        campaign.country,
    ]
        .filter((value): value is string => Boolean(value))
        .join(', ');
    const campaignMetadata = [campaignArea, formatDateRange(campaign)]
        .filter((value): value is string => Boolean(value))
        .join(' · ');

    return (
        <>
            <Head title={campaign.name} />
            <DashboardLayout>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Campaign Details"
                        description={`Review the campaign configured for ${organization.name}.`}
                        action={
                            <Link
                                href={institutional.campaigns.index()}
                                className={buttonVariants({
                                    variant: 'outline',
                                    size: 'compact',
                                })}
                            >
                                <ArrowLeftIcon className="size-4" />
                                All Campaigns
                            </Link>
                        }
                    />

                    <Card className="mt-10">
                        <CardContent className="flex min-h-24 flex-col justify-between gap-4 px-6 py-5 sm:flex-row sm:items-center">
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <h2 className="text-base font-semibold">
                                        {campaign.name}
                                    </h2>
                                    <CampaignStatusBadge
                                        status={campaign.status}
                                        label={campaign.statusLabel}
                                    />
                                </div>
                                {campaignMetadata && (
                                    <p className="pt-1 text-sm text-muted-foreground">
                                        {campaignMetadata}
                                    </p>
                                )}
                            </div>
                            <span className="flex items-center gap-2 text-sm text-muted-foreground">
                                <CalendarDaysIcon className="size-4" />
                                {timelineLabel(campaign)}
                            </span>
                        </CardContent>
                    </Card>

                    <section className="grid gap-4 pt-4 lg:grid-cols-[2fr_1fr]">
                        <Card>
                            <CardHeader className="gap-1 px-6 pt-6 pb-3">
                                <CardTitle className="text-base">
                                    Campaign overview
                                </CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    Schedule, audience and current campaign
                                    progress.
                                </p>
                            </CardHeader>
                            <CardContent className="grid gap-5 px-6 pt-3 pb-6">
                                <CampaignProgress campaign={campaign} />
                                <dl className="grid gap-4 border-t pt-5 text-sm sm:grid-cols-2">
                                    {campaign.startDate && (
                                        <DetailRow
                                            label="Start date"
                                            value={formatDate(
                                                campaign.startDate,
                                            )}
                                        />
                                    )}
                                    {campaign.endDate && (
                                        <DetailRow
                                            label="End date"
                                            value={formatDate(campaign.endDate)}
                                        />
                                    )}
                                    {campaign.targetAudience && (
                                        <DetailRow
                                            label="Target audience"
                                            value={campaign.targetAudience}
                                            wide
                                        />
                                    )}
                                </dl>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="px-6 pt-6 pb-4">
                                <CardTitle className="text-base">
                                    Campaign setup
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="px-6 pt-2 pb-6">
                                <dl className="grid gap-4 text-sm">
                                    <SummaryRow
                                        label="Organization"
                                        value={organization.name}
                                    />
                                    {campaign.city && (
                                        <SummaryRow
                                            label="City"
                                            value={campaign.city}
                                        />
                                    )}
                                    {campaign.state && (
                                        <SummaryRow
                                            label="State"
                                            value={campaign.state}
                                        />
                                    )}
                                    {campaign.country && (
                                        <SummaryRow
                                            label="Country"
                                            value={campaign.country}
                                        />
                                    )}
                                    {campaign.beneficiaryCount !==
                                        undefined && (
                                        <SummaryRow
                                            label="Beneficiaries"
                                            value={`${campaign.activeBeneficiaryCount ?? 0} active of ${campaign.beneficiaryCount}`}
                                        />
                                    )}
                                    <SummaryRow
                                        label="Booth required"
                                        value={
                                            campaign.boothRequired
                                                ? 'Yes'
                                                : 'No'
                                        }
                                        highlighted={campaign.boothRequired}
                                    />
                                </dl>
                            </CardContent>
                        </Card>
                    </section>
                </div>
            </DashboardLayout>
        </>
    );
}

function CampaignProgress({ campaign }: { campaign: Campaign }) {
    const progress = timelineProgress(campaign);
    const value = timelineLabel(campaign);

    return (
        <div className="grid gap-2">
            <div className="flex justify-between gap-4 text-sm">
                <span>Campaign timeline</span>
                <span className="font-semibold text-muted-foreground">
                    {value}
                </span>
            </div>
            <Progress
                value={progress}
                aria-label={`Campaign timeline: ${value}`}
            />
        </div>
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

function DetailRow({
    label,
    value,
    wide = false,
}: {
    label: string;
    value: string;
    wide?: boolean;
}) {
    return (
        <div className={wide ? 'grid gap-1 sm:col-span-2' : 'grid gap-1'}>
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="font-medium">{value}</dd>
        </div>
    );
}

function SummaryRow({
    label,
    value,
    highlighted = false,
}: {
    label: string;
    value: string;
    highlighted?: boolean;
}) {
    return (
        <div className="flex justify-between gap-4">
            <dt className="text-muted-foreground">{label}</dt>
            <dd
                className={
                    highlighted
                        ? 'font-semibold text-success'
                        : 'text-right font-medium'
                }
            >
                {value}
            </dd>
        </div>
    );
}

function timelineProgress(campaign: Campaign): number {
    if (!campaign.startDate || !campaign.endDate) {
        return 0;
    }

    const start = Date.parse(`${campaign.startDate}T00:00:00Z`);
    const end = Date.parse(`${campaign.endDate}T23:59:59Z`);
    const duration = end - start;

    if (duration <= 0) {
        return campaign.status === 'COMPLETED' ? 100 : 0;
    }

    return Math.min(100, Math.max(0, ((Date.now() - start) / duration) * 100));
}

function timelineLabel(campaign: Campaign): string {
    if (!campaign.startDate || !campaign.endDate) {
        return 'Dates not set';
    }

    if (campaign.status !== 'IN_PROGRESS') {
        return campaign.statusLabel;
    }

    const end = Date.parse(`${campaign.endDate}T23:59:59Z`);
    const daysRemaining = Math.max(
        0,
        Math.ceil((end - Date.now()) / 86_400_000),
    );

    return `${daysRemaining} day${daysRemaining === 1 ? '' : 's'} remaining`;
}

function formatDateRange(campaign: Campaign): string | null {
    if (!campaign.startDate || !campaign.endDate) {
        return null;
    }

    return `${formatDate(campaign.startDate)} – ${formatDate(campaign.endDate)}`;
}

function formatDate(value: string): string {
    return dateFormatter.format(new Date(value));
}
