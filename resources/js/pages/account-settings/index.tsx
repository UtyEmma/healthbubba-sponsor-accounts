import { Head, usePage } from '@inertiajs/react';
import { Building2Icon, PencilIcon } from 'lucide-react';
import { useState } from 'react';

import { PageHeader } from '@/components/page-header';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { DashboardLayout } from '@/layouts/dashboard';
import type { AccountType } from '@/types';

import { BusinessDetailsDialog } from './partials/business-details-dialog';
import { ChangePasswordDialog } from './partials/change-password-dialog';
import { ProfileInformationDialog } from './partials/profile-information-dialog';
import { SettingsSection } from './partials/settings-section';

const accountTypeLabels: Record<AccountType, string> = {
    individual: 'Individual Sponsor',
    business: 'Business Sponsor',
    institution: 'Institutional Sponsor',
};

export default function AccountSettingsIndex() {
    const { auth, workspace } = usePage().props;
    const [announcement, setAnnouncement] = useState('');
    const hasOrganizationDetails = workspace.type !== 'individual';
    const entityLabel =
        workspace.type === 'business' ? 'Business' : 'Institution';

    return (
        <DashboardLayout>
            <Head title="Account Settings" />

            <div className="w-full max-w-[676px]">
                <PageHeader
                    title="Account Settings"
                    description="Manage your profile, security, and sponsor details."
                />

                <SettingsSection
                    title="Personal Information"
                    description="Your personal information and account security settings."
                >
                    <div className="flex flex-col justify-between gap-4 border-b pb-4 sm:flex-row sm:items-center">
                        <div className="flex items-center gap-3">
                            <div className="relative shrink-0">
                                <Avatar className="size-12">
                                    {auth.user.avatar && (
                                        <AvatarImage
                                            src={auth.user.avatar}
                                            alt={auth.user.name}
                                        />
                                    )}
                                    <AvatarFallback>
                                        {initials(auth.user.name)}
                                    </AvatarFallback>
                                </Avatar>
                                <span className="absolute right-0 bottom-0 flex size-4 items-center justify-center rounded-full border border-border bg-background">
                                    <PencilIcon className="size-2.5 text-muted-foreground" />
                                </span>
                            </div>
                            <div>
                                <p className="text-sm font-medium">
                                    {auth.user.name}
                                </p>
                                <p className="flex items-center gap-2 pt-1 text-[13px] text-muted-foreground">
                                    <Building2Icon className="size-4" />
                                    {accountTypeLabels[workspace.type]}
                                </p>
                            </div>
                        </div>

                        <ProfileInformationDialog
                            user={auth.user}
                            onUpdated={() =>
                                setAnnouncement(
                                    'Your personal information has been updated.',
                                )
                            }
                        />
                    </div>
                    <div className="pt-4">
                        <p className="text-[13px] text-muted-foreground">
                            Email address
                        </p>
                        <p className="pt-2 text-sm font-medium">
                            {auth.user.email}
                        </p>
                    </div>
                </SettingsSection>

                {hasOrganizationDetails && (
                    <SettingsSection
                        title={`${entityLabel} Details`}
                        description={`Manage the information attached to this ${entityLabel.toLowerCase()} sponsor workspace.`}
                    >
                        <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                            <div className="flex min-w-0 items-start gap-3">
                                <span className="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-success-muted text-success">
                                    {workspace.logo ? (
                                        <img
                                            src={workspace.logo}
                                            alt=""
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        <Building2Icon className="size-5" />
                                    )}
                                </span>
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium">
                                        {workspace.name}
                                    </p>
                                    <p className="pt-2 text-[13px] leading-5 text-muted-foreground">
                                        {workspace.description ??
                                            `No ${entityLabel.toLowerCase()} description has been added yet.`}
                                    </p>
                                </div>
                            </div>

                            <BusinessDetailsDialog
                                workspace={workspace}
                                entityLabel={entityLabel}
                                onUpdated={() =>
                                    setAnnouncement(
                                        `${entityLabel} details have been updated.`,
                                    )
                                }
                            />
                        </div>
                    </SettingsSection>
                )}

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
        </DashboardLayout>
    );
}

function initials(name: string): string {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}
