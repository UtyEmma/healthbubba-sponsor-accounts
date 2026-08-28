<?php

namespace App\Enums;

enum InstitutionalOrganizationType: string
{
    case NgoFoundation = 'ngo_foundation';
    case GovernmentAgency = 'government_agency';
    case DevelopmentPartner = 'development_partner';
    case FaithBasedOrganization = 'faith_based_organization';
    case CommunityBasedOrganization = 'community_based_organization';
    case CorporateFoundation = 'corporate_foundation';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::NgoFoundation => 'NGO/Foundation',
            self::GovernmentAgency => 'Government Agency',
            self::DevelopmentPartner => 'Development Partner',
            self::FaithBasedOrganization => 'Faith-Based Organization',
            self::CommunityBasedOrganization => 'Community-Based Organization',
            self::CorporateFoundation => 'Corporate Foundation/CSR',
            self::Other => 'Other',
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            static fn (self $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases(),
        );
    }
}
