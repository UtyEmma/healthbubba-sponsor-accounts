import { Head, Link, useForm } from '@inertiajs/react';
import { InfoIcon } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import type { FormEvent } from 'react';

import InputError from '@/components/input/input-error';
import { Button } from '@/components/ui/button';
import { FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { login } from '@/routes';
import accountVerification from '@/routes/account_verification';
import type {
    AccountVerificationPageProps,
    VerificationChannel,
} from '@/types';

import { InstitutionalRegistrationShell } from './partials/institutional-registration-shell';

export default function InstitutionalVerification({
    verification,
}: AccountVerificationPageProps) {
    const [selectedChannel, setSelectedChannel] = useState<VerificationChannel>(
        verification.challenge?.channel ?? 'email',
    );
    const [editingContact, setEditingContact] = useState(false);

    const sendForm = useForm({ channel: selectedChannel });
    const verifyForm = useForm({
        channel: verification.challenge?.channel ?? selectedChannel,
        code: '',
    });
    const contactForm = useForm({
        email: verification.email,
        phone: verification.phone,
    });

    const challenge = verification.challenge;

    function sendCode(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        sendForm.transform(() => ({ channel: selectedChannel }));
        sendForm.post(accountVerification.send().url, {
            preserveScroll: true,
        });
    }

    function verifyCode(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        verifyForm.transform((data) => ({
            ...data,
            channel: challenge?.channel ?? selectedChannel,
            code: data.code.replace(/\D/g, '').slice(0, 6),
        }));
        verifyForm.post(accountVerification.verify().url, {
            preserveScroll: true,
        });
    }

    function resendCode() {
        if (!challenge) {
            return;
        }

        sendForm.transform(() => ({ channel: challenge.channel }));
        sendForm.post(accountVerification.send().url, {
            preserveScroll: true,
        });
    }

    function updateContact(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        contactForm.patch(accountVerification.contact.update().url, {
            preserveScroll: true,
            onSuccess: () => setEditingContact(false),
        });
    }

    return (
        <>
            <Head title="Verify your Institutional Sponsor Account" />
            <InstitutionalRegistrationShell step={3} stepLabel="Verify account">
                <div className="pt-5">
                    <h2 className="text-2xl leading-8 font-semibold tracking-[-.025em]">
                        Verify your account
                    </h2>
                    <p className="mt-1 text-sm leading-6 text-muted-foreground">
                        Confirm it is really you before we finish.
                    </p>
                </div>

                {editingContact ? (
                    <ContactForm
                        email={contactForm.data.email}
                        phone={contactForm.data.phone}
                        errors={contactForm.errors}
                        processing={contactForm.processing}
                        onEmailChange={(value) =>
                            contactForm.setData('email', value)
                        }
                        onPhoneChange={(value) =>
                            contactForm.setData('phone', value)
                        }
                        onSubmit={updateContact}
                        onCancel={() => setEditingContact(false)}
                    />
                ) : challenge ? (
                    <OtpForm
                        key={challenge.resendAt}
                        destination={challenge.destination}
                        code={verifyForm.data.code}
                        resendAt={challenge.resendAt}
                        processing={verifyForm.processing}
                        resendProcessing={sendForm.processing}
                        error={
                            verifyForm.errors.code ?? sendForm.errors.channel
                        }
                        onCodeChange={(value) =>
                            verifyForm.setData(
                                'code',
                                value.replace(/\D/g, '').slice(0, 6),
                            )
                        }
                        onSubmit={verifyCode}
                        onResend={resendCode}
                        onChangeContact={() => setEditingContact(true)}
                    />
                ) : (
                    <ChannelForm
                        selectedChannel={selectedChannel}
                        email={verification.email}
                        phone={verification.phone}
                        smsAvailable={verification.smsAvailable}
                        error={sendForm.errors.channel}
                        processing={sendForm.processing}
                        onChannelChange={setSelectedChannel}
                        onSubmit={sendCode}
                        onChangeContact={() => setEditingContact(true)}
                    />
                )}

                <p className="mt-7 text-center text-xs leading-5 text-muted-foreground">
                    Already have an account?{' '}
                    <Link
                        href={login.get().url}
                        className="ms-1 text-muted-foreground hover:text-foreground"
                    >
                        Sign in
                    </Link>
                </p>
            </InstitutionalRegistrationShell>
        </>
    );
}

interface ChannelFormProps {
    selectedChannel: VerificationChannel;
    email: string;
    phone: string;
    smsAvailable: boolean;
    error?: string;
    processing: boolean;
    onChannelChange: (channel: VerificationChannel) => void;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
    onChangeContact: () => void;
}

function ChannelForm({
    selectedChannel,
    email,
    phone,
    smsAvailable,
    error,
    processing,
    onChannelChange,
    onSubmit,
    onChangeContact,
}: ChannelFormProps) {
    return (
        <form onSubmit={onSubmit} className="pt-5">
            <fieldset>
                <legend className="text-sm leading-6 text-muted-foreground">
                    Where should we send your one-time code?
                </legend>

                <div className="mt-3 grid gap-3">
                    <ChannelCard
                        label="Email"
                        value={email}
                        selected={selectedChannel === 'email'}
                        onClick={() => onChannelChange('email')}
                    />
                    <ChannelCard
                        label="Phone"
                        value={phone}
                        selected={selectedChannel === 'sms'}
                        disabled={!smsAvailable}
                        onClick={() => onChannelChange('sms')}
                    />
                </div>
            </fieldset>

            <InputError error={error} className="mt-2" />

            <Button
                type="submit"
                className="mt-3 w-full"
                disabled={
                    processing || (selectedChannel === 'sms' && !smsAvailable)
                }
            >
                Send OTP
            </Button>

            <button
                type="button"
                onClick={onChangeContact}
                className="mx-auto mt-6 block text-sm text-muted-foreground hover:text-foreground"
            >
                Change email / phone number
            </button>
        </form>
    );
}

interface ChannelCardProps {
    label: string;
    value: string;
    selected: boolean;
    disabled?: boolean;
    onClick: () => void;
}

function ChannelCard({
    label,
    value,
    selected,
    disabled = false,
    onClick,
}: ChannelCardProps) {
    return (
        <button
            type="button"
            aria-pressed={selected}
            disabled={disabled}
            onClick={onClick}
            className={`min-h-16 w-full rounded-control border px-3 py-2.5 text-left transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:cursor-not-allowed disabled:opacity-50 ${
                selected
                    ? 'border-secondary bg-success-muted/70'
                    : 'border-input bg-background'
            }`}
        >
            <span className="block text-xs leading-5 text-muted-foreground">
                {label}
            </span>
            <strong className="block text-sm leading-5 font-semibold text-foreground">
                {value}
            </strong>
        </button>
    );
}

interface OtpFormProps {
    destination: string;
    code: string;
    resendAt: string;
    error?: string;
    processing: boolean;
    resendProcessing: boolean;
    onCodeChange: (value: string) => void;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
    onResend: () => void;
    onChangeContact: () => void;
}

function OtpForm({
    destination,
    code,
    resendAt,
    error,
    processing,
    resendProcessing,
    onCodeChange,
    onSubmit,
    onResend,
    onChangeContact,
}: OtpFormProps) {
    const remainingSeconds = useCountdown(resendAt);

    return (
        <form onSubmit={onSubmit} className="pt-5">
            <div className="flex min-h-[42px] items-center gap-3 rounded-control border border-blue-200 bg-blue-50 px-3 py-2 text-xs leading-5 text-blue-600">
                <InfoIcon className="size-4 shrink-0" />
                <span>A 6-digit code was sent to {destination}.</span>
            </div>

            <div className="mt-3 grid gap-1">
                <FieldLabel htmlFor="verification-code">Enter OTP</FieldLabel>
                <Input
                    id="verification-code"
                    value={code}
                    onChange={(event) =>
                        onCodeChange(event.currentTarget.value)
                    }
                    inputMode="numeric"
                    autoComplete="one-time-code"
                    pattern="[0-9]*"
                    maxLength={6}
                    autoFocus
                    className="h-[49px] border-secondary text-center text-lg font-medium tracking-[.55em] focus:border-secondary focus:ring-secondary/20"
                    aria-invalid={Boolean(error)}
                />
                <InputError error={error} />
            </div>

            <Button
                type="submit"
                className="mt-3 w-full"
                disabled={processing || code.length !== 6}
            >
                Verify account
            </Button>

            <div className="mt-3 flex flex-col justify-between gap-2 text-sm text-muted-foreground sm:flex-row sm:items-center">
                <button
                    type="button"
                    onClick={onResend}
                    disabled={remainingSeconds > 0 || resendProcessing}
                    className="text-left disabled:cursor-default disabled:opacity-100"
                >
                    {remainingSeconds > 0
                        ? `Resend OTP in ${remainingSeconds}s`
                        : 'Resend OTP'}
                </button>
                <button
                    type="button"
                    onClick={onChangeContact}
                    className="text-left hover:text-foreground sm:text-right"
                >
                    Change email / phone number
                </button>
            </div>
        </form>
    );
}

interface ContactFormProps {
    email: string;
    phone: string;
    errors: Partial<Record<'email' | 'phone', string>>;
    processing: boolean;
    onEmailChange: (value: string) => void;
    onPhoneChange: (value: string) => void;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
    onCancel: () => void;
}

function ContactForm({
    email,
    phone,
    errors,
    processing,
    onEmailChange,
    onPhoneChange,
    onSubmit,
    onCancel,
}: ContactFormProps) {
    return (
        <form onSubmit={onSubmit} className="grid gap-3.5 pt-5">
            <label className="grid gap-1 text-sm font-medium">
                <FieldLabel>Email address</FieldLabel>
                <Input
                    type="email"
                    value={email}
                    onChange={(event) =>
                        onEmailChange(event.currentTarget.value)
                    }
                    autoComplete="email"
                />
                <InputError error={errors.email} />
            </label>

            <label className="grid gap-1 text-sm font-medium">
                <FieldLabel>Phone number</FieldLabel>
                <Input
                    type="tel"
                    value={phone}
                    onChange={(event) =>
                        onPhoneChange(event.currentTarget.value)
                    }
                    autoComplete="tel"
                />
                <InputError error={errors.phone} />
            </label>

            <div className="grid grid-cols-[81px_1fr] gap-3 pt-1">
                <Button type="button" variant="outline" onClick={onCancel}>
                    Back
                </Button>
                <Button type="submit" disabled={processing}>
                    Save changes
                </Button>
            </div>
        </form>
    );
}

function useCountdown(resendAt: string): number {
    const deadline = useMemo(() => new Date(resendAt).getTime(), [resendAt]);
    const [remaining, setRemaining] = useState(() =>
        Math.max(0, Math.ceil((deadline - Date.now()) / 1000)),
    );

    useEffect(() => {
        const interval = window.setInterval(() => {
            const next = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
            setRemaining(next);

            if (next === 0) {
                window.clearInterval(interval);
            }
        }, 250);

        return () => window.clearInterval(interval);
    }, [deadline]);

    return remaining;
}
