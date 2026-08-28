<?php

namespace App\Models;

use App\Enums\CampaignBudgetCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $campaign_id
 * @property int $workspace_id
 * @property CampaignBudgetCategory $category
 * @property string $amount
 * @property string $currency
 * @property string $reference
 * @property Carbon $occurred_at
 * @property array<string, mixed>|null $meta
 */
final class CampaignBudgetUsage extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'campaign_id',
        'workspace_id',
        'category',
        'amount',
        'currency',
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

    /** @return array<string, string|class-string> */
    protected function casts(): array
    {
        return [
            'category' => CampaignBudgetCategory::class,
            'amount' => 'decimal:2',
            'occurred_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
