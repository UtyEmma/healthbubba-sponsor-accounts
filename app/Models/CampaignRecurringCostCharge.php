<?php

namespace App\Models;

use App\Enums\CampaignBoothChargeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $campaign_recurring_cost_id
 * @property int $campaign_id
 * @property int $workspace_id
 * @property Carbon $service_period
 * @property numeric-string $amount
 * @property string $currency
 * @property CampaignBoothChargeStatus $status
 * @property string|null $reference
 * @property Carbon|null $attempted_at
 * @property Carbon|null $paid_at
 */
final class CampaignRecurringCostCharge extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'campaign_recurring_cost_id', 'campaign_id', 'workspace_id', 'service_period',
        'amount', 'currency', 'status', 'reference', 'attempted_at', 'paid_at', 'meta',
    ];

    protected $attributes = ['currency' => 'NGN', 'status' => CampaignBoothChargeStatus::Pending->value];

    /** @return BelongsTo<CampaignRecurringCost, $this> */
    public function recurringCost(): BelongsTo
    {
        return $this->belongsTo(CampaignRecurringCost::class, 'campaign_recurring_cost_id');
    }

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'service_period' => 'date',
            'amount' => 'decimal:2',
            'status' => CampaignBoothChargeStatus::class,
            'attempted_at' => 'datetime',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
