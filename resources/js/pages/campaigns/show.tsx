import { Head, usePage } from '@inertiajs/react';
import { CalendarDaysIcon, MapPin } from 'lucide-react';

import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { DashboardLayout } from '@/layouts/dashboard';
import { BeneficiariesTable } from '@/pages/sponsor/beneficiaries/partials/beneficiaries-table';
import type {
    Campaign,
    CampaignStatus,
    InstitutionalCampaignShowPageProps,
} from '@/types';
import { AddCampaignBeneficiaryDialog } from './partials/add-campaign-beneficiary-dialog';
import CampaignAudienceProgress from './partials/campaign-audience-progress';
import { CampaignConsultationHistory } from './partials/campaign-consultation-history';
import { CampaignConsultationUsage } from './partials/campaign-consultation-usage';
import { PurchaseQuotaCard } from './partials/purchase-quota-card';

interface CampaignDetailsSharedProps {
    [key: string]: unknown;
    flash: {
        success?: string;
    };
    workspacePermissions: {
        canManage: boolean;
    };
}

const dateFormatter = new Intl.DateTimeFormat('en-NG', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    timeZone: 'UTC',
});

export default function InstitutionalCampaignDetailsPage({
    organization,
    campaign,
    beneficiaries,
    capacity,
    campaignConsultation,
    consultations,
    importResult,
}: InstitutionalCampaignShowPageProps) {
    const { flash, workspacePermissions } =
        usePage<CampaignDetailsSharedProps>().props;
    const location = [campaign.location, campaign.city, campaign.state]
        .filter((value): value is string => Boolean(value))
        .join(', ');
    const campaignDate = formatDateRange(campaign);

    return (
        <>
            <Head title={campaign.name} />
            <DashboardLayout>
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title={campaign.name}
                        description={`Review the campaign configured for ${organization.name}.`}
                        action={
                            workspacePermissions.canManage ? (
                                <div className="flex gap-2">
                                    <AddCampaignBeneficiaryDialog
                                        campaignSlug={campaign.slug}
                                        capacity={capacity}
                                    />

                                    <PurchaseQuotaCard campaign={campaign} />
                                </div>
                            ) : undefined
                        }
                    />

                    {flash.success && (
                        <p className="mt-5 rounded-xl border border-success/20 bg-success-muted px-4 py-3 text-sm text-success">
                            {flash.success}
                        </p>
                    )}

                    {importResult && (
                        <div className="mt-5 rounded-xl border bg-card px-4 py-3 text-sm">
                            <p className="font-medium">
                                Import complete: {importResult.imported}{' '}
                                invited, {importResult.skipped} skipped
                            </p>
                            {importResult.errors.length > 0 && (
                                <ul className="mt-2 list-disc space-y-1 pl-5 text-muted-foreground">
                                    {importResult.errors.map((error) => (
                                        <li key={error.row}>
                                            Row {error.row}:{' '}
                                            {error.errors.join(' ')}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    )}

                    <Tabs defaultValue="overview" className="flex-col pt-5">
                        <TabsList>
                            <TabsTrigger value="overview">Overview</TabsTrigger>
                            <TabsTrigger value="beneficiaries">
                                Beneficiaries
                            </TabsTrigger>
                            <TabsTrigger value="consultations">
                                Consultations
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value={'overview'}>
                            <div className="space-y-5">
                                <Card>
                                    <CardContent className="min-h-24 space-y-4">
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

                                            <div className="flex flex-wrap items-center gap-x-4 gap-y-1 pt-1 text-sm text-muted-foreground">
                                                {campaignDate && (
                                                    <span className="inline-flex shrink-0 items-center gap-1.5">
                                                        <CalendarDaysIcon
                                                            className="size-4 shrink-0"
                                                            aria-hidden="true"
                                                        />
                                                        {campaignDate}
                                                    </span>
                                                )}
                                                {location && (
                                                    <span className="inline-flex items-center gap-1.5">
                                                        <MapPin
                                                            className="size-4 shrink-0"
                                                            aria-hidden="true"
                                                        />

                                                        <span>{location}</span>
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                        <CampaignAudienceProgress
                                            campaign={campaign}
                                        />
                                    </CardContent>
                                </Card>

                                <div className="grid gap-5 md:grid-cols-3">
                                    <div className="md:col-span-2">
                                        <CampaignConsultationUsage
                                            coverage={
                                                campaignConsultation.coverage
                                            }
                                        />
                                    </div>
                                    <div>
                                        <Card>
                                            <CardHeader className="px-6 pt-6 pb-4">
                                                <CardTitle className="text-base">
                                                    Financial summary
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent className="px-6 pt-2 pb-6">
                                                <dl className="grid gap-4 text-sm">
                                                    <SummaryRow
                                                        label="GP consultations"
                                                        value={formatMoney(
                                                            campaignConsultation
                                                                .financialSummary
                                                                .gpSpent,
                                                            campaignConsultation
                                                                .financialSummary
                                                                .currency,
                                                        )}
                                                    />
                                                    <SummaryRow
                                                        label="Specialist consultations"
                                                        value={formatMoney(
                                                            campaignConsultation
                                                                .financialSummary
                                                                .specialistSpent,
                                                            campaignConsultation
                                                                .financialSummary
                                                                .currency,
                                                        )}
                                                    />
                                                    <div className="border-t pt-4">
                                                        <SummaryRow
                                                            label="Total amount spent"
                                                            value={formatMoney(
                                                                campaignConsultation
                                                                    .financialSummary
                                                                    .totalSpent,
                                                                campaignConsultation
                                                                    .financialSummary
                                                                    .currency,
                                                            )}
                                                            highlighted
                                                        />
                                                    </div>
                                                </dl>
                                            </CardContent>
                                        </Card>
                                    </div>
                                </div>
                            </div>
                        </TabsContent>

                        <TabsContent value="beneficiaries">
                            <BeneficiariesTable
                                invitations={beneficiaries}
                                canManage={workspacePermissions.canManage}
                                campaignSlug={campaign.slug}
                            />
                        </TabsContent>

                        <TabsContent value="consultations">
                            <CampaignConsultationHistory
                                consultations={consultations}
                                campaignSlug={campaign.slug}
                            />
                        </TabsContent>
                    </Tabs>
                </div>
            </DashboardLayout>
        </>
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

    return `${formatDate(campaign.startDate)} – ${formatDate(campaign.endDate)}`;
}

function formatDate(value: string): string {
    return dateFormatter.format(new Date(value));
}

function formatMoney(value: string, currency: string): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency,
    }).format(Number(value));
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
                        ? 'text-lg font-semibold text-success'
                        : 'font-medium'
                }
            >
                {value}
            </dd>
        </div>
    );
}
