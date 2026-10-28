<?php

namespace App\Filament\Resources\CampaignPricingTiers\Schemas;

use App\Models\CampaignPricingTier;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class CampaignPricingTierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Beneficiary range')
                    ->description('Define a non-overlapping range. Leave the maximum empty for an open-ended tier.')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            TextInput::make('minimum_beneficiaries')
                                ->label('Minimum beneficiaries')
                                ->numeric()
                                ->integer()
                                ->minValue(1)
                                ->required(),
                            TextInput::make('maximum_beneficiaries')
                                ->label('Maximum beneficiaries')
                                ->numeric()
                                ->integer()
                                ->nullable()
                                ->gte('minimum_beneficiaries')
                                ->minValue(1)
                                ->helperText('Leave empty for this tier to cover every larger campaign.'),
                        ]),
                    ]),
                Section::make('Charges')
                    ->description('These prices are snapshotted when a new campaign is launched.')
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])->schema([
                            TextInput::make('beneficiary_unit_fee')
                                ->label('One-time fee per beneficiary')
                                ->numeric()
                                ->minValue(0.01)
                                ->required(),
                            TextInput::make('monthly_service_fee')
                                ->label('Monthly campaign service fee')
                                ->numeric()
                                ->minValue(0.01)
                                ->nullable()
                                ->helperText('Leave empty when this tier has no monthly campaign service fee.'),
                            TextInput::make('currency')
                                ->label('Currency')
                                ->default('NGN')
                                ->minLength(3)
                                ->maxLength(3)
                                ->dehydrateStateUsing(static fn (string $state): string => mb_strtoupper($state))
                                ->required(),
                            TextInput::make('sort_order')
                                ->label('Display order')
                                ->numeric()
                                ->integer()
                                ->minValue(0)
                                ->default(fn (): int => ((int) CampaignPricingTier::query()->max('sort_order')) + 1)
                                ->required(),
                        ]),
                        Toggle::make('is_active')
                            ->label('Available for new campaigns')
                            ->default(true),
                    ]),
            ]);
    }
}
