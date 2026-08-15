<?php

namespace App\Http\Controllers;

use App\Enums\Transactions\TransactionFlow;
use App\Enums\Transactions\TransactionStatus;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\WalletResource;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceMembers\WorkspaceMemberAccessService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class WalletController extends Controller
{
    public function __construct(private readonly WorkspaceMemberAccessService $workspaceAccess) {}

    public function index(Request $request): Response
    {
        $workspace = Workspace::current();

        abort_if($workspace === null, 404);
        abort_unless($request->user() instanceof User && $this->workspaceAccess->canManage($request->user(), $workspace), 403);

        $wallet = $workspace->wallet()->firstOrCreate([], [
            'balance' => '0.00',
            'currency' => config('payments.currency', 'NGN'),
        ]);

        $completedTransactions = Transaction::query()
            ->where('owner_type', $workspace->getMorphClass())
            ->where('owner_id', $workspace->getKey())
            ->where('status', TransactionStatus::COMPLETED);

        $totals = (clone $completedTransactions)
            ->where('created_at', '>=', now()->subDays(90))
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN flow = ? THEN amount ELSE 0 END), 0) AS total_in, COALESCE(SUM(CASE WHEN flow = ? THEN amount ELSE 0 END), 0) AS total_out',
                [TransactionFlow::CREDIT->value, TransactionFlow::DEBIT->value],
            )
            ->first();

        return Inertia::render('wallet/index', [
            'wallet' => new WalletResource(
                resource: $wallet,
                totalIn: (string) data_get($totals, 'total_in', '0.00'),
                totalOut: (string) data_get($totals, 'total_out', '0.00'),
            ),
            'transactions' => TransactionResource::collection(
                $completedTransactions->latest()->limit(50)->get(),
            ),
        ]);
    }
}
