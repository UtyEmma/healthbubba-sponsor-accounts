<?php

use App\Enums\AccountTypes;
use App\Enums\Consultations\ConsultationType;
use App\Enums\Subscriptions\Features;
use App\Services\Consultations\ConsultationTypeResolver;

it('resolves consultation products exclusively from appointment urgency', function (
    ?bool $isUrgent,
    ConsultationType $expected,
): void {
    expect((new ConsultationTypeResolver)->resolve($isUrgent))->toBe($expected);
})->with([
    'urgent appointments are instant' => [true, ConsultationType::Instant],
    'non-urgent appointments are scheduled' => [false, ConsultationType::Scheduled],
    'legacy appointments without a flag are scheduled' => [null, ConsultationType::Scheduled],
]);

it('maps scheduled and instant products to the matching subscription features', function (): void {
    $resolver = new ConsultationTypeResolver;

    expect($resolver->feature(AccountTypes::INDIVIDUAL, ConsultationType::Scheduled))
        ->toBe(Features::SCHEDULED_CONSULTATIONS)
        ->and($resolver->feature(AccountTypes::INDIVIDUAL, ConsultationType::Instant))
        ->toBe(Features::INSTANT_CONSULTATIONS)
        ->and($resolver->feature(AccountTypes::BUSINESS, ConsultationType::Scheduled))
        ->toBe(Features::SCHEDULED_CONSULTATIONS_PER_SEAT)
        ->and($resolver->feature(AccountTypes::BUSINESS, ConsultationType::Instant))
        ->toBe(Features::INSTANT_CONSULTATIONS_PER_SEAT);
});
