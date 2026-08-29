<?php

namespace App\Actions\Campaigns;

use App\DTOs\Campaigns\AddCampaignBoothsData;
use App\Enums\CampaignBoothStatus;
use App\Enums\CampaignRecurringCostCategory;
use App\Enums\CampaignStatus;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Models\Campaign;
use App\Models\CampaignBooth;
use App\Models\CampaignRecurringCost;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Support\Payments\PaymentReferenceGenerator;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class AddCampaignBoothsAction
{
    public function __construct(private PaymentReferenceGenerator $references) {}

    /** @return Collection<int, CampaignBooth> */
    public function execute(AddCampaignBoothsData $data): Collection
    {
        return DB::transaction(function () use ($data): Collection {
            $campaign = Campaign::query()->whereBelongsTo($data->workspace)->whereKey($data->campaign->getKey())->lockForUpdate()->firstOrFail();

            if ($campaign->lifecycleStatus() === CampaignStatus::COMPLETED) {
                throw ValidationException::withMessages(['campaign' => 'Booths cannot be added to an ended campaign.']);
            }

            $deploymentDate = CarbonImmutable::parse($data->preferredDeploymentDate);

            if (($campaign->start_date !== null && $deploymentDate->isBefore($campaign->start_date))
                || ($campaign->end_date !== null && $deploymentDate->isAfter($campaign->end_date))) {
                throw ValidationException::withMessages(['preferred_deployment_date' => 'The deployment date must be within the campaign period.']);
            }

            $communities = collect(explode(',', (string) $campaign->location))
                ->map(static fn (string $community): string => mb_strtolower(trim($community)))
                ->filter();

            if (! $communities->contains(mb_strtolower($data->community))) {
                throw ValidationException::withMessages([
                    'community' => 'The community must be one of this campaign’s locations.',
                ]);
            }

            $setupFee = Money::fromMajor((string) config('campaigns.booth_setup_fee'), $campaign->currency);
            $monthlyFee = Money::fromMajor((string) config('campaigns.booth_monthly_fee'), $campaign->currency);
            $total = $setupFee->multiply($data->count);
            $wallet = $data->workspace->wallet()->firstOrCreate([], ['balance' => '0.00', 'currency' => $campaign->currency]);
            $wallet = Wallet::query()->whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();
            $balance = Money::fromMajor($wallet->balance, $wallet->currency);

            if ($balance->currency !== $total->currency || $balance->amountInMinorUnits < $total->amountInMinorUnits) {
                throw ValidationException::withMessages(['booth' => 'Your available wallet balance is insufficient for the booth setup fee.']);
            }

            $reference = $this->references->generateBoothCharge();
            $booths = new Collection;

            foreach (range(1, $data->count) as $position) {
                $name = $data->count > 1 ? "{$data->site} {$position}" : $data->site;
                $booth = CampaignBooth::query()->create([
                    'public_id' => (string) Str::ulid(),
                    'campaign_id' => $campaign->getKey(),
                    'workspace_id' => $data->workspace->getKey(),
                    'name' => $name,
                    'site' => $data->site,
                    'community' => $data->community,
                    'expected_beneficiaries' => $data->expectedBeneficiaries,
                    'contact_name' => $data->contactName,
                    'contact_phone' => $data->contactPhone,
                    'preferred_deployment_date' => $data->preferredDeploymentDate,
                    'setup_fee' => $setupFee->toMajorAmount(),
                    'monthly_fee' => $monthlyFee->toMajorAmount(),
                    'currency' => $campaign->currency,
                    'status' => CampaignBoothStatus::Requested,
                    'setup_reference' => $reference,
                    'setup_paid_at' => now(),
                ]);
                CampaignRecurringCost::query()->create([
                    'campaign_id' => $campaign->getKey(),
                    'workspace_id' => $data->workspace->getKey(),
                    'campaign_booth_id' => $booth->getKey(),
                    'name' => 'Booth management & service',
                    'category' => CampaignRecurringCostCategory::BoothService,
                    'monthly_amount' => $monthlyFee->toMajorAmount(),
                    'currency' => $campaign->currency,
                    'starts_on' => $data->preferredDeploymentDate,
                    'is_active' => false,
                ]);
                $booths->push($booth);
            }

            $wallet->update(['balance' => (new Money($balance->amountInMinorUnits - $total->amountInMinorUnits, $balance->currency))->toMajorAmount()]);
            $campaign->update(['booth_required' => true, 'booth_count' => $campaign->booths()->count()]);
            Transaction::query()->create([
                'owner_type' => $data->workspace->getMorphClass(),
                'owner_id' => $data->workspace->getKey(),
                'transactable_type' => $campaign->getMorphClass(),
                'transactable_id' => $campaign->getKey(),
                'amount' => $total->toMajorAmount(),
                'currency' => $total->currency,
                'reference' => $reference,
                'type' => TransactionTypes::CAMPAIGN_BOOTH_SETUP,
                'status' => TransactionStatus::COMPLETED,
                'flow' => TransactionFlow::DEBIT,
                'meta' => ['description' => "Booth setup for {$campaign->name}", 'campaign_id' => $campaign->getKey(), 'quantity' => $data->count, 'created_by_user_id' => $data->user->getKey()],
            ]);

            return $booths;
        }, 3);
    }
}
