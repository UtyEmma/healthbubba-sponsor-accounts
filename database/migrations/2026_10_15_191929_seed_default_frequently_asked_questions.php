<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('frequently_asked_questions')->insert([
            ...$this->individualQuestions($now),
            ...$this->businessQuestions($now),
            ...$this->institutionalQuestions($now),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The schema migration drops this table during a full rollback. A standalone
        // rollback intentionally preserves administrator-edited FAQ content.
    }

    /** @return list<array<string, mixed>> */
    private function individualQuestions(DateTimeInterface $now): array
    {
        return $this->rows('individual', [
            ['How is coverage shared?', 'When a plan includes primary-sponsor coverage, the sponsor and covered beneficiaries use the same consultation pool and the sponsor does not consume a beneficiary slot. Plans that do not include the sponsor cover only the beneficiaries you add.'],
            ['Do unused consultations roll over?', 'No. Unused scheduled and instant consultation allocations expire and reset on the subscription renewal date.'],
            ['What happens when I upgrade or downgrade the plan?', 'Upgrades take effect immediately after a prorated payment. Eligible downgrades also take effect immediately, with no charge or refund; a downgrade is blocked when current capacity or consultation usage exceeds the target limits.'],
            ['How are subscription payments collected?', "You can pay with your Wallet or the active external payment provider. Renewals try the Wallet first and use the subscription's saved reusable authorization only when the Wallet balance is insufficient."],
            ['Can I cancel anytime?', 'Yes. Coverage stays active until the end of the current billing cycle. Covered people retain their HealthBubba records afterward.'],
        ], $now);
    }

    /** @return list<array<string, mixed>> */
    private function businessQuestions(DateTimeInterface $now): array
    {
        return $this->rows('business', [
            ['How is coverage allocated to each employee?', 'Corporate coverage is isolated per employee. Each seat receives its own monthly consultation allocation, so one employee cannot consume another employee’s unused consultations.'],
            ['Do unused consultations roll over?', 'No. All unused scheduled and instant consultation allocations expire and reset on the monthly renewal date.'],
            ['What happens when I upgrade or downgrade the plan?', 'Upgrades take effect immediately after a prorated payment. Eligible downgrades also take effect immediately, with no charge or refund; a downgrade is blocked when current capacity or consultation usage exceeds the target limits.'],
            ['How are subscription payments collected?', "You can pay with your Wallet or the active external payment provider. Renewals try the Wallet first and use the subscription's saved reusable authorization only when the Wallet balance is insufficient."],
            ['Can I cancel anytime?', 'Yes. Coverage stays active until the end of the current billing cycle. Employees retain their own records and wallets afterward.'],
        ], $now);
    }

    /** @return list<array<string, mixed>> */
    private function institutionalQuestions(DateTimeInterface $now): array
    {
        return $this->rows('institution', [
            ['How is campaign funding allocated?', 'Funds added to the institutional wallet remain available until they are allocated to a campaign or used for booth and operating charges.'],
            ['What happens to unused campaign allocation?', 'When a campaign ends, unused and unencumbered healthcare allocation is returned to the institutional wallet.'],
            ['Are booth charges part of healthcare allocation?', 'No. Booth setup, monthly service fees, and configured operating costs are charged directly from the institutional wallet.'],
            ['Can the funding program be extended?', 'Yes. Extending the program changes its informational end date and does not add funds or change campaign allocations.'],
        ], $now);
    }

    /**
     * @param  list<array{0: string, 1: string}>  $questions
     * @return list<array<string, mixed>>
     */
    private function rows(string $accountType, array $questions, DateTimeInterface $now): array
    {
        return array_map(
            static fn (array $question, int $index): array => [
                'account_type' => $accountType,
                'question' => $question[0],
                'answer' => $question[1],
                'is_active' => true,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $questions,
            array_keys($questions),
        );
    }
};
