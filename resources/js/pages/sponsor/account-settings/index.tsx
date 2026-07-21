import { Head } from '@inertiajs/react';
import { Building2Icon, PencilIcon } from 'lucide-react';
import { useState } from 'react';

import { PageHeader } from '@/components/page-header';
import { PortalShell } from '@/components/portal-shell';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

import { ChangePasswordDialog } from './partials/change-password-dialog';

export default function AccountSettingsIndex() {
    const [announcement, setAnnouncement] = useState('');

    return (
        <>
            <Head title="Account Settings" />
            <PortalShell>
                <div className="w-full max-w-[676px]">
                    <PageHeader
                        title="Account Settings"
                        description="Manage your profile, security, and notification preferences."
                    />

                    <SettingsSection
                        title="Personal Information"
                        description="Your personal information and account security settings."
                    >
                        <div className="flex items-center gap-3 border-b pb-4">
                            <div className="relative shrink-0">
                                <img
                                    src="/images/sponsor/beneficiary-alexander.png"
                                    alt="Ifeoma Okafor"
                                    className="size-12 rounded-full object-cover"
                                />
                                <span className="absolute right-0 bottom-0 flex size-4 items-center justify-center rounded-full border border-border bg-background">
                                    <PencilIcon className="size-2.5 text-muted-foreground" />
                                </span>
                            </div>
                            <div>
                                <p className="text-sm font-medium">
                                    Ifeoma Okafor
                                </p>
                                <p className="flex items-center gap-2 pt-1 text-[13px] text-muted-foreground">
                                    <Building2Icon className="size-4" />
                                    Individual Sponsor
                                </p>
                            </div>
                        </div>
                        <div className="pt-4">
                            <p className="text-[13px] text-muted-foreground">
                                Email address
                            </p>
                            <p className="pt-2 text-sm font-medium">
                                ifeomaokafor@acme.com
                            </p>
                        </div>
                    </SettingsSection>

                    <SettingsSection
                        title="Security"
                        description="Update your password and account protection."
                    >
                        <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                            <div>
                                <p className="text-sm font-medium">
                                    Change Password
                                </p>
                                <p className="pt-2 text-[13px] text-muted-foreground">
                                    Want to set a more unique password for your
                                    account?
                                </p>
                            </div>
                            <ChangePasswordDialog
                                onChanged={() =>
                                    setAnnouncement(
                                        'Your password has been updated.',
                                    )
                                }
                            />
                        </div>
                    </SettingsSection>

                    <p className="sr-only" role="status" aria-live="polite">
                        {announcement}
                    </p>
                </div>
            </PortalShell>
        </>
    );
}

function SettingsSection({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: React.ReactNode;
}) {
    return (
        <section className="pt-6">
            <Card className="gap-0 bg-muted/30 py-0 shadow-none">
                <CardHeader className="gap-1 px-5 py-3">
                    <CardTitle className="text-sm font-medium">
                        {title}
                    </CardTitle>
                    <CardDescription className="pt-1 text-[13px] leading-[18px]">
                        {description}
                    </CardDescription>
                </CardHeader>
                <CardContent className="rounded-xl border bg-card p-4 shadow-control">
                    {children}
                </CardContent>
            </Card>
        </section>
    );
}
