import { Head, router, useForm } from '@inertiajs/react';
import { ArrowRight, Building2Icon, LandmarkIcon, UserIcon } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { BrandMark } from '@/components/brand-mark';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { login } from '@/routes';

export default function Login() {

    const form = useForm({
        email: '',
        password: ''
    })

    function submit() {
        form.post(login().url)
    }

    return (
        <>
            <Head title="Sign in" />
            <main className="relative min-h-screen overflow-hidden bg-white px-5 py-12 sm:px-8">
                <img
                    src="/images/sponsor/login-bg.svg"
                    alt=""
                    className="pointer-events-none absolute inset-x-0 bottom-0 h-[53%] w-full object-cover"
                />
                <div className="relative mx-auto flex w-full max-w-96 flex-col items-center gap-[13px] sm:pt-[70px]">
                    <BrandMark />
                    <header className="text-center">
                        <h1 className="text-2xl leading-8 font-semibold tracking-[-.025em]">Sign in to your portal</h1>
                        <p className="text-sm leading-6 text-muted-foreground">Choose your account type to continue.</p>
                    </header>

                    <div className="mt-0 w-full" >
                        <div className="grid gap-4 pt-3">
                            <label
                                className="grid gap-2 text-sm font-medium"
                                htmlFor="email"
                            >
                                Email address
                                <Input
                                    value={form.data.email}
                                    onChange={e => form.setData('email', e.currentTarget.value)}
                                    type="email"
                                    autoComplete="email"
                                    placeholder="Email Address"
                                    // required
                                />
                            </label>
                            <label
                                className="grid gap-2 text-sm font-medium"
                                htmlFor="password"
                            >
                                Password
                                <Input
                                    value={form.data.password}
                                    onChange={e => form.setData('password', e.currentTarget.value)}
                                    name="password"
                                    type="password"
                                    autoComplete="current-password"
                                    placeholder="Password"
                                    // required
                                    minLength={8}
                                />
                            </label>
                            <Button onClick={submit} className="h-11 w-full">
                                Continue to portal <ArrowRight className='size-4' />
                            </Button>
                            <p className="text-center text-[13px] text-muted-foreground">
                                Forgot password?{' '}
                                <button
                                    type="button"
                                    onClick={() => setResetRequested(true)}
                                    className="font-medium text-information underline decoration-dotted underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                >
                                    Reset here
                                </button>
                            </p>
                        </div>
                    </div>
                </div>
            </main>
        </>
    );
}
