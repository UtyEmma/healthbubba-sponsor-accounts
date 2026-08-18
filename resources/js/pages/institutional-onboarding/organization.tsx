import { Form, Head } from '@inertiajs/react';
import { Building2Icon } from 'lucide-react';

import CompleteInstitutionalOrganizationProfileController from '@/actions/App/Http/Controllers/InstitutionalOnboarding/CompleteInstitutionalOrganizationProfileController';
import { BrandMark } from '@/components/brand-mark';
import InputError from '@/components/input/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { UserAccountMenu } from '@/components/user-account-menu';
import type { InstitutionalOrganizationPageProps } from '@/types';

export default function InstitutionalOrganizationPage({
    organization,
}: InstitutionalOrganizationPageProps) {
    return (
        <main className="relative min-h-screen overflow-hidden bg-background px-5 py-8 sm:px-8 sm:py-12">
            <Head title="Set Up Campaign" />
            <img
                src="/images/sponsor/login-bg.svg"
                alt=""
                className="pointer-events-none absolute inset-x-0 bottom-0 h-[45%] w-full object-cover"
            />

            <div className="relative mx-auto w-full max-w-2xl">
                <header className="flex items-center justify-between gap-4">
                    <BrandMark showName />
                    <UserAccountMenu />
                </header>

                <Card className="mt-10 overflow-hidden shadow-card sm:mt-14">
                    <CardHeader className="flex flex-row gap-3 border-b px-6 py-6 sm:px-8">
                        <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-secondary/10 text-secondary">
                            <Building2Icon className="size-5" />
                        </span>

                        <div className="min-w-0">
                            {/* <p className="text-xs font-semibold tracking-wide text-secondary uppercase">
                                Institutional campaign setup
                            </p> */}
                            <h3 className="truncate text-xl">
                                {organization.name}
                            </h3>
                            <p className="text-sm leading-6 text-muted-foreground">
                                Provide information about your campaign
                            </p>
                        </div>
                    </CardHeader>

                    <Form
                        {...CompleteInstitutionalOrganizationProfileController.form()}
                        options={{ preserveScroll: true }}
                    >
                        {({ errors, processing }) => (
                            <>
                                <CardContent className="grid gap-5 px-6 py-6 sm:grid-cols-2 sm:px-8">
                                    <label className="grid gap-1.5 text-sm font-medium sm:col-span-2">
                                        Campaign
                                        <Input
                                            name="campaign_name"
                                            required
                                            maxLength={255}
                                            placeholder="Campaign name"
                                        />
                                        <InputError
                                            error={errors.campaign_name}
                                        />
                                    </label>

                                    <label className="grid gap-1.5 text-sm font-medium">
                                        Start date
                                        <Input
                                            type="date"
                                            name="start_date"
                                            required
                                        />
                                        <InputError error={errors.start_date} />
                                    </label>

                                    <label className="grid gap-1.5 text-sm font-medium">
                                        End date
                                        <Input
                                            type="date"
                                            name="end_date"
                                            required
                                        />
                                        <InputError error={errors.end_date} />
                                    </label>

                                    <label className="grid gap-1.5 text-sm font-medium">
                                        Campaign city
                                        <Input
                                            name="city"
                                            required
                                            maxLength={120}
                                            autoComplete="address-level2"
                                        />
                                        <InputError error={errors.city} />
                                    </label>

                                    <label className="grid gap-1.5 text-sm font-medium">
                                        Campaign state
                                        <Input
                                            name="state"
                                            required
                                            maxLength={120}
                                            autoComplete="address-level1"
                                        />
                                        <InputError error={errors.state} />
                                    </label>

                                    <label className="grid gap-1.5 text-sm font-medium">
                                        Campaign location
                                        <Input
                                            name="campaign_location"
                                            maxLength={255}
                                            placeholder="Optional"
                                        />
                                        <InputError
                                            error={errors.campaign_location}
                                        />
                                    </label>

                                    <label className="grid gap-1.5 text-sm font-medium">
                                        Target audience
                                        <Input
                                            name="target_audience"
                                            maxLength={255}
                                            placeholder="Optional"
                                        />
                                        <InputError
                                            error={errors.target_audience}
                                        />
                                    </label>

                                    <fieldset className="grid gap-3 sm:col-span-2">
                                        <legend className="text-sm font-medium">
                                            Does this campaign require a
                                            HealthBubba booth?
                                        </legend>

                                        <div className="mt-2 grid gap-3 sm:grid-cols-2">
                                            <BoothOption
                                                value="1"
                                                label="Yes, a booth is required"
                                                defaultChecked={false}
                                            />
                                            <BoothOption
                                                value="0"
                                                label="No booth is required"
                                                defaultChecked={false}
                                            />
                                        </div>
                                        <InputError
                                            error={errors.booth_required}
                                        />
                                    </fieldset>
                                </CardContent>

                                <div className="flex justify-end border-t px-6 py-5 sm:px-8">
                                    <Button type="submit" disabled={processing}>
                                        {processing
                                            ? 'Submitting...'
                                            : 'Create Campaign'}
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </Card>
            </div>
        </main>
    );
}

function BoothOption({
    value,
    label,
    defaultChecked,
}: {
    value: '0' | '1';
    label: string;
    defaultChecked: boolean;
}) {
    return (
        <label className="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border bg-card px-4 py-3 text-sm hover:bg-accent/50 has-checked:border-secondary has-checked:bg-success-muted">
            <input
                type="radio"
                name="booth_required"
                value={value}
                defaultChecked={defaultChecked}
                required
                className="size-4 accent-secondary"
            />
            {label}
        </label>
    );
}
