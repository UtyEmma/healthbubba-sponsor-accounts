import { Head, usePage } from '@inertiajs/react';
import { CalendarDaysIcon, MapPin } from 'lucide-react';

import StoreCampaignBeneficiaryController from '@/actions/App/Http/Controllers/InstitutionalCampaigns/StoreCampaignBeneficiaryController';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { DashboardLayout } from '@/layouts/dashboard';
import { AddBeneficiaryDialog } from '@/pages/sponsor/beneficiaries/partials/add-beneficiary-dialog';
import { BeneficiariesTable } from '@/pages/sponsor/beneficiaries/partials/beneficiaries-table';
import type {
    Campaign,
    CampaignStatus,
    InstitutionalCampaignShowPageProps,
} from '@/types';
import { CampaignConsultationHistory } from './partials/campaign-consultation-history';
import { CampaignConsultationUsage } from './partials/campaign-consultation-usage';
import CampaignAudienceProgress from './partials/campaign-audience-progress';
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
    coverage,
    consultations,
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

                                <div className='flex gap-2'>
                                    <AddBeneficiaryDialog
                                        form={StoreCampaignBeneficiaryController.form(
                                            campaign.slug,
                                        )}
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

                    <Tabs defaultValue="overview" className="pt-5 flex-col">
                        <TabsList >
                            <TabsTrigger value="overview">Overview</TabsTrigger>
                            <TabsTrigger value="beneficiaries">Beneficiaries</TabsTrigger>
                            <TabsTrigger value="consultations">Consultations</TabsTrigger>
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
                                        <CampaignAudienceProgress campaign={campaign} />
                                    </CardContent>
                                </Card>

                                <CampaignConsultationUsage coverage={coverage} />
                            </div>
                        </TabsContent>

                        <TabsContent value="beneficiaries">
                            <BeneficiariesTable
                                invitations={beneficiaries}
                                canManage={false}
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
