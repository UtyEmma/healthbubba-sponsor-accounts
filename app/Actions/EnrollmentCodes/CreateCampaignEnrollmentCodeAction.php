<?php

namespace App\Actions\EnrollmentCodes;

use App\DTOs\EnrollmentCodes\CreateCampaignEnrollmentCodeData;
use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignEnrollmentCode;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateCampaignEnrollmentCodeAction
{
    public function execute(CreateCampaignEnrollmentCodeData $data): CampaignEnrollmentCode
    {
        return DB::transaction(function () use ($data): CampaignEnrollmentCode {
            $campaign = Campaign::query()
                ->whereKey($data->campaign->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($campaign->lifecycleStatus() === CampaignStatus::COMPLETED) {
                throw ValidationException::withMessages([
                    'campaign' => 'Enrollment codes cannot be created for an ended campaign.',
                ]);
            }

            for ($attempt = 0; $attempt < 5; $attempt++) {
                try {
                    $code = CampaignEnrollmentCode::query()->create([
                        'public_id' => (string) Str::ulid(),
                        'campaign_id' => $campaign->getKey(),
                        'created_by_user_id' => $data->creator->getKey(),
                        'code' => $this->code($campaign),
                        'enrollment_limit' => $data->enrollmentLimit,
                        'expires_at' => $data->expiresAt,
                    ]);

                    if ($campaign->display_enrollment_code === null) {
                        $campaign->update(['display_enrollment_code' => $code->code]);
                    }

                    return $code;
                } catch (UniqueConstraintViolationException $exception) {
                    if ($attempt === 4) {
                        throw $exception;
                    }
                }
            }

            throw ValidationException::withMessages(['code' => 'A unique enrollment code could not be generated.']);
        }, 3);
    }

    private function code(Campaign $campaign): string
    {
        $location = Str::of((string) $campaign->location)->before(',')->slug('-')->upper()->limit(12, '')->toString();
        $location = $location !== '' ? $location : 'CAMPAIGN';
        $year = $campaign->end_date?->format('Y') ?? now()->format('Y');

        return "{$location}-{$year}-".Str::upper(Str::random(5));
    }
}
