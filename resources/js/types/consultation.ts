export type ConsultationType = 'gp' | 'specialist';
export type ConsultationStatus = 'upcoming' | 'completed' | 'cancelled';
export type ConsultationAllocationScope = 'shared' | 'per_employee';
export type ConsultationReservationStatus =
    'reserved' | 'confirmed' | 'cancelled';

export interface ConsultationPerson {
    id: number | null;
    name: string;
    email: string | null;
    phone: string | null;
}

export interface ConsultationDoctor {
    id: number;
    name: string;
    providerType: string | null;
}

export interface Consultation {
    id: number;
    beneficiary: ConsultationPerson;
    consultationType: {
        value: ConsultationType;
        label: string;
    };
    doctor: ConsultationDoctor | null;
    status: {
        value: ConsultationStatus;
        label: string;
    };
    scheduledAt: string | null;
    cost: {
        units: number;
        featureSlug: string;
        planName: string | null;
        scope: ConsultationAllocationScope;
        label: string;
    };
    createdAt: string | null;
}

export interface PaginatedConsultations {
    data: Consultation[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
}

export interface ConsultationAllocationSummary {
    type: ConsultationType;
    label: string;
    scope: ConsultationAllocationScope;
    scopeLabel: string;
    limit: number | null;
    completed: number;
    reserved: number;
    remaining: number | null;
    resetAt: string | null;
    unavailableReason: string | null;
}

export interface ConsultationQuotaBreakdown {
    base: number | null;
    additional: number | null;
    total: number | null;
}

export interface ConsultationScalingStep {
    capacity: number;
    additionalCapacity: number;
    gp: ConsultationQuotaBreakdown;
    specialist: ConsultationQuotaBreakdown;
}

export interface ConsultationAllocationScaling {
    available: boolean;
    unavailableReason: string | null;
    capacityLabel: string;
    capacityUnit: string;
    capacityUnitPlural: string;
    includedCapacity: number | null;
    currentCapacity: number | null;
    maximumCapacity: number | null;
    gpPerCapacity: number | null;
    specialistPerCapacity: number | null;
    description: string;
    steps: ConsultationScalingStep[];
}

export interface ConsultationCoverage {
    planName: string | null;
    activeBeneficiaries: number;
    allocations: ConsultationAllocationSummary[];
    scaling: ConsultationAllocationScaling;
}

export interface ConsultationPageProps {
    consultations: PaginatedConsultations;
    coverage: ConsultationCoverage;
}

export interface ConsultationReservation {
    token: string;
    status: ConsultationReservationStatus;
    appointmentId: number | null;
    sponsorId: number;
    patientId: number;
    doctorId: number;
    consultationType: {
        value: ConsultationType;
        label: string;
    };
    featureSlug: string;
    planName: string;
    scope: ConsultationAllocationScope;
    reservedAt: string;
    confirmedAt: string | null;
    cancelledAt: string | null;
    heldUntilCancelled: boolean;
}

export interface ConsultationEligibilityResponse {
    data: {
        available: boolean;
        reason: string | null;
        consultationType: {
            value: ConsultationType;
            label: string;
        } | null;
        reservation: ConsultationReservation | null;
    };
}

export type ConsultationSponsorshipUnavailableReason =
    | 'no_active_subscription'
    | 'feature_unavailable'
    | 'no_funding_program'
    | 'no_active_campaign'
    | 'daily_limit_reached'
    | 'per_beneficiary_limit_reached'
    | 'allocation_exhausted';

export interface ConsultationTypeAvailability {
    value: ConsultationType;
    label: string;
    available: boolean;
    reason: ConsultationSponsorshipUnavailableReason | null;
    coverageName: string | null;
    allocatedUnits: number | null;
    usedUnits: number;
    reservedUnits: number;
    remainingUnits: number | null;
    periodStartsAt: string | null;
    periodEndsAt: string | null;
}

export interface ConsultationSponsorAvailability {
    id: number;
    name: string;
    type: {
        value: 'individual' | 'business' | 'institution';
        label: string;
    };
    consultationTypes: ConsultationTypeAvailability[];
}

export interface PatientConsultationSponsorshipResponse {
    patientId: number;
    sponsors: ConsultationSponsorAvailability[];
}

export interface RecordConsultationUsagePayload {
    appointment_id: number;
    sponsor_id: number;
}

export interface ConsultationUsageResponse {
    recorded: true;
    usageReference: string;
    appointmentId: number;
    patientId: number;
    doctorId: number;
    sponsor: {
        id: number;
        name: string;
        type: {
            value: 'individual' | 'business' | 'institution';
            label: string;
        };
    };
    consultationType: {
        value: ConsultationType;
        label: string;
    };
    coverageName: string;
    recordedAt: string | null;
}
