<?php

namespace App\Filament\Resources\Plans\Schemas;

use App\Enums\AccountTypes;
use App\Models\Plan;
use App\Services\Payments\CapacityPricingService;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Revoltify\Subscriptionify\Enums\Interval;

class PlanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan details')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('slug')->copyable(),
                        TextEntry::make('account_type')
                            ->badge()
                            ->formatStateUsing(fn (AccountTypes $state): string => $state->label()),
                        TextEntry::make('description')
                            ->placeholder('No description')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Billing')
                    ->schema([
                        TextEntry::make('price')->money('NGN'),
                        TextEntry::make('included_seats')
                            ->label('Included seats')
                            ->placeholder('Not configured')
                            ->visible(fn (Plan $record): bool => $record->account_type === AccountTypes::BUSINESS),
                        TextEntry::make('additional_seat_price')
                            ->label('Additional seat price')
                            ->money('NGN')
                            ->placeholder('Not configured')
                            ->visible(fn (Plan $record): bool => $record->account_type === AccountTypes::BUSINESS),
                        TextEntry::make('individual_included_capacity')
                            ->label('Included beneficiaries')
                            ->state(fn (Plan $record, CapacityPricingService $pricing): ?int => $pricing
                                ->configuration($record)?->includedCapacity)
                            ->placeholder('Not configured')
                            ->visible(fn (Plan $record): bool => $record->account_type === AccountTypes::INDIVIDUAL),
                        TextEntry::make('individual_maximum_capacity')
                            ->label('Maximum beneficiaries')
                            ->state(fn (Plan $record, CapacityPricingService $pricing): ?int => $pricing
                                ->configuration($record)?->maximumCapacity)
                            ->placeholder('Not configured')
                            ->visible(fn (Plan $record): bool => $record->account_type === AccountTypes::INDIVIDUAL),
                        TextEntry::make('individual_capacity_unit_price')
                            ->label('Additional beneficiary price')
                            ->state(fn (Plan $record, CapacityPricingService $pricing): ?string => $pricing
                                ->configuration($record)?->unitPrice?->toMajorAmount())
                            ->money('NGN')
                            ->placeholder('Not configured')
                            ->visible(fn (Plan $record): bool => $record->account_type === AccountTypes::INDIVIDUAL),
                        TextEntry::make('billing_period')
                            ->formatStateUsing(fn (int $state): string => (string) $state),
                        TextEntry::make('billing_interval')
                            ->formatStateUsing(fn (Interval $state): string => str($state->value)->headline()->toString()),
                        TextEntry::make('trial_days')->suffix(' days'),
                        TextEntry::make('grace_days')->suffix(' days'),
                        IconEntry::make('is_free')->boolean(),
                        IconEntry::make('is_active')->boolean(),
                        IconEntry::make('allows_capacity_purchases')
                            ->label('Additional capacity purchases')
                            ->boolean()
                            ->visible(fn (Plan $record): bool => $record->account_type !== AccountTypes::INSTITUTION),
                        TextEntry::make('sort_order'),
                    ])
                    ->columns(3),
                Section::make('Audit')
                    ->schema([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
