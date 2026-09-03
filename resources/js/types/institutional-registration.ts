export interface RegistrationOption {
    value: string;
    label: string;
}

export interface InstitutionalRegistrationPageProps {
    organizationTypes: RegistrationOption[];
    countries: RegistrationOption[];
    states: RegistrationOption[];
    initialAccountType: AccountType | null;
    initialEmail: string;
}

export type VerificationChannel = 'email' | 'sms';

export interface AccountVerificationChallenge {
    channel: VerificationChannel;
    destination: string;
    resendAt: string;
    expiresAt: string;
}

export interface AccountVerificationPageProps {
    verification: {
        email: string;
        phone: string;
        smsAvailable: boolean;
        challenge: AccountVerificationChallenge | null;
    };
}
import type { AccountType } from './billing';
