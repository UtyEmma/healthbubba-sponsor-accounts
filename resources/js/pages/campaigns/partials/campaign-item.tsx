import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import campaigns from '@/routes/campaigns';
import type {
    Campaign,
    CampaignBudgetMetric,
    CampaignConsultationMetric,
    CampaignStatus,
} from '@/types';

const dateFormatter = new Intl.DateTimeFormat('en-NG', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    timeZone: 'UTC',
});

const dateWithoutYearFormatter = new Intl.DateTimeFormat('en-NG', {
    day: 'numeric',
    month: 'short',
    timeZone: 'UTC',
});

export default function CampaignItem({ campaign }: { campaign: Campaign }) {
    const financial = campaign.financial;

    if (!financial) {
        return null;
    }

    const location = [campaign.location, campaign.city, campaign.state]
        .filter((value): value is string => Boolean(value))
        .join(', ');
    const campaignDate = formatDateRange(campaign);
    const ended = campaign.status === 'COMPLETED';
    const gp = financial.consultations.gp;
    const specialist = financial.consultations.specialist;
    const remainingMessage = [
        gp.units > 0 && `${gp.remaining.toLocaleString('en-NG')} GP`,
        specialist.units > 0 &&
            `${specialist.remaining.toLocaleString('en-NG')} specialist consultations`,
    ]
        .filter(Boolean)
        .join(' and ');

    return (
        <Card className="border border-border shadow-none">
            <CardContent className="p-4 sm:p-5">
                <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 className="text-base font-semibold">
                                {campaign.name}
                            </h2>
                            <CampaignStatusBadge
                                status={campaign.status}
                                label={campaign.statusLabel}
                            />
                        </div>
                        {campaign.description && (
                            <p className="mt-2 text-[13px] text-muted-foreground">
                                {campaign.description}
                            </p>
                        )}
                        <div className="mt-2 flex flex-wrap gap-2">
                            {location && (
                                <span className="rounded-full bg-muted px-2.5 py-1 text-[11px] leading-none text-muted-foreground">
                                    {location}
                                </span>
                            )}
                            {campaignDate && (
                                <span className="rounded-full bg-muted px-2.5 py-1 text-[11px] leading-none text-muted-foreground">
                                    {campaignDate}
                                </span>
                            )}
                        </div>
                    </div>
                    <Link
                        href={campaigns.show({ campaign }).url}
                        className={cn(
                            buttonVariants({ variant: 'outline' }),
                            'self-start px-3.5',
                        )}
                    >
                        Open <ArrowRight className="size-4" />
                    </Link>
                </div>

                <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <FinancialCell
                        label="Allocated"
                        value={formatMoney(financial.allocated)}
                    />
                    <FinancialCell
                        label="Utilized"
                        value={formatMoney(financial.utilized)}
                        valueClassName="text-[#2764ed]"
                    />
                    <FinancialCell
                        label={ended ? 'Returned to wallet' : 'Still reserved'}
                        value={formatMoney(
                            ended ? financial.returned : financial.reserved,
                        )}
                        valueClassName="text-[#168437]"
                    />
                    <FinancialCell
                        label="Beneficiaries"
                        value={String(campaign.activeBeneficiaryCount ?? 0)}
                    />
                </div>

                <div className="mt-4 grid gap-x-5 gap-y-3 border-t pt-4 lg:grid-cols-2">
                    {gp.units > 0 && (
                        <ConsultationProgress
                            label="GP consultations"
                            metric={gp}
                        />
                    )}
                    {specialist.units > 0 && (
                        <ConsultationProgress
                            label="Specialist"
                            metric={specialist}
                        />
                    )}
                    {number(financial.budgets.medication.allocated) > 0 && (
                        <BudgetProgress
                            label="Medication budget"
                            metric={financial.budgets.medication}
                        />
                    )}
                    {number(financial.budgets.laboratory.allocated) > 0 && (
                        <BudgetProgress
                            label="Laboratory budget"
                            metric={financial.budgets.laboratory}
                        />
                    )}
                </div>

                <p className="mt-4 text-xs text-subtle">
                    {financial.utilizationPercentage}% of allocation utilized
                    {remainingMessage && ` · ${remainingMessage} remaining`}
                </p>
            </CardContent>
        </Card>
    );
}

function FinancialCell({
    label,
    value,
    valueClassName,
}: {
    label: string;
    value: string;
    valueClassName?: string;
}) {
    return (
        <div className="rounded-xl border px-3 py-3">
            <div className="text-[11px] text-muted-foreground">{label}</div>
            <div className={cn('mt-1 text-sm font-semibold', valueClassName)}>
                {value}
            </div>
        </div>
    );
}

function ConsultationProgress({
    label,
    metric,
}: {
    label: string;
    metric: CampaignConsultationMetric;
}) {
    const utilized = Math.max(0, metric.units - metric.remaining);

    return (
        <ProgressRow
            label={`${label} · ${formatMoney(metric.unitFee)} each`}
            value={`${metric.remaining.toLocaleString('en-NG')} / ${metric.units.toLocaleString('en-NG')} left`}
            percentage={metric.units > 0 ? (utilized / metric.units) * 100 : 0}
        />
    );
}

function BudgetProgress({
    label,
    metric,
}: {
    label: string;
    metric: CampaignBudgetMetric;
}) {
    const allocated = number(metric.allocated);

    return (
        <ProgressRow
            label={label}
            value={`${formatMoney(metric.remaining)} / ${formatMoney(metric.allocated)} left`}
            percentage={
                allocated > 0 ? (number(metric.used) / allocated) * 100 : 0
            }
        />
    );
}

function ProgressRow({
    label,
    value,
    percentage,
}: {
    label: string;
    value: string;
    percentage: number;
}) {
    return (
        <div>
            <div className="mb-2 flex items-center justify-between gap-4 text-xs">
                <span>{label}</span>
                <span className="text-muted-foreground">{value}</span>
            </div>
            <div className="h-1.5 overflow-hidden rounded-full bg-muted">
                <div
                    className="h-full rounded-full bg-[#35b847]"
                    style={{
                        width: `${Math.min(100, Math.max(0, percentage))}%`,
                    }}
                />
            </div>
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

    if (status === 'PAUSED') {
        return <Badge className="bg-warning-muted text-warning">{label}</Badge>;
    }

    return <Badge variant="secondary">{label}</Badge>;
}

function formatDateRange(campaign: Campaign): string | null {
    if (!campaign.startDate || !campaign.endDate) {
        return null;
    }

    const start = new Date(campaign.startDate);
    const end = new Date(campaign.endDate);
    const formattedStart =
        start.getUTCFullYear() === end.getUTCFullYear()
            ? dateWithoutYearFormatter.format(start)
            : dateFormatter.format(start);

    return `${formattedStart} – ${dateFormatter.format(end)}`;
}

function number(value: string): number {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : 0;
}

function formatMoney(value: string): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        maximumFractionDigits: 0,
    }).format(number(value));
}
