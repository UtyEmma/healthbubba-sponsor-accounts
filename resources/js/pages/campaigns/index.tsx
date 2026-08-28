import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    BriefcaseBusiness,
    Plus,
    UsersRound,
    WalletCards,
} from 'lucide-react';
import { useState } from 'react';

import { Button, buttonVariants } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DashboardLayout } from '@/layouts/dashboard';
import { cn } from '@/lib/utils';
import type {
    CampaignIndexSummary,
    InstitutionalCampaignIndexPageProps,
} from '@/types';
import CampaignItem from './partials/campaign-item';
import { CreateCampaignDialog } from './partials/create-campaign-dialog';

export default function InstitutionalCampaignsPage({
    campaigns,
    summary,
    creation,
}: InstitutionalCampaignIndexPageProps) {
    const { workspacePermissions } = usePage().props;
    const [creationOpen, setCreationOpen] = useState(false);
    const current = campaigns.data.filter(
        (campaign) => campaign.status !== 'COMPLETED',
    );
    const ended = campaigns.data.filter(
        (campaign) => campaign.status === 'COMPLETED',
    );

    return (
        <>
            <Head title="Campaigns" />
            <DashboardLayout>
                <div className="w-full max-w-[1120px]">
                    <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">
                                Campaigns
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Each campaign reserves money from your wallet
                                and funds its own enrolled beneficiaries.
                            </p>
                        </div>
                        {workspacePermissions.canManage && (
                            <Button
                                className="self-start"
                                onClick={() => setCreationOpen(true)}
                            >
                                <Plus className="size-4" />
                                Create campaign
                            </Button>
                        )}
                    </div>

                    <SummaryCards summary={summary} />

                    <section className="mt-4 space-y-4" aria-label="Campaigns">
                        {current.map((campaign) => (
                            <CampaignItem
                                key={campaign.id}
                                campaign={campaign}
                            />
                        ))}

                        {current.length === 0 && ended.length === 0 && (
                            <Card className="border border-border shadow-none">
                                <CardContent className="grid min-h-40 place-items-center p-6 text-center">
                                    <div className="max-w-md">
                                        <h2 className="font-semibold">
                                            No campaigns yet
                                        </h2>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            Create a campaign to reserve
                                            healthcare benefits for the
                                            communities you serve.
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {ended.length > 0 && (
                            <div className="pt-1">
                                <h2 className="mb-4 text-sm font-semibold text-muted-foreground">
                                    Ended campaigns
                                </h2>
                                <div className="space-y-4">
                                    {ended.map((campaign) => (
                                        <CampaignItem
                                            key={campaign.id}
                                            campaign={campaign}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </section>

                    {(campaigns.links.prev || campaigns.links.next) && (
                        <nav
                            aria-label="Campaign pagination"
                            className="flex justify-end gap-3 pt-6"
                        >
                            {campaigns.links.prev && (
                                <Link
                                    href={campaigns.links.prev}
                                    className={buttonVariants({
                                        variant: 'outline',
                                        size: 'compact',
                                    })}
                                >
                                    Previous
                                </Link>
                            )}
                            {campaigns.links.next && (
                                <Link
                                    href={campaigns.links.next}
                                    className={buttonVariants({
                                        variant: 'outline',
                                        size: 'compact',
                                    })}
                                >
                                    Next
                                </Link>
                            )}
                        </nav>
                    )}
                </div>

                {workspacePermissions.canManage && (
                    <CreateCampaignDialog
                        open={creationOpen}
                        onOpenChange={setCreationOpen}
                        configuration={creation}
                    />
                )}
            </DashboardLayout>
        </>
    );
}

function SummaryCards({ summary }: { summary: CampaignIndexSummary }) {
    const cards = [
        {
            label: 'Available balance',
            value: formatMoney(summary.availableBalance),
            note: 'Not yet allocated',
            icon: WalletCards,
            iconClassName: 'bg-success-muted text-success',
        },
        {
            label: 'Allocated balance',
            value: formatMoney(summary.allocatedBalance),
            note: `Reserved by ${summary.allocatedCampaigns} ${summary.allocatedCampaigns === 1 ? 'campaign' : 'campaigns'}`,
            icon: BriefcaseBusiness,
            iconClassName: 'bg-information/10 text-information',
        },
        {
            label: 'Utilized',
            value: formatMoney(summary.utilized),
            note: 'Actually used by beneficiaries',
            icon: Activity,
            iconClassName: 'bg-muted text-foreground',
        },
        {
            label: 'Enrolled beneficiaries',
            value: summary.enrolledBeneficiaries.toLocaleString('en-NG'),
            note: 'Across all campaigns',
            icon: UsersRound,
            iconClassName: 'bg-muted text-foreground',
        },
    ];

    return (
        <section
            className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
            aria-label="Campaign financial summary"
        >
            {cards.map((card) => {
                const Icon = card.icon;

                return (
                    <Card
                        key={card.label}
                        className="border border-border shadow-none"
                    >
                        <CardContent className="flex items-center gap-3 p-4">
                            <span
                                className={cn(
                                    'grid size-10 shrink-0 place-items-center rounded-xl',
                                    card.iconClassName,
                                )}
                            >
                                <Icon className="size-5" />
                            </span>
                            <div className="min-w-0">
                                <div className="text-[11px] text-muted-foreground">
                                    {card.label}
                                </div>
                                <div className="mt-0.5 truncate text-xl font-semibold">
                                    {card.value}
                                </div>
                                <div className="mt-0.5 truncate text-[10px] text-subtle">
                                    {card.note}
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                );
            })}
        </section>
    );
}

function formatMoney(value: string): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 0,
    }).format(Number(value));
}
