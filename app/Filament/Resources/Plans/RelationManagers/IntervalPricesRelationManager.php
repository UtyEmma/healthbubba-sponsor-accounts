<?php

namespace App\Filament\Resources\Plans\RelationManagers;

use App\Enums\AccountTypes;
use App\Enums\Subscriptions\IndividualPlanBillingCycle;
use App\Models\Plan;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;

final class IntervalPricesRelationManager extends RelationManager
{
    protected static string $relationship = 'intervalPrices';

    protected static ?string $title = 'Individual billing interval prices';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Plan
            && $ownerRecord->account_type === AccountTypes::INDIVIDUAL;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('billing_cycle')
                ->options(collect([
                    IndividualPlanBillingCycle::QUARTERLY,
                    IndividualPlanBillingCycle::BIANNUAL,
                    IndividualPlanBillingCycle::ANNUAL,
                ])->mapWithKeys(fn (IndividualPlanBillingCycle $cycle): array => [
                    $cycle->value => $cycle->label(),
                ])->all())
                ->unique(
                    table: 'plan_interval_prices',
                    column: 'billing_cycle',
                    modifyRuleUsing: fn (Unique $rule): Unique => $rule
                        ->where('plan_id', $this->getOwnerRecord()->getKey()),
                    ignoreRecord: true,
                )
                ->required(),
            TextInput::make('price')
                ->numeric()
                ->minValue(1)
                ->prefix(config()->string('payments.currency', 'NGN'))
                ->required(),
            Toggle::make('is_active')->default(true),
            TextInput::make('sort_order')
                ->numeric()
                ->integer()
                ->minValue(0)
                ->default(0)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('billing_cycle')
                    ->formatStateUsing(fn (IndividualPlanBillingCycle $state): string => $state->label())
                    ->sortable(),
                TextColumn::make('price')->money(config()->string('payments.currency', 'NGN'))->sortable(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()])
            ->defaultSort('sort_order');
    }
}
