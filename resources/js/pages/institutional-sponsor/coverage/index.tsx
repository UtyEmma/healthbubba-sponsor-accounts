import { Head } from '@inertiajs/react';
import {
    DownloadIcon,
    PlusIcon,
    RefreshCwIcon,
    ArrowUpCircleIcon,
} from 'lucide-react';
import { useState } from 'react';

import { BusinessPortalShell } from '@/components/business-portal-shell';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';

import { institutionalNavigation } from '../partials/institutional-navigation';
import {
    RenewCoverageDialog,
    TopUpCoverageDialog,
    UpgradeCoverageDialog,
} from './partials/coverage-dialogs';

export default function InstitutionalCoveragePage() {
    const [renewOpen, setRenewOpen] = useState(false);
    const [topUpOpen, setTopUpOpen] = useState(false);
    const [upgradeOpen, setUpgradeOpen] = useState(false);
    const [announcement, setAnnouncement] = useState('');

    function complete(message: string) {
        setAnnouncement(message);
        setRenewOpen(false);
        setTopUpOpen(false);
        setUpgradeOpen(false);
    }

    return (
        <>
            <Head title="Coverage" />
            <BusinessPortalShell
                navigation={institutionalNavigation}
                navigationLabel="Institutional sponsor navigation"
            >
                <div className="mx-auto w-full max-w-6xl">
                    <PageHeader
                        title="Coverage"
                        description="Manage purchased coverage, rules, and the transaction ledger."
                        action={
                            <div className="flex gap-3">
                                <Button
                                    variant="outline"
                                    size="compact"
                                    onClick={() => setRenewOpen(true)}
                                >
                                    <RefreshCwIcon className="size-4" />
                                    Renew
                                </Button>
                                <Button
                                    size="compact"
                                    onClick={() => setTopUpOpen(true)}
                                >
                                    <PlusIcon className="size-4" />
                                    Top Up
                                </Button>
                            </div>
                        }
                    />

                    <Tabs defaultValue="overview" className="pt-10">
                        <TabsList>
                            <TabsTrigger value="overview">Overview</TabsTrigger>
                            <TabsTrigger value="rules">Rules</TabsTrigger>
                            <TabsTrigger value="ledger">Ledger (5)</TabsTrigger>
                        </TabsList>
                    </Tabs>

                    <Card className="mt-2">
                        <CardContent className="flex min-h-[96px] flex-col justify-between gap-4 px-6 py-5 sm:flex-row sm:items-center">
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <h2 className="text-base font-semibold">
                                        Community Health Program 2026
                                    </h2>
                                    <Badge variant="success">Active</Badge>
                                </div>
                                <p className="pt-1 text-sm text-muted-foreground">
                                    Shared Coverage Pool · 23 Mar 2026 – 23 Mar
                                    2027
                                </p>
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    onClick={() => setUpgradeOpen(true)}
                                >
                                    <ArrowUpCircleIcon className="size-4" />
                                    Upgrade
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        setAnnouncement(
                                            'Coverage report selected.',
                                        )
                                    }
                                >
                                    <DownloadIcon className="size-4" />
                                    Report
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <section className="grid gap-4 pt-4 lg:grid-cols-[2fr_1fr]">
                        <Card>
                            <CardHeader className="gap-1 px-6 pt-6 pb-3">
                                <CardTitle className="text-base">
                                    Coverage wallet
                                </CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    Purchased, consumed and remaining units.
                                </p>
                            </CardHeader>
                            <CardContent className="grid gap-5 px-6 pt-3 pb-6">
                                <CoverageProgress
                                    label="GP consultations"
                                    value="1258 / 2000 left"
                                    progress={37}
                                />
                                <CoverageProgress
                                    label="Specialist consultations"
                                    value="332 / 500 left"
                                    progress={34}
                                />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="px-6 pt-6 pb-4">
                                <CardTitle className="text-base">
                                    Financial summary
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="px-6 pt-2 pb-6">
                                <dl className="grid gap-4 text-sm">
                                    <SummaryRow
                                        label="Total budget"
                                        value="₦25,000,000"
                                    />
                                    <SummaryRow
                                        label="Consumed"
                                        value="₦9,120,000"
                                    />
                                    <div className="border-t pt-4">
                                        <SummaryRow
                                            label="Remaining"
                                            value="₦15,880,000"
                                            highlighted
                                        />
                                    </div>
                                </dl>
                            </CardContent>
                        </Card>
                    </section>

                    <RenewCoverageDialog
                        open={renewOpen}
                        onOpenChange={setRenewOpen}
                        onComplete={complete}
                    />
                    <TopUpCoverageDialog
                        open={topUpOpen}
                        onOpenChange={setTopUpOpen}
                        onComplete={complete}
                    />
                    <UpgradeCoverageDialog
                        open={upgradeOpen}
                        onOpenChange={setUpgradeOpen}
                        onComplete={complete}
                    />
                    <p className="sr-only" role="status" aria-live="polite">
                        {announcement}
                    </p>
                </div>
            </BusinessPortalShell>
        </>
    );
}

function CoverageProgress({
    label,
    value,
    progress,
}: {
    label: string;
    value: string;
    progress: number;
}) {
    return (
        <div className="grid gap-2">
            <div className="flex justify-between gap-4 text-sm">
                <span>{label}</span>
                <span className="font-semibold text-muted-foreground">
                    {value}
                </span>
            </div>
            <Progress value={progress} aria-label={`${label}: ${value}`} />
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
                        ? 'text-lg font-semibold text-success'
                        : 'font-medium'
                }
            >
                {value}
            </dd>
        </div>
    );
}
