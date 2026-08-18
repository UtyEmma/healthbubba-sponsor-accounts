import { Head, Link } from '@inertiajs/react';
import { ArrowUpRightIcon, MegaphoneIcon, CalendarIcon, Clock } from 'lucide-react';

import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { DashboardLayout } from '@/layouts/dashboard';
import { cn } from '@/lib/utils';
import institutional from '@/routes/institutional';
import type {
    Campaign,
    CampaignStatus,
    InstitutionalCampaignIndexPageProps,
} from '@/types';
import campaigns from '@/routes/campaigns';
import CampaignItem from './partials/campaign-item';



export default function InstitutionalCampaignsPage({
    organization,
    campaigns,
}: InstitutionalCampaignIndexPageProps) {
    return (
        <>
            <Head title="Campaigns" />
            <DashboardLayout>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Campaigns"
                        description={`Review campaigns set up for ${organization.name}.`}
                    />

                    <section
                        className="grid gap-4 pt-6 lg:grid-cols-2"
                        aria-label="Campaigns"
                    >
                        {campaigns.data.map((campaign) => (
                            <CampaignItem
                                key={campaign.id}
                                campaign={campaign}
                            />
                        ))}

                        {campaigns.data.length === 0 && (
                            <Card className="lg:col-span-2">
                                <CardContent className="grid min-h-48 place-items-center p-6 text-center">
                                    <div className="grid max-w-md gap-2">
                                        <MegaphoneIcon className="mx-auto size-8 text-muted-foreground" />
                                        <h2 className="font-semibold">
                                            No campaigns available
                                        </h2>
                                        <p className="text-sm text-muted-foreground">
                                            Campaigns configured for this
                                            workspace will appear here.
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
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
            </DashboardLayout>
        </>
    );
}

