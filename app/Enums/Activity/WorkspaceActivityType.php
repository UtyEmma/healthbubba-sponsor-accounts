<?php

namespace App\Enums\Activity;

enum WorkspaceActivityType: string
{
    case WalletTopUpCompleted = 'wallet_top_up_completed';
    case PaymentFailed = 'payment_failed';
    case SubscriptionActivated = 'subscription_activated';
    case SubscriptionRenewed = 'subscription_renewed';
    case SubscriptionPastDue = 'subscription_past_due';
    case SubscriptionExpired = 'subscription_expired';
    case PlanUpgradeCompleted = 'plan_upgrade_completed';
    case PlanDowngradeScheduled = 'plan_downgrade_scheduled';
    case PlanDowngradeApplied = 'plan_downgrade_applied';
    case CapacityPurchased = 'capacity_purchased';
    case BeneficiaryInvited = 'beneficiary_invited';
    case InvitationResent = 'invitation_resent';
    case InvitationCancelled = 'invitation_cancelled';
    case EmployeeImportCompleted = 'employee_import_completed';
    case InvitationAccepted = 'invitation_accepted';
    case InvitationDeclined = 'invitation_declined';
    case BeneficiarySuspended = 'beneficiary_suspended';
    case BeneficiaryRestored = 'beneficiary_restored';
    case BeneficiaryRevoked = 'beneficiary_revoked';
    case MedicalAccessRequested = 'medical_access_requested';
    case MedicalAccessApproved = 'medical_access_approved';
    case MedicalAccessDenied = 'medical_access_denied';

    public function category(): string
    {
        return match ($this) {
            self::WalletTopUpCompleted,
            self::PaymentFailed => 'transaction',
            self::SubscriptionActivated,
            self::SubscriptionRenewed,
            self::SubscriptionPastDue,
            self::SubscriptionExpired,
            self::PlanUpgradeCompleted,
            self::PlanDowngradeScheduled,
            self::PlanDowngradeApplied,
            self::CapacityPurchased => 'subscription',
            self::BeneficiaryInvited,
            self::InvitationResent,
            self::InvitationCancelled,
            self::EmployeeImportCompleted,
            self::InvitationAccepted,
            self::InvitationDeclined,
            self::BeneficiarySuspended,
            self::BeneficiaryRestored,
            self::BeneficiaryRevoked => 'beneficiary',
            self::MedicalAccessRequested,
            self::MedicalAccessApproved,
            self::MedicalAccessDenied => 'medical_access',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::WalletTopUpCompleted => 'wallet',
            self::PaymentFailed,
            self::SubscriptionPastDue => 'circle-alert',
            self::SubscriptionActivated,
            self::SubscriptionRenewed => 'credit-card',
            self::SubscriptionExpired => 'clock',
            self::PlanUpgradeCompleted => 'arrow-up-circle',
            self::PlanDowngradeScheduled,
            self::PlanDowngradeApplied => 'calendar-clock',
            self::CapacityPurchased,
            self::BeneficiaryInvited,
            self::InvitationResent,
            self::EmployeeImportCompleted => 'user-plus',
            self::InvitationAccepted => 'user-check',
            self::InvitationCancelled,
            self::InvitationDeclined,
            self::BeneficiaryRevoked => 'user-x',
            self::BeneficiarySuspended => 'pause-circle',
            self::BeneficiaryRestored => 'rotate-ccw',
            self::MedicalAccessRequested,
            self::MedicalAccessApproved => 'shield-check',
            self::MedicalAccessDenied => 'shield-x',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::WalletTopUpCompleted,
            self::SubscriptionActivated,
            self::SubscriptionRenewed,
            self::PlanUpgradeCompleted,
            self::CapacityPurchased,
            self::InvitationAccepted,
            self::BeneficiaryRestored,
            self::MedicalAccessApproved => 'success',
            self::PlanDowngradeScheduled,
            self::BeneficiarySuspended => 'warning',
            self::PaymentFailed,
            self::SubscriptionPastDue,
            self::SubscriptionExpired,
            self::InvitationCancelled,
            self::InvitationDeclined,
            self::BeneficiaryRevoked,
            self::MedicalAccessDenied => 'destructive',
            default => 'info',
        };
    }
}
