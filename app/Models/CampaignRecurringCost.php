<?php

namespace App\Models;

use App\Enums\CampaignRecurringCostCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $campaign_id
 * @property int $workspace_id
 * @property int|null $campaign_booth_id
 * @property string $name
 * @property CampaignRecurringCostCategory $category
 * @property numeric-string $monthly_amount
 * @property string $currency
 * @property Carbon $starts_on
 * @property Carbon|null $next_charge_on
 * @property Carbon|null $ends_on
 * @property bool $is_active
 * @property Carbon|null $deactivated_at
 */
final class CampaignRecurringCost extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'campaign_id', 'workspace_id', 'campaign_booth_id', 'name', 'category',
        'monthly_amount', 'currency', 'starts_on', 'next_charge_on', 'ends_on', 'is_active', 'deactivated_at',
    ];

    protected $attributes = ['currency' => 'NGN', 'is_active' => true];

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return BelongsTo<CampaignBooth, $this> */
    public function booth(): BelongsTo
    {
        return $this->belongsTo(CampaignBooth::class, 'campaign_booth_id');
    }

    /** @return HasMany<CampaignRecurringCostCharge, $this> */
    public function charges(): HasMany
    {
        return $this->hasMany(CampaignRecurringCostCharge::class);
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'category' => CampaignRecurringCostCategory::class,
            'monthly_amount' => 'decimal:2',
            'starts_on' => 'date',
            'next_charge_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
        ];
    }
}
