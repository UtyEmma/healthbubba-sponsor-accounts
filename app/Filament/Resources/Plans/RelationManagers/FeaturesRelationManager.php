<?php

namespace App\Filament\Resources\Plans\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Revoltify\Subscriptionify\Enums\FeatureType;
use Revoltify\Subscriptionify\Enums\Interval;

class FeaturesRelationManager extends RelationManager
{
    protected static string $relationship = 'features';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'md' => 2])
                    ->schema($this->pivotFields()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (FeatureType $state): string => str($state->value)->headline()->toString()),
                TextColumn::make('value')
                    ->label('Allowance'),
                TextColumn::make('unit_price')
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('reset_period')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? 'Never' : (string) $state),
                TextColumn::make('reset_interval')
                    ->formatStateUsing(fn (?Interval $state): string => $state === null ? '—' : str($state->value)->headline()->toString()),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Grid::make(['default' => 1, 'md' => 2])
                            ->schema($this->pivotFields()),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }

    /** @return array<int, Component> */
    private function pivotFields(): array
    {
        return [
            TextInput::make('value')
                ->label('Allowance')
                ->default('0')
                ->required(),
            TextInput::make('unit_price')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),
            TextInput::make('reset_period')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->nullable(),
            Select::make('reset_interval')
                ->options(collect(Interval::cases())
                    ->mapWithKeys(fn (Interval $interval): array => [
                        $interval->value => str($interval->value)->headline()->toString(),
                    ])
                    ->all())
                ->nullable(),
        ];
    }
}
