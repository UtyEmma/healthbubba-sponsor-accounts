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
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public string $price,
        public bool $isCurrent,
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
     *     is_current: bool,
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
            'features' => $this->features,
            'quotas' => $this->quotas,
            'is_current' => $this->isCurrent,
        ];
    }
}
