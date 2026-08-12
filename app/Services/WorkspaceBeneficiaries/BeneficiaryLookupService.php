<?php

namespace App\Services\WorkspaceBeneficiaries;

use App\Models\Beneficiary;
use Illuminate\Support\Collection;
use Throwable;

final class BeneficiaryLookupService
{
    /** @param list<string> $emails
     * @return Collection<string, int>
     */
    public function idsByEmail(array $emails): Collection
    {
        $normalized = collect($emails)
            ->map(static fn (string $email): string => mb_strtolower(trim($email)))
            ->filter()
            ->unique()
            ->values();

        try {
            return $normalized
                ->chunk(500)
                ->flatMap(fn (Collection $chunk): Collection => Beneficiary::query()
                    ->select(['id', 'email'])
                    ->whereIn('email', $chunk->all())
                    ->get())
                ->mapWithKeys(static fn (Beneficiary $beneficiary): array => [
                    mb_strtolower(trim((string) $beneficiary->getAttribute('email'))) => (int) $beneficiary->getKey(),
                ]);
        } catch (Throwable $exception) {
            report($exception);

            return collect();
        }
    }
}
