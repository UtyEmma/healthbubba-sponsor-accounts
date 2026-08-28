import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowRight,
    Building2Icon,
    ChevronLeft,
    LandmarkIcon,
    UserIcon,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';

import { BrandMark } from '@/components/brand-mark';
import InputError from '@/components/input/input-error';
import { Disclose } from '@/components/toggle/disclose';
import { Button } from '@/components/ui/button';
import { FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { AccountTypes } from '@/constants/account';
import { login, register } from '@/routes';
import institutionalRegistration from '@/routes/institutional_registration';
import type { InstitutionalRegistrationPageProps } from '@/types';

import { InstitutionalRegistrationShell } from './partials/institutional-registration-shell';

const accountTypes = [
    {
        name: 'Individual Sponsor',
        description: 'Sponsor care for family & loved ones',
        icon: UserIcon,
        value: AccountTypes.Individual.value,
    },
    {
        name: 'Business Sponsor',
        description: 'Provide healthcare for your employees',
        icon: Building2Icon,
        value: AccountTypes.Business.value,
    },
    {
        name: 'Institutional Sponsor',
        description: 'Fund healthcare access for communities',
        icon: LandmarkIcon,
        value: AccountTypes.Institution.value,
    },
];

type RegistrationView = 'account-type' | 'standard' | 'institutional';

export default function Register({
    organizationTypes,
    countries,
    states,
}: InstitutionalRegistrationPageProps) {
    const [view, setView] = useState<RegistrationView>('account-type');
    const [institutionalStep, setInstitutionalStep] = useState<1 | 2>(1);
    const [selectedType, setSelectedType] = useState(
        AccountTypes.Individual.value,
    );

    const standardForm = useForm({
        type: AccountTypes.Individual.value,
        name: '',
        email: '',
        password: '',
        organization_name: '',
    });

    const institutionalForm = useForm({
        organization_name: '',
        organization_type: '',
        country_code: 'NG',
        state_code: '',
        official_email: '',
        official_phone: '',
        name: '',
        job_title: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
        authorization_confirmed: false,
    });

    function continueFromAccountType() {
        if (selectedType === AccountTypes.Institution.value) {
            setInstitutionalStep(1);
            setView('institutional');

            return;
        }

        standardForm.setData('type', selectedType);
        setView('standard');
    }

    function submitStandard() {
        standardForm.post(register().url, {
            onError: (errors) => {
                if (errors.type) {
                    setView('account-type');
                }
            },
        });
    }

    function continueToOwner(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        institutionalForm.clearErrors();

        const requiredFields = [
            ['organization_name', 'Enter your organization name.'],
            ['organization_type', 'Select an organization type.'],
            ['state_code', 'Select a state.'],
            ['official_email', 'Enter the official email address.'],
            ['official_phone', 'Enter the official phone number.'],
        ] as const;

        let hasErrors = false;

        requiredFields.forEach(([field, message]) => {
            if (!String(institutionalForm.data[field]).trim()) {
                institutionalForm.setError(field, message);
                hasErrors = true;
            }
        });

        if (!hasErrors) {
            setInstitutionalStep(2);
        }
    }

    function submitInstitutional(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        institutionalForm.post(institutionalRegistration.store().url, {
            preserveScroll: true,
            onError: (errors) => {
                const organizationFields = [
                    'organization_name',
                    'organization_type',
                    'country_code',
                    'state_code',
                    'official_email',
                    'official_phone',
                ];

                setInstitutionalStep(
                    organizationFields.some((field) => errors[field]) ? 1 : 2,
                );
            },
        });
    }

    if (view === 'institutional') {
        return (
            <>
                <Head title="Create an Institutional Sponsor Account" />
                <InstitutionalRegistrationShell
                    step={institutionalStep}
                    stepLabel={
                        institutionalStep === 1
                            ? 'Organization details'
                            : 'Account administrator'
                    }
                >
                    {institutionalStep === 1 ? (
                        <form
                            onSubmit={continueToOwner}
                            className="pt-5"
                            noValidate
                        >
                            <div>
                                <h2 className="text-2xl leading-8 font-semibold tracking-[-.025em]">
                                    Tell us about your organization
                                </h2>
                                <p className="mt-1 text-sm leading-6 text-muted-foreground">
                                    The institution funding healthcare access
                                    for communities.
                                </p>
                            </div>

                            <div className="grid gap-3.5 pt-5">
                                <Field>
                                    <FieldLabel>
                                        Organization / institution name
                                    </FieldLabel>
                                    <Input
                                        value={
                                            institutionalForm.data
                                                .organization_name
                                        }
                                        onChange={(event) =>
                                            institutionalForm.setData(
                                                'organization_name',
                                                event.currentTarget.value,
                                            )
                                        }
                                        autoComplete="organization"
                                        aria-invalid={Boolean(
                                            institutionalForm.errors
                                                .organization_name,
                                        )}
                                    />
                                    <InputError
                                        error={
                                            institutionalForm.errors
                                                .organization_name
                                        }
                                    />
                                </Field>

                                <Field>
                                    <FieldLabel>Organization type</FieldLabel>
                                    <Select
                                        value={
                                            institutionalForm.data
                                                .organization_type || null
                                        }
                                        onValueChange={(value) =>
                                            institutionalForm.setData(
                                                'organization_type',
                                                value ?? '',
                                            )
                                        }
                                    >
                                        <SelectTrigger className="h-10 w-full rounded-control px-3 shadow-none">
                                            <SelectValue placeholder="Select organization type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {organizationTypes.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        error={
                                            institutionalForm.errors
                                                .organization_type
                                        }
                                    />
                                </Field>

                                <div className="grid gap-3 sm:grid-cols-2">
                                    <Field>
                                        <FieldLabel>Country</FieldLabel>
                                        <Select
                                            value={
                                                institutionalForm.data
                                                    .country_code
                                            }
                                            onValueChange={(value) =>
                                                institutionalForm.setData(
                                                    'country_code',
                                                    value ?? 'NG',
                                                )
                                            }
                                        >
                                            <SelectTrigger className="h-10 w-full rounded-control px-3 shadow-none">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {countries.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </Field>

                                    <Field>
                                        <FieldLabel>State</FieldLabel>
                                        <Select
                                            value={
                                                institutionalForm.data
                                                    .state_code || null
                                            }
                                            onValueChange={(value) =>
                                                institutionalForm.setData(
                                                    'state_code',
                                                    value ?? '',
                                                )
                                            }
                                        >
                                            <SelectTrigger className="h-10 w-full rounded-control px-3 shadow-none">
                                                <SelectValue placeholder="Select state" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {states.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            error={
                                                institutionalForm.errors
                                                    .state_code
                                            }
                                        />
                                    </Field>
                                </div>

                                <Field>
                                    <FieldLabel>
                                        Official email address
                                    </FieldLabel>
                                    <Input
                                        type="email"
                                        value={
                                            institutionalForm.data
                                                .official_email
                                        }
                                        onChange={(event) =>
                                            institutionalForm.setData(
                                                'official_email',
                                                event.currentTarget.value,
                                            )
                                        }
                                        autoComplete="email"
                                    />
                                    <InputError
                                        error={
                                            institutionalForm.errors
                                                .official_email
                                        }
                                    />
                                </Field>

                                <Field>
                                    <FieldLabel>
                                        Official phone number
                                    </FieldLabel>
                                    <Input
                                        type="tel"
                                        value={
                                            institutionalForm.data
                                                .official_phone
                                        }
                                        onChange={(event) =>
                                            institutionalForm.setData(
                                                'official_phone',
                                                event.currentTarget.value,
                                            )
                                        }
                                        autoComplete="tel"
                                        placeholder="+234"
                                    />
                                    <InputError
                                        error={
                                            institutionalForm.errors
                                                .official_phone
                                        }
                                    />
                                </Field>

                                <Button type="submit" className="mt-1 w-full">
                                    Continue
                                </Button>
                            </div>

                            <AuthFooter />
                        </form>
                    ) : (
                        <form
                            onSubmit={submitInstitutional}
                            className="pt-5"
                            noValidate
                        >
                            <div>
                                <h2 className="max-w-xs text-2xl leading-8 font-semibold tracking-[-.025em]">
                                    Who will administer the account?
                                </h2>
                                <p className="mt-1 text-sm leading-6 text-muted-foreground">
                                    This person manages campaigns, funding and
                                    beneficiaries.
                                </p>
                            </div>

                            <div className="grid gap-3.5 pt-5">
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <Field>
                                        <FieldLabel>Full name</FieldLabel>
                                        <Input
                                            value={institutionalForm.data.name}
                                            onChange={(event) =>
                                                institutionalForm.setData(
                                                    'name',
                                                    event.currentTarget.value,
                                                )
                                            }
                                            autoComplete="name"
                                        />
                                        <InputError
                                            error={
                                                institutionalForm.errors.name
                                            }
                                        />
                                    </Field>
                                    <Field>
                                        <FieldLabel>
                                            Role / job title
                                        </FieldLabel>
                                        <Input
                                            value={
                                                institutionalForm.data.job_title
                                            }
                                            onChange={(event) =>
                                                institutionalForm.setData(
                                                    'job_title',
                                                    event.currentTarget.value,
                                                )
                                            }
                                            autoComplete="organization-title"
                                        />
                                        <InputError
                                            error={
                                                institutionalForm.errors
                                                    .job_title
                                            }
                                        />
                                    </Field>
                                </div>

                                <Field>
                                    <FieldLabel>Email address</FieldLabel>
                                    <Input
                                        type="email"
                                        value={institutionalForm.data.email}
                                        onChange={(event) =>
                                            institutionalForm.setData(
                                                'email',
                                                event.currentTarget.value,
                                            )
                                        }
                                        autoComplete="email"
                                    />
                                    <InputError
                                        error={institutionalForm.errors.email}
                                    />
                                </Field>

                                <Field>
                                    <FieldLabel>Phone number</FieldLabel>
                                    <Input
                                        type="tel"
                                        value={institutionalForm.data.phone}
                                        onChange={(event) =>
                                            institutionalForm.setData(
                                                'phone',
                                                event.currentTarget.value,
                                            )
                                        }
                                        autoComplete="tel"
                                        placeholder="+234"
                                    />
                                    <InputError
                                        error={institutionalForm.errors.phone}
                                    />
                                </Field>

                                <div className="grid gap-3 sm:grid-cols-2">
                                    <Field>
                                        <FieldLabel>Create password</FieldLabel>
                                        <Input
                                            type="password"
                                            value={
                                                institutionalForm.data.password
                                            }
                                            onChange={(event) =>
                                                institutionalForm.setData(
                                                    'password',
                                                    event.currentTarget.value,
                                                )
                                            }
                                            autoComplete="new-password"
                                        />
                                    </Field>
                                    <Field>
                                        <FieldLabel>
                                            Confirm password
                                        </FieldLabel>
                                        <Input
                                            type="password"
                                            value={
                                                institutionalForm.data
                                                    .password_confirmation
                                            }
                                            onChange={(event) =>
                                                institutionalForm.setData(
                                                    'password_confirmation',
                                                    event.currentTarget.value,
                                                )
                                            }
                                            autoComplete="new-password"
                                        />
                                    </Field>
                                </div>
                                <InputError
                                    error={institutionalForm.errors.password}
                                />

                                <label className="flex min-h-[61px] items-start gap-3 rounded-control border border-secondary bg-success-muted/50 px-3 py-3 text-sm leading-5">
                                    <input
                                        type="checkbox"
                                        checked={
                                            institutionalForm.data
                                                .authorization_confirmed
                                        }
                                        onChange={(event) =>
                                            institutionalForm.setData(
                                                'authorization_confirmed',
                                                event.currentTarget.checked,
                                            )
                                        }
                                        className="mt-0.5 size-4 shrink-0 accent-primary"
                                    />
                                    <span>
                                        I confirm that I am authorized to create
                                        and manage this account on behalf of
                                        this organization.
                                    </span>
                                </label>
                                <InputError
                                    error={
                                        institutionalForm.errors
                                            .authorization_confirmed
                                    }
                                />

                                <div className="grid grid-cols-[81px_1fr] gap-3 pt-1">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setInstitutionalStep(1)}
                                    >
                                        <ChevronLeft className="size-4" />
                                        Back
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={institutionalForm.processing}
                                    >
                                        Create account
                                    </Button>
                                </div>
                            </div>

                            <AuthFooter />
                        </form>
                    )}
                </InstitutionalRegistrationShell>
            </>
        );
    }

    return (
        <>
            <Head title="Create an Account" />
            <main className="relative min-h-screen overflow-hidden bg-white px-5 py-12 sm:px-8">
                <img
                    src="/images/sponsor/login-bg.svg"
                    alt=""
                    className="pointer-events-none absolute inset-x-0 bottom-0 h-[53%] w-full object-cover"
                />
                <div className="relative mx-auto flex w-full max-w-96 flex-col items-center gap-[13px] sm:pt-[70px]">
                    <BrandMark />
                    <header className="text-center">
                        <h1 className="text-2xl leading-8 font-semibold tracking-[-.025em]">
                            Create your account
                        </h1>
                        <p className="text-sm leading-6 text-muted-foreground">
                            {view === 'account-type'
                                ? 'Choose your account type to continue.'
                                : 'Setup your account information.'}
                        </p>
                    </header>

                    <div className="w-full">
                        <Disclose show={view === 'account-type'}>
                            <div className="space-y-5">
                                <fieldset className="mt-3 w-full">
                                    <legend className="sr-only">
                                        Account type
                                    </legend>
                                    <div className="mb-2 grid gap-3">
                                        {accountTypes.map(
                                            ({
                                                icon: Icon,
                                                value,
                                                ...type
                                            }) => (
                                                <button
                                                    key={type.name}
                                                    type="button"
                                                    aria-pressed={
                                                        selectedType === value
                                                    }
                                                    onClick={() =>
                                                        setSelectedType(value)
                                                    }
                                                    className={
                                                        selectedType === value
                                                            ? 'flex min-h-[75px] w-full items-center gap-3 rounded-card border-[1.5px] border-secondary bg-success-muted p-4 text-left focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring'
                                                            : 'flex min-h-[75px] w-full items-center gap-3 rounded-card bg-card p-4 text-left shadow-card focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring'
                                                    }
                                                >
                                                    <span
                                                        className={
                                                            selectedType ===
                                                            value
                                                                ? 'flex size-10 shrink-0 items-center justify-center rounded-[9.6px] bg-secondary text-success-foreground'
                                                                : 'flex size-10 shrink-0 items-center justify-center rounded-[9.6px] bg-muted text-gray-500'
                                                        }
                                                    >
                                                        <Icon />
                                                    </span>
                                                    <span>
                                                        <strong className="block text-sm leading-5 font-semibold">
                                                            {type.name}
                                                        </strong>
                                                        <span className="block text-xs leading-4 text-muted-foreground">
                                                            {type.description}
                                                        </span>
                                                    </span>
                                                </button>
                                            ),
                                        )}
                                    </div>
                                </fieldset>

                                <Button
                                    onClick={continueFromAccountType}
                                    className="w-full"
                                >
                                    Get Started{' '}
                                    <ArrowRight className="size-4" />
                                </Button>
                            </div>
                        </Disclose>

                        <Disclose show={view === 'standard'}>
                            <div className="w-full">
                                <Button
                                    variant="link"
                                    onClick={() => setView('account-type')}
                                    className="p-0"
                                >
                                    <ChevronLeft className="size-4" />
                                    Go back
                                </Button>

                                <div className="grid gap-4 pt-3">
                                    <Field>
                                        <FieldLabel>Your Name</FieldLabel>
                                        <Input
                                            value={standardForm.data.name}
                                            onChange={(event) =>
                                                standardForm.setData(
                                                    'name',
                                                    event.currentTarget.value,
                                                )
                                            }
                                            placeholder="Your Name"
                                        />
                                        <InputError
                                            error={standardForm.errors.name}
                                        />
                                    </Field>

                                    {selectedType ===
                                        AccountTypes.Business.value && (
                                        <Field>
                                            <FieldLabel>
                                                Your Company Name
                                            </FieldLabel>
                                            <Input
                                                value={
                                                    standardForm.data
                                                        .organization_name
                                                }
                                                onChange={(event) =>
                                                    standardForm.setData(
                                                        'organization_name',
                                                        event.currentTarget
                                                            .value,
                                                    )
                                                }
                                                placeholder="Your Company Name"
                                            />
                                            <InputError
                                                error={
                                                    standardForm.errors
                                                        .organization_name
                                                }
                                            />
                                        </Field>
                                    )}

                                    <Field>
                                        <FieldLabel>Email Address</FieldLabel>
                                        <Input
                                            value={standardForm.data.email}
                                            onChange={(event) =>
                                                standardForm.setData(
                                                    'email',
                                                    event.currentTarget.value,
                                                )
                                            }
                                            type="email"
                                            autoComplete="email"
                                            placeholder="Email Address"
                                        />
                                        <InputError
                                            error={standardForm.errors.email}
                                        />
                                    </Field>

                                    <Field>
                                        <FieldLabel>Password</FieldLabel>
                                        <Input
                                            value={standardForm.data.password}
                                            onChange={(event) =>
                                                standardForm.setData(
                                                    'password',
                                                    event.currentTarget.value,
                                                )
                                            }
                                            type="password"
                                            autoComplete="current-password"
                                            placeholder="Password"
                                            minLength={8}
                                        />
                                        <InputError
                                            error={standardForm.errors.password}
                                        />
                                    </Field>

                                    <Button
                                        onClick={submitStandard}
                                        className="w-full"
                                        disabled={standardForm.processing}
                                    >
                                        Continue to portal{' '}
                                        <ArrowRight className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        </Disclose>

                        <AuthFooter />
                    </div>
                </div>
            </main>
        </>
    );
}

function Field({ children }: { children: ReactNode }) {
    return <label className="grid gap-1 text-sm font-medium">{children}</label>;
}

function AuthFooter() {
    return (
        <p className="mt-5 text-center text-xs leading-5 text-muted-foreground">
            Already have an account?{' '}
            <Link
                href={login.get().url}
                className="ms-1 text-muted-foreground hover:text-foreground"
            >
                Sign in
            </Link>
        </p>
    );
}
