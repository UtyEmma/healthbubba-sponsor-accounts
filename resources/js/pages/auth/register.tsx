import { Head, Link, router, useForm, useHttp } from '@inertiajs/react';
import type { InertiaFormProps } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';

import InputError from '@/components/input/input-error';
import InputPassword from '@/components/input/password';
import { Button, buttonVariants } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { home, login, register } from '@/routes';
import auth from '@/routes/auth';
import institutionalRegistration from '@/routes/institutional_registration';
import type {
    AccountAvailability,
    AccountSetupAuthentication,
    AccountType,
    InstitutionalRegistrationPageProps,
} from '@/types';

import {
    AccountTypeSelect,
    accountTypeOptions,
} from './partials/account-type-select';
import { AuthFlowShell } from './partials/auth-flow-shell';

type RegistrationStep =
    | 'account-type'
    | 'email'
    | 'identity'
    | 'existing-password'
    | 'member-choice'
    | 'setup';

interface WorkspaceSetupForm {
    account_type: AccountType;
    organization_name: string;
    organization_type: string;
    country_code: string;
    state_code: string;
    official_email: string;
    official_phone: string;
    authorization_confirmed: boolean;
}

interface NewUserRegistrationForm extends WorkspaceSetupForm {
    type: AccountType;
    name: string;
    email: string;
    phone: string;
    job_title: string;
    password: string;
    password_confirmation: string;
}

