<?php

namespace App\Actions\Admin;

use App\DTOs\Activity\WorkspaceActivityActor;
use App\DTOs\Activity\WorkspaceActivityData;
use App\DTOs\Admin\RecordAdminWorkspaceFundingData;
use App\Enums\Activity\WorkspaceActivityType;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Enums\Transactions\TransactionTypes;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Workspace;
use App\Services\Activity\WorkspaceActivityLogger;
use App\Support\Payments\PaymentReferenceGenerator;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RecordAdminWorkspaceFundingAction
{
    public function __construct(
        private PaymentReferenceGenerator $references,
        private WorkspaceActivityLogger $activities,
    ) {}

    public function execute(User $administrator, RecordAdminWorkspaceFundingData $data): Transaction
    {
        abort_unless($administrator->isAdmin(), 403);

        if ($data->amount->amountInMinorUnits <= 0) {
            throw ValidationException::withMessages([
                'funding_amount' => 'The funding amount must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($administrator, $data): Transaction {
            $workspace = Workspace::query()->whereKey($data->workspaceId)->lockForUpdate()->firstOrFail();
            $wallet = $workspace->wallet()->firstOrCreate([], [
                'balance' => '0.00',
                'currency' => $data->amount->currency,
            ]);
            $wallet = Wallet::query()->whereKey($wallet->getKey())->lockForUpdate()->firstOrFail();

            if ($wallet->currency !== $data->amount->currency) {
                throw ValidationException::withMessages([
                    'funding_amount' => 'The funding currency must match the workspace wallet currency.',
                ]);
            }

            $balance = Money::fromMajor($wallet->balance, $wallet->currency);
            $wallet->update(['balance' => $balance->add($data->amount)->toMajorAmount()]);

            $transaction = Transaction::query()->create([
                'payment_id' => null,
                'recorded_by_user_id' => $administrator->getKey(),
                'owner_type' => $workspace->getMorphClass(),
                'owner_id' => $workspace->getKey(),
                'transactable_type' => $wallet->getMorphClass(),
                'transactable_id' => $wallet->getKey(),
                'amount' => $data->amount->toMajorAmount(),
                'currency' => $data->amount->currency,
                'reference' => $this->references->generate(PaymentPurpose::WALLET_TOP_UP),
                'type' => TransactionTypes::TOPUP,
                'status' => TransactionStatus::COMPLETED,
                'flow' => TransactionFlow::CREDIT,
                'meta' => [
                    'description' => 'Sponsor-funded wallet credit recorded by an administrator',
                    'funding_source' => 'sponsor',
                    'recording_channel' => 'admin',
                    'external_reference' => $data->externalReference,
                    'note' => $data->note,
                ],
            ]);

            $this->activities->record($workspace, new WorkspaceActivityData(
                type: WorkspaceActivityType::AdminWalletFundingRecorded,
                title: 'Sponsor funding recorded',
                actor: WorkspaceActivityActor::user($administrator),
                subjectType: 'transaction',
                subjectId: $transaction->getKey(),
                subjectName: $transaction->reference,
                description: 'Sponsor-provided funds were credited to the workspace wallet by an administrator.',
                context: [
                    'amount_minor' => $data->amount->amountInMinorUnits,
                    'currency' => $data->amount->currency,
                ],
            ));

            return $transaction->refresh();
        }, 3);
    }
}
