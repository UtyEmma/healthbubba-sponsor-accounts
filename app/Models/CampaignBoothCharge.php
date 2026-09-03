<?php

namespace App\Models;

use App\Enums\CampaignBoothChargeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $campaign_id
 * @property int $workspace_id
 * @property Carbon $service_period
 * @property int $quantity
 * @property string $unit_fee
 * @property string $total_cost
 * @property string $currency
 * @property CampaignBoothChargeStatus $status
 * @property string|null $reference
 * @property Carbon|null $attempted_at
 * @property Carbon|null $paid_at
 * @property array<string, mixed>|null $meta
 */
final class CampaignBoothCharge extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'campaign_id',
        'workspace_id',
        'service_period',
        'quantity',
        'unit_fee',
        'total_cost',
        'currency',
        'status',
        'reference',
        'attempted_at',
        'paid_at',
        'meta',
    ];

    protected $attributes = [
        'status' => CampaignBoothChargeStatus::Pending->value,
        'currency' => 'NGN',
    ];

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'service_period' => 'date',
            'quantity' => 'integer',
            'unit_fee' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'status' => CampaignBoothChargeStatus::class,
            'attempted_at' => 'datetime',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