export default function Register({
    organizationTypes,
    countries,
    states,
    initialAccountType,
    initialEmail,
}: InstitutionalRegistrationPageProps) {
    const [selectedType, setSelectedType] = useState<AccountType | null>(
        initialAccountType,
    );
    const [step, setStep] = useState<RegistrationStep>(
        initialAccountType ? 'email' : 'account-type',
    );
    const [availability, setAvailability] =
        useState<AccountAvailability | null>(null);
    const [setupAuthentication, setSetupAuthentication] =
        useState<AccountSetupAuthentication | null>(null);
    const [requestError, setRequestError] = useState<string | null>(null);

    const availabilityForm = useHttp<
        { account_type: AccountType; email: string },
        AccountAvailability
    >({
        account_type: initialAccountType ?? 'individual',
        email: initialEmail,
    });

    const existingUserForm = useHttp<
        { account_type: AccountType; email: string; password: string },
        AccountSetupAuthentication
    >({
        account_type: initialAccountType ?? 'individual',
        email: initialEmail,
        password: '',
    });

    const newUserForm = useForm<NewUserRegistrationForm>({
        account_type: initialAccountType ?? 'individual',
        type: initialAccountType ?? ('individual' as AccountType),
        name: '',
        email: initialEmail,
        phone: '',
        job_title: '',
        password: '',
        password_confirmation: '',
        organization_name: '',
        organization_type: '',
        country_code: 'NG',
        state_code: '',
        official_email: '',
        official_phone: '',
        authorization_confirmed: false,
    });

    const workspaceForm = useHttp<WorkspaceSetupForm, null>({
        account_type: initialAccountType ?? 'individual',
        organization_name: '',
        organization_type: '',
        country_code: 'NG',
        state_code: '',
        official_email: '',
        official_phone: '',
        authorization_confirmed: false,
    });

    const accountLabel = accountTypeOptions.find(
        (option) => option.value === selectedType,
    )?.label;
    const isExistingIdentity = availability?.status !== 'new_identity';
    const stepNumber =
        step === 'account-type'
            ? 1
            : step === 'email'
              ? 2
              : step === 'identity' || step === 'existing-password'
                ? 3
                : 4;

    function chooseAccountType(value: AccountType) {
        setSelectedType(value);
        availabilityForm.setData('account_type', value);
        existingUserForm.setData('account_type', value);
        newUserForm.setData('type', value);
        workspaceForm.setData('account_type', value);
        setAvailability(null);
    }

    function checkEmail(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setAvailability(null);
        setRequestError(null);

        void availabilityForm
            .post(auth.accountAvailability().url, {
                onSuccess: (response) => {
                    setAvailability(response);
                    newUserForm.setData('email', availabilityForm.data.email);
                    existingUserForm.setData(
                        'email',
                        availabilityForm.data.email,
                    );

                    if (response.status === 'new_identity') {
                        setStep('identity');
                    } else if (response.status !== 'owned_workspace') {
                        setStep('existing-password');
                    }
                },
                onHttpException: () => {
                    setRequestError(
                        'We could not check this account right now. Please try again.',
                    );

                    return true;
                },
                onNetworkError: () =>
                    setRequestError('Check your connection and try again.'),
            })
            .catch(() => undefined);
    }

    function continueFromIdentity(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        newUserForm.clearErrors();
        let invalid = false;

        if (!newUserForm.data.name.trim()) {
            newUserForm.setError('name', 'Enter your full name.');
            invalid = true;
        }

        if (newUserForm.data.password.length < 8) {
            newUserForm.setError('password', 'Use at least 8 characters.');
            invalid = true;
        }

        if (
            newUserForm.data.password !== newUserForm.data.password_confirmation
        ) {
            newUserForm.setError(
                'password_confirmation',
                'The passwords do not match.',
            );
            invalid = true;
        }

        if (selectedType === 'institution' && !newUserForm.data.phone.trim()) {
            newUserForm.setError('phone', 'Enter your phone number.');
            invalid = true;
        }

        if (
            selectedType === 'institution' &&
            !newUserForm.data.job_title.trim()
        ) {
            newUserForm.setError('job_title', 'Enter your role or job title.');
            invalid = true;
        }

        if (!invalid) {
            setStep('setup');
        }
    }

    function authenticateExistingUser(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setRequestError(null);

        void existingUserForm
            .post(auth.accountSetup.authenticate().url, {
                onSuccess: (response) => {
                    setSetupAuthentication(response);
                    setStep(response.canLogin ? 'member-choice' : 'setup');
                },
                onHttpException: () => {
                    setRequestError(
                        'We could not verify this account right now. Please try again.',
                    );

                    return true;
                },
                onNetworkError: () =>
                    setRequestError('Check your connection and try again.'),
            })
            .catch(() => undefined);
    }

    function submitSetup(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (isExistingIdentity) {
            setRequestError(null);
            void workspaceForm
                .post(auth.accountSetup.workspace().url, {
                    onSuccess: () => router.visit(home().url),
                    onHttpException: () => {
                        setRequestError(
                            'We could not create this account right now. Please try again.',
                        );

                        return true;
                    },
                    onNetworkError: () =>
                        setRequestError('Check your connection and try again.'),
                })
                .catch(() => undefined);

            return;
        }

        newUserForm.clearErrors();
        const organizationFields = [
            'organization_name',
            'organization_type',
            'country_code',
            'state_code',
            'official_email',
            'official_phone',
            'authorization_confirmed',
        ];

        newUserForm.post(
            selectedType === 'institution'
                ? institutionalRegistration.store().url
                : register().url,
            {
                preserveScroll: true,
                onError: (errors) => {
                    if (
                        Object.keys(errors).some(
                            (field) => !organizationFields.includes(field),
                        )
                    ) {
                        setStep('identity');
                    }
                },
            },
        );
    }

    const loginUrl = login({
        query: {
            account_type: selectedType ?? undefined,
            email: availabilityForm.data.email || undefined,
        },
    }).url;

    return (
        <>
            <Head title="Create an account" />
            <AuthFlowShell
                title="Create your sponsor account"
                description="Set up the account type you want to use."
                step={stepNumber}
                totalSteps={selectedType === 'institution' ? 6 : 4}
                stepLabel={stepLabel(step)}
                wide={step === 'setup' && selectedType === 'institution'}
            >
                {step === 'account-type' && (
                    <div className="grid gap-4 pt-5">
                        <AccountTypeSelect
                            value={selectedType}
                            onChange={chooseAccountType}
                        />
                        <Button
                            type="button"
                            className="h-11 w-full"
                            disabled={!selectedType}
                            onClick={() => selectedType && setStep('email')}
                        >
                            Continue <ArrowRight className="size-4" />
                        </Button>
                    </div>
                )}

                {step === 'email' && (
                    <form
                        onSubmit={checkEmail}
                        className="grid gap-4 pt-5"
                        noValidate
                    >
                        <BackButton onClick={() => setStep('account-type')}>
                            Change account type
                        </BackButton>
                        <div>
                            <h2 className="text-base font-semibold">
                                What is your email address?
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                We will check whether you already use
                                HealthBubba.
                            </p>
                        </div>
                        <Field
                            label="Email address"
                            error={availabilityForm.errors.email}
                        >
                            <Input
                                value={availabilityForm.data.email}
                                onChange={(event) => {
                                    availabilityForm.setData(
                                        'email',
                                        event.currentTarget.value,
                                    );
                                    availabilityForm.clearErrors('email');
                                    setAvailability(null);
                                }}
                                type="email"
                                autoComplete="email"
                                autoFocus
                                aria-invalid={Boolean(
                                    availabilityForm.errors.email,
                                )}
                            />
                        </Field>
                        {availability?.status === 'owned_workspace' && (
                            <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-950">
                                <p className="font-medium">
                                    An account with this email and account type
                                    already exists.
                                </p>
                                <Link
                                    href={loginUrl}
                                    className="mt-2 inline-flex font-semibold text-primary hover:underline"
                                >
                                    Sign in instead
                                </Link>
                            </div>
                        )}
                        <RequestError message={requestError} />
                        <Button
                            type="submit"
                            className="h-11 w-full"
                            disabled={availabilityForm.processing}
                        >
                            {availabilityForm.processing
                                ? 'Checking…'
                                : 'Continue'}
                            <ArrowRight className="size-4" />
                        </Button>
                    </form>
                )}

                {step === 'identity' && (
                    <form
                        onSubmit={continueFromIdentity}
                        className="grid gap-4 pt-5"
                        noValidate
                    >
                        <BackButton onClick={() => setStep('email')}>
                            Use another email
                        </BackButton>
                        <div>
                            <h2 className="text-base font-semibold">
                                Create your login
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                These credentials will work across every account
                                type you own.
                            </p>
                        </div>
                        <Field
                            label="Full name"
                            error={newUserForm.errors.name}
                        >
                            <Input
                                value={newUserForm.data.name}
                                onChange={(event) =>
                                    newUserForm.setData(
                                        'name',
                                        event.currentTarget.value,
                                    )
                                }
                                autoComplete="name"
                                autoFocus
                            />
                        </Field>
                        <Field label="Email address">
                            <Input
                                value={newUserForm.data.email}
                                readOnly
                                className="bg-muted/40"
                            />
                        </Field>
                        {selectedType === 'institution' && (
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field
                                    label="Phone number"
                                    error={newUserForm.errors.phone}
                                >
                                    <Input
                                        value={newUserForm.data.phone}
                                        onChange={(event) =>
                                            newUserForm.setData(
                                                'phone',
                                                event.currentTarget.value,
                                            )
                                        }
                                        type="tel"
                                        autoComplete="tel"
                                        placeholder="+234"
                                    />
                                </Field>
                                <Field
                                    label="Role / job title"
                                    error={newUserForm.errors.job_title}
                                >
                                    <Input
                                        value={newUserForm.data.job_title}
                                        onChange={(event) =>
                                            newUserForm.setData(
                                                'job_title',
                                                event.currentTarget.value,
                                            )
                                        }
                                        autoComplete="organization-title"
                                    />
                                </Field>
                            </div>
                        )}
                        <Field
                            label="Password"
                            error={newUserForm.errors.password}
                        >
                            <InputPassword
                                value={newUserForm.data.password}
                                onChange={(event) =>
                                    newUserForm.setData(
                                        'password',
                                        event.currentTarget.value,
                                    )
                                }
                                autoComplete="new-password"
                            />
                        </Field>
                        <Field
                            label="Confirm password"
                            error={newUserForm.errors.password_confirmation}
                        >
                            <InputPassword
                                value={newUserForm.data.password_confirmation}
                                onChange={(event) =>
                                    newUserForm.setData(
                                        'password_confirmation',
                                        event.currentTarget.value,
                                    )
                                }
                                autoComplete="new-password"
                            />
                        </Field>
                        <Button type="submit" className="h-11 w-full">
                            Continue <ArrowRight className="size-4" />
                        </Button>
                    </form>
                )}

                {step === 'existing-password' && (
                    <form
                        onSubmit={authenticateExistingUser}
                        className="grid gap-4 pt-5"
                        noValidate
                    >
                        <BackButton onClick={() => setStep('email')}>
                            Use another email
                        </BackButton>
                        <div>
                            <h2 className="text-base font-semibold">
                                Confirm it is you
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {availability?.status === 'member_workspace'
                                    ? `You are already a member of a ${accountLabel?.toLowerCase()} account. Enter your password to sign in or create your own.`
                                    : `Your HealthBubba login exists, but you do not own a ${accountLabel?.toLowerCase()} account.`}
                            </p>
                        </div>
                        <Field label="Email address">
                            <Input
                                value={existingUserForm.data.email}
                                readOnly
                                className="bg-muted/40"
                            />
                        </Field>
                        <Field
                            label="Password"
                            error={existingUserForm.errors.password}
                        >
                            <InputPassword
                                value={existingUserForm.data.password}
                                onChange={(event) =>
                                    existingUserForm.setData(
                                        'password',
                                        event.currentTarget.value,
                                    )
                                }
                                autoComplete="current-password"
                                autoFocus
                            />
                        </Field>
                        <InputError
                            error={existingUserForm.errors.account_type}
                        />
                        <RequestError message={requestError} />
                        <Button
                            type="submit"
                            className="h-11 w-full"
                            disabled={existingUserForm.processing}
                        >
                            {existingUserForm.processing
                                ? 'Verifying…'
                                : 'Verify and continue'}
                            <ArrowRight className="size-4" />
                        </Button>
                    </form>
                )}

                {step === 'member-choice' && setupAuthentication && (
                    <div className="grid gap-4 pt-5">
                        <div>
                            <h2 className="text-base font-semibold">
                                Choose how to continue
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                You already have member access to this account
                                type. You can use it or create an account that
                                you own.
                            </p>
                        </div>
                        {setupAuthentication.loginRedirect && (
                            <Link
                                href={setupAuthentication.loginRedirect}
                                className={buttonVariants({
                                    className: 'h-11 w-full',
                                })}
                            >
                                Sign in to existing workspace
                            </Link>
                        )}
                        <Button
                            type="button"
                            variant="outline"
                            className="h-11 w-full"
                            onClick={() => setStep('setup')}
                        >
                            Create my own {accountLabel?.toLowerCase()} account
                        </Button>
                    </div>
                )}

                {step === 'setup' && (
                    <form
                        onSubmit={submitSetup}
                        className="grid gap-4 pt-5"
                        noValidate
                    >
                        {setupAuthentication?.canLogin && (
                            <BackButton
                                onClick={() => setStep('member-choice')}
                            >
                                Back to account choices
                            </BackButton>
                        )}
                        <div>
                            <h2 className="text-base font-semibold">
                                Set up your {accountLabel?.toLowerCase()}
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {selectedType === 'individual'
                                    ? 'Your personal sponsor workspace is ready to be created.'
                                    : 'Tell us about the organization this workspace belongs to.'}
                            </p>
                        </div>

                        {selectedType !== 'individual' && (
                            <Field
                                label={
                                    selectedType === 'institution'
                                        ? 'Organization / institution name'
                                        : 'Company name'
                                }
                                error={formError(
                                    'organization_name',
                                    isExistingIdentity,
                                    newUserForm,
                                    workspaceForm,
                                )}
                            >
                                <Input
                                    value={
                                        isExistingIdentity
                                            ? workspaceForm.data
                                                  .organization_name
                                            : newUserForm.data.organization_name
                                    }
                                    onChange={(event) =>
                                        isExistingIdentity
                                            ? workspaceForm.setData(
                                                  'organization_name',
                                                  event.currentTarget.value,
                                              )
                                            : newUserForm.setData(
                                                  'organization_name',
                                                  event.currentTarget.value,
                                              )
                                    }
                                    autoComplete="organization"
                                    autoFocus
                                />
                            </Field>
                        )}

                        {selectedType === 'institution' && (
                            <InstitutionFields
                                organizationTypes={organizationTypes}
                                countries={countries}
                                states={states}
                                existing={isExistingIdentity}
                                newUserForm={newUserForm}
                                workspaceForm={workspaceForm}
                            />
                        )}

                        <InputError
                            error={
                                isExistingIdentity
                                    ? workspaceForm.errors.account_type
                                    : newUserForm.errors.type
                            }
                        />
                        <RequestError message={requestError} />
                        <Button
                            type="submit"
                            className="h-11 w-full"
                            disabled={
                                isExistingIdentity
                                    ? workspaceForm.processing
                                    : newUserForm.processing
                            }
                        >
                            {isExistingIdentity
                                ? workspaceForm.processing
                                    ? 'Creating account…'
                                    : 'Create account'
                                : newUserForm.processing
                                  ? 'Creating account…'
                                  : 'Create account'}
                            <ArrowRight className="size-4" />
                        </Button>
                    </form>
                )}

                {step !== 'member-choice' && step !== 'setup' && (
                    <p className="mt-4 text-center text-sm text-muted-foreground">
                        Already have an account ?{' '}
                        <Link
                            href={loginUrl}
                            className="font-medium text-primary"
                        >
                            Sign in
                        </Link>
                    </p>
                )}
            </AuthFlowShell>
        </>
    );
}

