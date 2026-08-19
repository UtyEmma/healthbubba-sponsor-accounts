<?php

namespace App\Models;

use App\Enums\Consultations\ConsultationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $campaign_id
 * @property int $workspace_id
 * @property ConsultationType $consultation_type
 * @property int $quantity
 * @property string $unit_fee
 * @property string $total_cost
 * @property string $reference
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Campaign $campaign
 * @property-read Workspace $workspace
 */
final class CampaignConsultationQuota extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'campaign_id',
        'workspace_id',
        'consultation_type',
        'quantity',
        'unit_fee',
        'total_cost',
        'reference',
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
            'consultation_type' => ConsultationType::class,
            'quantity' => 'integer',
            'unit_fee' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }
}
