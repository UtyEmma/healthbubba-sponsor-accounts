<?php

namespace App\Models;

use App\Enums\CampaignUsageBenefit;
use App\Enums\CampaignUsageSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $campaign_id
 * @property int $workspace_id
 * @property int|null $workspace_beneficiary_id
 * @property int|null $recorded_by_user_id
 * @property CampaignUsageBenefit $benefit
 * @property int|null $quantity
 * @property numeric-string|null $unit_amount
 * @property numeric-string $total_amount
 * @property string $currency
 * @property CampaignUsageSource $source
 * @property string|null $source_reference
 * @property string $reference
 * @property Carbon $occurred_at
 */
final class CampaignUsageEntry extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'campaign_id',
        'workspace_id',
        'workspace_beneficiary_id',
        'recorded_by_user_id',
        'benefit',
        'quantity',
        'unit_amount',
        'total_amount',
        'currency',
        'source',
        'source_reference',
        'reference',
        'occurred_at',
        'meta',
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

    /** @return BelongsTo<WorkspaceBeneficiary, $this> */
    public function workspaceBeneficiary(): BelongsTo
    {
        return $this->belongsTo(WorkspaceBeneficiary::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'benefit' => CampaignUsageBenefit::class,
            'source' => CampaignUsageSource::class,
            'quantity' => 'integer',
            'unit_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'occurred_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
