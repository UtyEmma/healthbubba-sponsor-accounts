<?php

namespace App\Enums\MedicalAccess;

enum MedicalAccessDataType: string
{
    case ClinicalRecord = 'CLINICAL_RECORD';
    case PrescriptionRecord = 'PRESCRIPTION_RECORD';
    case LabRecord = 'LAB_RECORD';

    public function label(): string
    {
        return match ($this) {
            self::ClinicalRecord => 'Clinical Diagnosis',
            self::PrescriptionRecord => 'Prescription Records',
            self::LabRecord => 'Laboratory Results',
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
