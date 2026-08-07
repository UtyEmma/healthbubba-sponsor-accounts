import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowRight,
    Building2Icon,
    ChevronLeft,
    LandmarkIcon,
    UserIcon,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import type { FormEvent } from 'react';

import { BrandMark } from '@/components/brand-mark';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { login, register } from '@/routes';
import { Disclose } from '@/components/toggle/disclose';
import { FieldLabel } from '@/components/ui/field';
import InputError from '@/components/input/input-error';
import { AccountTypes } from '@/constants/account';

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

export default function Login() {
    const [step, setStep] = useState(1);

    const form = useForm({
        type: AccountTypes.Individual.value,
        name: '',
        email: '',
        password: '',
        organization_name: '',
    });

    function submit() {
        form.post(register().url);
    }

    useEffect(() => {
        if (form.errors.type) setStep(1);
    }, [form.errors]);

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
                            {step == 1 &&
                                'Choose your account type to continue.'}
                            {step == 2 && 'Setup your account information.'}
                        </p>
                    </header>

                    <div className="w-full">
                        <Disclose show={step == 1}>
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
                                                        form.data.type === value
                                                    }
                                                    onClick={() =>
                                                        form.setData(
                                                            'type',
                                                            value,
                                                        )
                                                    }
                                                    className={
                                                        form.data.type === value
                                                            ? 'flex min-h-[75px] w-full items-center gap-3 rounded-card border-[1.5px] border-secondary bg-success-muted p-4 text-left focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring'
                                                            : 'flex min-h-[75px] w-full items-center gap-3 rounded-card bg-card p-4 text-left shadow-card focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring'
                                                    }
                                                >
                                                    <span
                                                        className={
                                                            form.data.type ===
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
                                    <InputError error={form.errors.type} />
                                </fieldset>

                                <div>
                                    <Button
                                        onClick={() => setStep(2)}
                                        className="w-full"
                                    >
                                        Get Started{' '}
                                        <ArrowRight className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        </Disclose>

                        <Disclose show={step == 2}>
                            <div className="mt-0 w-full">
                                <div>
                                    <Button
                                        variant="link"
                                        onClick={() => setStep(1)}
                                        className="p-0"
                                    >
                                        <ChevronLeft className="size-4" />
                                        Go back
                                    </Button>
                                </div>
                                <div className="grid gap-4 pt-3">
                                    <div className="space-y-1">
                                        <FieldLabel className="text-sm font-medium">
                                            Your Name
                                        </FieldLabel>
                                        <Input
                                            value={form.data.name}
                                            onChange={(e) =>
                                                form.setData(
                                                    'name',
                                                    e.currentTarget.value,
                                                )
                                            }
                                            placeholder="Your Name"
                                        />
                                        <InputError error={form.errors.name} />
                                    </div>

                                    <Disclose
                                        as="div"
                                        show={
                                            form.data.type ==
                                            AccountTypes.Business.value
                                        }
                                        className="space-y-1"
                                    >
                                        <FieldLabel className="text-sm font-medium">
                                            Your Company Name
                                        </FieldLabel>
                                        <Input
                                            value={form.data.organization_name}
                                            onChange={(e) =>
                                                form.setData(
                                                    'organization_name',
                                                    e.currentTarget.value,
                                                )
                                            }
                                            placeholder="Your Company Name"
                                        />
                                        <InputError
                                            error={
                                                form.errors.organization_name
                                            }
                                        />
                                    </Disclose>

                                    <Disclose
                                        as="div"
                                        show={
                                            form.data.type ==
                                            AccountTypes.Institution.value
                                        }
                                        className="space-y-1"
                                    >
                                        <FieldLabel className="text-sm font-medium">
                                            Your Organization Name
                                        </FieldLabel>
                                        <Input
                                            value={form.data.organization_name}
                                            onChange={(e) =>
                                                form.setData(
                                                    'organization_name',
                                                    e.currentTarget.value,
                                                )
                                            }
                                            placeholder="Your Organization Name"
                                        />
                                        <InputError
                                            error={
                                                form.errors.organization_name
                                            }
                                        />
                                    </Disclose>

                                    <div className="space-y-2">
                                        <FieldLabel className="text-sm font-medium">
                                            Email Address
                                        </FieldLabel>
                                        <Input
                                            value={form.data.email}
                                            onChange={(e) =>
                                                form.setData(
                                                    'email',
                                                    e.currentTarget.value,
                                                )
                                            }
                                            type="email"
                                            autoComplete="email"
                                            placeholder="Email Address"
                                        />
                                        <InputError error={form.errors.email} />
                                    </div>

                                    <div>
                                        <FieldLabel
                                            className="grid gap-2 text-sm font-medium"
                                            htmlFor="password"
                                        >
                                            Password
                                        </FieldLabel>
                                        <Input
                                            value={form.data.password}
                                            onChange={(e) =>
                                                form.setData(
                                                    'password',
                                                    e.currentTarget.value,
                                                )
                                            }
                                            type="password"
                                            autoComplete="current-password"
                                            placeholder="Password"
                                            minLength={8}
                                        />
                                        <InputError
                                            error={form.errors.password}
                                        />
                                    </div>
                                    <Button onClick={submit} className="w-full">
                                        Continue to portal{' '}
                                        <ArrowRight className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        </Disclose>

                        <p className="mt-4 text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <Link
                                href={login.get().url}
                                className="ms-1 font-medium text-information underline decoration-dotted underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                            >
                                Login
                            </Link>
                        </p>
                    </div>
                </div>
            </main>
        </>
    );
}
