<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, array<string, string>> */
    private const VALUES = [
        'type' => [
            'TOPUP' => 'topup',
            'SUBSCRIPTION' => 'subscription',
        ],
        'status' => [
            'PENDING' => 'pending',
            'COMPLETED' => 'completed',
            'FAILED' => 'failed',
        ],
        'flow' => [
            'DEBIT' => 'debit',
            'CREDIT' => 'credit',
        ],
    ];

    public function up(): void
    {
        foreach (self::VALUES as $column => $values) {
            foreach ($values as $legacyValue => $value) {
                DB::table('transactions')
                    ->where($column, $legacyValue)
                    ->update([$column => $value]);
            }
        }
    }

    public function down(): void
    {
        foreach (self::VALUES as $column => $values) {
            foreach ($values as $legacyValue => $value) {
                DB::table('transactions')
                    ->where($column, $value)
                    ->update([$column => $legacyValue]);
            }
        }
    }
};
