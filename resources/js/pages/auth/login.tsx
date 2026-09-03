import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowRight, InfoIcon } from 'lucide-react';
import type { FormEvent } from 'react';

import InputError from '@/components/input/input-error';
import InputPassword from '@/components/input/password';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { register } from '@/routes';
import loginRoutes from '@/routes/login';
import password from '@/routes/password';
import type { AccountType, AuthEntryPageProps } from '@/types';

import {
    AccountTypeSelect,
    accountTypeOptions,
} from './partials/account-type-select';
import { AuthFlowShell } from './partials/auth-flow-shell';

interface LoginFormData {
    account_type: AccountType | '';
    email: string;
    password: string;
    remember: boolean;
}

export default function Login({
    initialAccountType,
    initialEmail,
}: AuthEntryPageProps) {
    const form = useForm<LoginFormData>({
        account_type: initialAccountType ?? '',
        email: initialEmail,
        password: '',
        remember: false,
    });

    const selectedType = form.data.account_type || null;
    const accountLabel = accountTypeOptions.find(
        (option) => option.value === selectedType,
    )?.label;
    const setupError = selectedType ? form.errors.account_type : undefined;
    const credentialsError = form.errors.email || form.errors.password;
    const registrationUrl = register({
        query: {
            account_type: selectedType ?? undefined,
            email: form.data.email || undefined,
        },
    }).url;

    function chooseAccountType(accountType: AccountType) {
        form.setData('account_type', accountType);
        form.clearErrors('account_type', 'email', 'password');
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post(loginRoutes.store().url, {
            preserveScroll: true,
            onFinish: () => form.reset('password'),
        });
    }

    return (
        <>
            <Head title="Sign in" />
            <AuthFlowShell
                title="Sign in to your portal"
                description="Choose an account type and enter your credentials."
                step={1}
                totalSteps={1}
                stepLabel="Sign in"
                // wide
                showProgress={false}
            >
                <form onSubmit={submit} className="grid gap-3" noValidate>
                    <div className="grid gap-2">
                        <AccountTypeSelect
                            value={selectedType}
                            onChange={chooseAccountType}
                            // compact
                        />
                        {!selectedType && (
                            <InputError error={form.errors.account_type} />
                        )}
                    </div>

                    <label
                        className="grid gap-2 text-sm font-medium"
                        htmlFor="email"
                    >
                        Email address
                        <Input
                            id="email"
                            value={form.data.email}
                            onChange={(event) => {
                                form.setData(
                                    'email',
                                    event.currentTarget.value,
                                );
                                form.clearErrors('email', 'account_type');
                            }}
                            type="email"
                            autoComplete="email"
                            aria-invalid={Boolean(credentialsError)}
                        />
                    </label>

                    <div className="grid gap-2 text-sm font-medium">
                        <div className="flex items-center justify-between gap-4">
                            <label htmlFor="password">Password</label>
                            <Link
                                href={password.request().url}
                                className="text-xs font-normal text-primary hover:underline"
                            >
                                Forgot password?
                            </Link>
                        </div>
                        <InputPassword
                            id="password"
                            name="password"
                            value={form.data.password}
                            onChange={(event) => {
                                form.setData(
                                    'password',
                                    event.currentTarget.value,
                                );
                                form.clearErrors(
                                    'email',
                                    'password',
                                    'account_type',
                                );
                            }}
                            autoComplete="current-password"
                            aria-invalid={Boolean(credentialsError)}
                        />
                    </div>

                    {credentialsError && (
                        <div
                            className="rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive"
                            role="alert"
                        >
                            <p className="font-medium">
                                Wrong email or password
                            </p>
                            <p className="mt-1 text-xs">
                                Check your credentials and try again.
                            </p>
                        </div>
                    )}

                    {setupError && (
                        <div
                            className="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-950"
                            role="alert"
                        >
                            <div className="flex items-start gap-2">
                                <InfoIcon
                                    className="mt-0.5 size-4 shrink-0"
                                    aria-hidden="true"
                                />
                                <div>
                                    <p className="font-medium">
                                        Set up your {accountLabel} account
                                    </p>
                                    <p className="mt-1 text-xs leading-5">
                                        {setupError}
                                    </p>
                                    <Link
                                        href={registrationUrl}
                                        className="mt-2 inline-flex font-semibold text-primary hover:underline"
                                    >
                                        Set up this account type
                                    </Link>
                                </div>
                            </div>
                        </div>
                    )}

                    <label className="flex items-center gap-2 text-sm text-muted-foreground">
                        <input
                            type="checkbox"
                            checked={form.data.remember}
                            onChange={(event) =>
                                form.setData(
                                    'remember',
                                    event.currentTarget.checked,
                                )
                            }
                            className="size-4 rounded border-input accent-primary"
                        />
                        Keep me signed in
                    </label>

                    <Button
                        type="submit"
                        className="h-11 w-full"
                        disabled={form.processing || !selectedType}
                    >
                        {form.processing ? 'Signing in…' : 'Continue to portal'}
                        <ArrowRight className="size-4" />
                    </Button>

                    <p className="text-center text-sm text-muted-foreground">
                        Don&apos;t have an account?{' '}
                        <Link
                            href={registrationUrl}
                            className="font-medium text-primary hover:underline"
                        >
                            Sign up
                        </Link>
                    </p>
                </form>
            </AuthFlowShell>
        </>
    );
}
