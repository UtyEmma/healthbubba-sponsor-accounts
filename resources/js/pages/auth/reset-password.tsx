import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import type { FormEvent } from 'react';

import InputError from '@/components/input/input-error';
import InputPassword from '@/components/input/password';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { login } from '@/routes';
import password from '@/routes/password';

import { AuthFlowShell } from './partials/auth-flow-shell';

interface ResetPasswordProps {
    email: string;
    token: string;
}

interface ResetPasswordFormData {
    email: string;
    password: string;
    password_confirmation: string;
    token: string;
}

export default function ResetPassword({ email, token }: ResetPasswordProps) {
    const form = useForm<ResetPasswordFormData>({
        email,
        password: '',
        password_confirmation: '',
        token,
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post(password.update().url, {
            preserveScroll: true,
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    }

    return (
        <>
            <Head title="Reset password" />
            <AuthFlowShell
                title="Choose a new password"
                description="Create a secure password for your account."
                step={1}
                totalSteps={1}
                stepLabel="Set new password"
                showProgress={false}
            >
                <form onSubmit={submit} className="grid gap-4" noValidate>
                    <label
                        className="grid gap-2 text-sm font-medium"
                        htmlFor="email"
                    >
                        Email address
                        <Input
                            id="email"
                            name="email"
                            value={form.data.email}
                            onChange={(event) => {
                                form.setData(
                                    'email',
                                    event.currentTarget.value,
                                );
                                form.clearErrors('email');
                            }}
                            type="email"
                            autoComplete="email"
                            aria-invalid={Boolean(form.errors.email)}
                            aria-describedby={
                                form.errors.email ? 'email-error' : undefined
                            }
                        />
                    </label>
                    <InputError id="email-error" error={form.errors.email} />

                    <label
                        className="grid gap-2 text-sm font-medium"
                        htmlFor="password"
                    >
                        New password
                        <InputPassword
                            id="password"
                            name="password"
                            value={form.data.password}
                            onChange={(event) => {
                                form.setData(
                                    'password',
                                    event.currentTarget.value,
                                );
                                form.clearErrors('password');
                            }}
                            autoComplete="new-password"
                            autoFocus
                            aria-invalid={Boolean(form.errors.password)}
                            aria-describedby={
                                form.errors.password
                                    ? 'password-error'
                                    : undefined
                            }
                        />
                    </label>
                    <InputError
                        id="password-error"
                        error={form.errors.password}
                    />

                    <label
                        className="grid gap-2 text-sm font-medium"
                        htmlFor="password_confirmation"
                    >
                        Confirm new password
                        <InputPassword
                            id="password_confirmation"
                            name="password_confirmation"
                            value={form.data.password_confirmation}
                            onChange={(event) => {
                                form.setData(
                                    'password_confirmation',
                                    event.currentTarget.value,
                                );
                                form.clearErrors('password_confirmation');
                            }}
                            autoComplete="new-password"
                            aria-invalid={Boolean(
                                form.errors.password_confirmation,
                            )}
                            aria-describedby={
                                form.errors.password_confirmation
                                    ? 'password-confirmation-error'
                                    : undefined
                            }
                        />
                    </label>
                    <InputError
                        id="password-confirmation-error"
                        error={form.errors.password_confirmation}
                    />

                    <Button
                        type="submit"
                        className="h-11 w-full"
                        disabled={form.processing}
                    >
                        {form.processing
                            ? 'Resetting password…'
                            : 'Reset password'}
                        <ArrowRight className="size-4" aria-hidden="true" />
                    </Button>

                    <Link
                        href={login().url}
                        className="inline-flex items-center justify-center gap-2 text-sm font-medium text-primary hover:underline"
                    >
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        Back to sign in
                    </Link>
                </form>
            </AuthFlowShell>
        </>
    );
}
