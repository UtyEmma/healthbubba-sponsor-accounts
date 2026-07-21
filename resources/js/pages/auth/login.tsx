import { Head, router } from '@inertiajs/react';
import { Building2Icon, LandmarkIcon, UserIcon } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { BrandMark } from '@/components/brand-mark';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';

const accountTypes = [
    {
        name: 'Individual Sponsor',
        description: 'Sponsor care for family & loved ones',
        icon: UserIcon,
    },
    {
        name: 'Business Sponsor',
        description: 'Provide healthcare for your employees',
        icon: Building2Icon,
    },
    {
        name: 'Institutional Sponsor',
        description: 'Fund healthcare access for communities',
        icon: LandmarkIcon,
    },
];

export default function Login() {
    const [selectedType, setSelectedType] = useState(0);
    const [resetRequested, setResetRequested] = useState(false);

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        router.visit(dashboard.url());
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
                        <h1 className="text-2xl leading-8 font-semibold tracking-[-.025em]">
                            Sign in to your portal
                        </h1>
                        <p className="text-sm leading-6 text-muted-foreground">
                            Choose your account type to continue.
                        </p>
                    </header>

                    <fieldset className="mt-3 w-full">
                        <legend className="sr-only">Account type</legend>
                        <div className="grid gap-3">
                            {accountTypes.map(
                                ({ icon: Icon, ...type }, index) => (
                                    <button
                                        key={type.name}
                                        type="button"
                                        aria-pressed={selectedType === index}
                                        onClick={() => setSelectedType(index)}
                                        className={
                                            selectedType === index
                                                ? 'flex min-h-[75px] w-full items-center gap-3 rounded-card border-[1.5px] border-secondary bg-success-muted p-4 text-left focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring'
                                                : 'flex min-h-[75px] w-full items-center gap-3 rounded-card bg-card p-4 text-left shadow-card focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring'
                                        }
                                    >
                                        <span
                                            className={
                                                selectedType === index
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

                    <form className="mt-0 w-full" onSubmit={submit}>
                        <div className="grid gap-4 pt-3">
                            <label
                                className="grid gap-2 text-sm font-medium"
                                htmlFor="email"
                            >
                                Email address
                                <Input
                                    id="email"
                                    name="email"
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
                                    id="password"
                                    name="password"
                                    type="password"
                                    autoComplete="current-password"
                                    placeholder="Password"
                                    // required
                                    minLength={8}
                                />
                            </label>
                            <Button type="submit" className="h-11 w-full">
                                Continue to portal
                                <img
                                    src="/images/sponsor/arrow-right.svg"
                                    alt=""
                                    className="size-4"
                                />
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
                            {resetRequested && (
                                <p
                                    role="status"
                                    className="text-center text-xs text-success"
                                >
                                    Password reset instructions will be sent to
                                    the email address above.
                                </p>
                            )}
                        </div>
                    </form>
                </div>
            </main>
        </>
    );
}
