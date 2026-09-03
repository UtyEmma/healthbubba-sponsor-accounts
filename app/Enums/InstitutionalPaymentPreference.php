<?php

namespace App\Enums;

enum InstitutionalPaymentPreference: string
{
    case UserChoice = 'user_choice';
    case BeneficiaryWallet = 'beneficiary_wallet';
    case CardPayment = 'card_payment';

    public function label(): string
    {
        return match ($this) {
            self::UserChoice => 'User choice',
            self::BeneficiaryWallet => 'Beneficiary wallet',
            self::CardPayment => 'Card payment',
        };
    }
}
