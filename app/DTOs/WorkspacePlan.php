<?php

namespace App\DTOs;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class WorkspacePlan implements Arrayable
{
    /**
     * @param  list<array{
     *     slug: string,
     *     name: string,
     *     description: string|null,
     *     type: string,
     *     included: bool,
     *     value: string|null,
     *     unitPrice: string|null
     * }>  $features
     * @param  list<array{
     *     name: string,
     *     slug: string,
     *     quota: string|null,
     *     description: string
     * }>  $quotas
     * @param  array{
     *     unit: string,
     *     unit_plural: string,
     *     included: int,
     *     maximum: int|null,
     *     additional_unit_price: string|null,
     *     purchases_enabled: bool,
     *     unavailable_reason: string|null
     * }|null  $capacity
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public string $price,
        public string $cadence,
        public string $currency,
        public bool $isCurrent,
        public bool $checkoutAvailable,
        public ?int $includedSeats,
        public ?string $additionalSeatPrice,
        public bool $allowsCapacityPurchases,
        public ?array $capacity,
        public ?string $unavailableReason,
        public array $features,
        public array $quotas,
    ) {}

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     description: string|null,
     *     price: string,
     *     cadence: string,
     *     currency: string,
     *     is_current: bool,
     *     checkout_available: bool,
     *     included_seats: int|null,
     *     additional_seat_price: string|null,
     *     allows_capacity_purchases: bool,
     *     capacity: array{
     *         unit: string,
     *         unit_plural: string,
     *         included: int,
     *         maximum: int|null,
     *         additional_unit_price: string|null,
     *         purchases_enabled: bool,
     *         unavailable_reason: string|null
     *     }|null,
     *     unavailable_reason: string|null,
     *     features: list<array{
     *         slug: string,
     *         name: string,
     *         description: string|null,
     *         type: string,
     *         included: bool,
     *         value: string|null,
     *         unitPrice: string|null
     *     }>,
     *     quotas: list<array{
     *         name: string,
     *         slug: string,
     *         quota: string|null,
     *         description: string
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'cadence' => $this->cadence,
            'currency' => $this->currency,
            'features' => $this->features,
            'quotas' => $this->quotas,
            'is_current' => $this->isCurrent,
            'checkout_available' => $this->checkoutAvailable,
            'included_seats' => $this->includedSeats,
            'additional_seat_price' => $this->additionalSeatPrice,
            'allows_capacity_purchases' => $this->allowsCapacityPurchases,
            'capacity' => $this->capacity,
            'unavailable_reason' => $this->unavailableReason,
        ];
    }
}
