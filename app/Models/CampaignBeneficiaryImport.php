<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $public_id
 * @property int $campaign_id
 * @property int $workspace_id
 * @property list<array{row: int, identifier: string|null, code: string, message: string}> $errors
 */
final class CampaignBeneficiaryImport extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'public_id', 'campaign_id', 'workspace_id', 'created_by_user_id',
        'processed_count', 'enrolled_count', 'skipped_count', 'errors',
    ];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['processed_count' => 'integer', 'enrolled_count' => 'integer', 'skipped_count' => 'integer', 'errors' => 'array'];
    }
}
