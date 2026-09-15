import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, CircleCheck } from 'lucide-react';
import type { FormEvent } from 'react';

import InputError from '@/components/input/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { login } from '@/routes';
import password from '@/routes/password';

import { AuthFlowShell } from './partials/auth-flow-shell';

interface ForgotPasswordProps {
    status: string | null;
}

export default function ForgotPassword({ status }: ForgotPasswordProps) {
    const form = useForm({ email: '' });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post(password.email().url, {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Forgot password" />
            <AuthFlowShell
                title="Reset your password"
                description="Enter your email address and we’ll send you a secure reset link."
                step={1}
                totalSteps={1}
                stepLabel="Request reset link"
                showProgress={false}
            >
                <form onSubmit={submit} className="grid gap-4" noValidate>
                    {status && (
                        <div
                            className="flex items-start gap-2 rounded-lg border border-success/30 bg-success/5 p-3 text-sm text-foreground"
                            role="status"
                        >
                            <CircleCheck
                                className="mt-0.5 size-4 shrink-0 text-success"
                                aria-hidden="true"
                            />
                            <p>{status}</p>
                        </div>
                    )}

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
                            autoFocus
                            aria-invalid={Boolean(form.errors.email)}
                            aria-describedby={
                                form.errors.email ? 'email-error' : undefined
                            }
                        />
                    </label>
                    <InputError id="email-error" error={form.errors.email} />

                    <Button
                        type="submit"
                        className="h-11 w-full"
                        disabled={form.processing}
                    >
                        {form.processing
                            ? 'Sending reset link…'
                            : 'Email password reset link'}
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