function InstitutionFields({
    organizationTypes,
    countries,
    states,
    existing,
    newUserForm,
    workspaceForm,
}: {
    organizationTypes: InstitutionalRegistrationPageProps['organizationTypes'];
    countries: InstitutionalRegistrationPageProps['countries'];
    states: InstitutionalRegistrationPageProps['states'];
    existing: boolean;
    newUserForm: InertiaFormProps<NewUserRegistrationForm>;
    workspaceForm: ReturnType<typeof useHttp<WorkspaceSetupForm, null>>;
}) {
    const data = existing ? workspaceForm.data : newUserForm.data;
    const errors = existing ? workspaceForm.errors : newUserForm.errors;
    const setData = (field: string, value: string | boolean) => {
        if (existing) {
            workspaceForm.setData(
                field as keyof WorkspaceSetupForm,
                value as never,
            );
        } else {
            newUserForm.setData(
                field as keyof NewUserRegistrationForm,
                value as never,
            );
        }
    };

    return (
        <>
            <Field label="Organization type" error={errors.organization_type}>
                <Select
                    value={data.organization_type || ''}
                    onChange={(event) =>
                        setData('organization_type', event.currentTarget.value)
                    }
                >
                    <option value="" disabled>
                        Select organization type
                    </option>
                    {organizationTypes.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </Select>
            </Field>
            <div className="grid gap-4 sm:grid-cols-2">
                <Field label="Country" error={errors.country_code}>
                    <Select
                        value={data.country_code}
                        onChange={(event) =>
                            setData('country_code', event.currentTarget.value)
                        }
                    >
                        {countries.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </Select>
                </Field>
                <Field label="State" error={errors.state_code}>
                    <Select
                        value={data.state_code || ''}
                        onChange={(event) =>
                            setData('state_code', event.currentTarget.value)
                        }
                    >
                        <option value="" disabled>
                            Select state
                        </option>
                        {states.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </Select>
                </Field>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <Field
                    label="Official email address"
                    error={errors.official_email}
                >
                    <Input
                        value={data.official_email}
                        onChange={(event) =>
                            setData('official_email', event.currentTarget.value)
                        }
                        type="email"
                        autoComplete="organization-email"
                    />
                </Field>
                <Field
                    label="Official phone number"
                    error={errors.official_phone}
                >
                    <Input
                        value={data.official_phone}
                        onChange={(event) =>
                            setData('official_phone', event.currentTarget.value)
                        }
                        type="tel"
                        autoComplete="organization-tel"
                        placeholder="+234"
                    />
                </Field>
            </div>
            <label className="flex items-start gap-2 text-sm text-muted-foreground">
                <input
                    type="checkbox"
                    checked={data.authorization_confirmed}
                    onChange={(event) =>
                        setData(
                            'authorization_confirmed',
                            event.currentTarget.checked,
                        )
                    }
                    className="mt-0.5 size-4 rounded border-input accent-primary"
                />
                <span>
                    I confirm that I am authorized to create and manage this
                    account.
                </span>
            </label>
            <InputError error={errors.authorization_confirmed} />
        </>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <label className="grid gap-2 text-sm font-medium">
            {label}
            {children}
            <InputError error={error} />
        </label>
    );
}

function BackButton({
    onClick,
    children,
}: {
    onClick: () => void;
    children: string;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className="flex w-fit items-center gap-1 text-xs font-medium text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft className="size-3.5" /> {children}
        </button>
    );
}

function RequestError({ message }: { message: string | null }) {
    return message ? (
        <p className="text-sm text-destructive" role="alert">
            {message}
        </p>
    ) : null;
}

function stepLabel(step: RegistrationStep): string {
    return {
        'account-type': 'Account type',
        email: 'Email address',
        identity: 'Login details',
        'existing-password': 'Verify your account',
        'member-choice': 'Choose account access',
        setup: 'Account setup',
    }[step];
}

function formError(
    field: 'organization_name',
    existing: boolean,
    newUserForm: InertiaFormProps<NewUserRegistrationForm>,
    workspaceForm: ReturnType<typeof useHttp<WorkspaceSetupForm, null>>,
): string | undefined {
    return existing ? workspaceForm.errors[field] : newUserForm.errors[field];
}
