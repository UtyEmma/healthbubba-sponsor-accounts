import { Progress } from '@/components/ui/progress'
import { Campaign } from '@/types';
import React from 'react'

export default function ({campaign}: {campaign: Campaign}) {
    return (
        <>
            <div className="flex justify-between gap-2 text-sm">
                <span className="text-muted-foreground">
                    Beneficiaries reached
                </span>
                <span className="font-medium">
                    {beneficiariesAdded(campaign)} of{' '}
                    {totalAudience(campaign)}
                </span>
            </div>
            <Progress
                className="mt-1"
                value={audienceProgress(campaign)}
            />
        </>
    )
}

function audienceProgress(campaign: Campaign): number {
    const audience = totalAudience(campaign);
    const beneficiaries = beneficiariesAdded(campaign);

    if (audience <= 0) {
        return 0;
    }

    return Math.min(100, Math.max(0, (beneficiaries / audience) * 100));
}

function totalAudience(campaign: Campaign): number {
    const value = Number(campaign.beneficiaryLimit);

    return Number.isFinite(value) ? value : 0;
}

function beneficiariesAdded(campaign: Campaign): number {
    const value = Number(campaign.capacityUsed ?? 0);

    return Number.isFinite(value) ? value : 0;
}

