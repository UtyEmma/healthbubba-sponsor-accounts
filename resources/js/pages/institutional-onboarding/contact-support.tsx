import { Head, Link, usePage } from '@inertiajs/react';
import { CheckCircle2Icon, MailIcon } from 'lucide-react';

import { BrandMark } from '@/components/brand-mark';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { UserAccountMenu } from '@/components/user-account-menu';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import type { InstitutionalSupportPageProps } from '@/types';

export default function InstitutionalContactSupportPage({
    organization,
    campaign,
    supportEmail,
    supportMailtoUrl,
}: InstitutionalSupportPageProps) {
    const { flash } = usePage().props;
    const campaignArea = [campaign?.city, campaign?.state, campaign?.country]
        .filter((value): value is string => Boolean(value))
        .join(', ');

    return (
        <main className="min-h-screen bg-muted/40 px-5 py-8 sm:px-8 sm:py-12">
            <Head title="Contact Support" />
            <div className="mx-auto w-full max-w-2xl">
                <header className="flex items-center justify-between gap-4">
                    <BrandMark showName />
                    <UserAccountMenu />
                </header>

                <Card className="mt-10 overflow-hidden text-center shadow-card sm:mt-16">
                    <CardHeader className="items-center gap-3 border-b px-6 py-7 sm:px-8">
                        <span className="flex size-12 items-center justify-center rounded-full bg-success-muted text-success">
                            <CheckCircle2Icon className="size-6" />
                        </span>
                        <CardTitle className="text-xl">
                            Campaign submitted
                        </CardTitle>
                        <p className="max-w-lg text-sm leading-6 text-muted-foreground">
                            Contact HealthBubba Support to complete campaign and
                            subscription setup for {organization.name}. Portal
                            access will unlock once an active or trialing
                            subscription is assigned.
                        </p>
                    </CardHeader>

                    <CardContent className="grid gap-6 px-6 py-7 sm:px-8">
                        {flash.success && (
                            <p className="rounded-xl border border-success/20 bg-success-muted px-4 py-3 text-sm text-success">
                                {flash.success}
                            </p>
                        )}

                        <dl className="grid gap-3 rounded-2xl border bg-background p-5 text-left text-sm sm:grid-cols-2">
                            <Detail
                                label="Organization"
                                value={organization.name}
                            />
                            {campaignArea && (
                                <Detail
                                    label="Campaign city/state"
                                    value={campaignArea}
                                />
                            )}
                            {campaign && (
                                <>
                                    <Detail
                                        label="Campaign"
                                        value={campaign.name}
                                    />
                                    {campaign.startDate && (
                                        <Detail
                                            label="Campaign start date"
                                            value={formatCampaignDate(
                                                campaign.startDate,
                                            )}
                                        />
                                    )}
                                    {campaign.endDate && (
                                        <Detail
                                            label="Campaign end date"
                                            value={formatCampaignDate(
                                                campaign.endDate,
                                            )}
                                        />
                                    )}
                                    {campaign.location && (
                                        <Detail
                                            label="Campaign location"
                                            value={campaign.location}
                                        />
                                    )}
                                    {campaign.targetAudience && (
                                        <Detail
                                            label="Target audience"
                                            value={campaign.targetAudience}
                                        />
                                    )}
                                    <Detail
                                        label="Booth required"
                                        value={
                                            campaign.boothRequired
                                                ? 'Yes'
                                                : 'No'
                                        }
                                    />
                                </>
                            )}
                        </dl>

                        <div className="flex flex-col justify-center gap-3 sm:flex-row">
                            <a
                                href={supportMailtoUrl}
                                className={buttonVariants()}
                            >
                                <MailIcon className="size-4" />
                                Email {supportEmail}
                            </a>
                            <Link
                                href={home()}
                                className={cn(
                                    buttonVariants({ variant: 'outline' }),
                                )}
                            >
                                Check Access
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </main>
    );
}

function formatCampaignDate(value: string): string {
    return new Intl.DateTimeFormat('en-NG', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(value));
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div className="grid gap-1">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="font-medium">{value}</dd>
        </div>
    );
}
