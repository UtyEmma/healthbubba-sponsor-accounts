<?php

namespace App\Filament\Resources\Campaigns\RelationManagers;

use App\Enums\CampaignRecurringCostCategory;
use App\Models\Campaign;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class RecurringCostsRelationManager extends RelationManager
{
    protected static string $relationship = 'recurringCosts';

    protected static ?string $title = 'Monthly operating costs';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('category')->default(CampaignRecurringCostCategory::Operating->value),
            Hidden::make('currency')->default('NGN'),
            TextInput::make('name')->required()->maxLength(120),
            TextInput::make('monthly_amount')
                ->label('Monthly amount')
                ->numeric()
                ->prefix('₦')
                ->minValue(0.01)
                ->required(),
            Grid::make(['default' => 1, 'md' => 2])->schema([
                DatePicker::make('starts_on')->required(),
                DatePicker::make('ends_on')->afterOrEqual('starts_on'),
            ]),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(static fn ($query) => $query->where('category', CampaignRecurringCostCategory::Operating))
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('monthly_amount')->money('NGN')->label('Monthly amount'),
                TextColumn::make('starts_on')->date(),
                TextColumn::make('ends_on')->date()->placeholder('Campaign end'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->databaseTransaction()
                    ->mutateDataUsing(function (array $data): array {
                        /** @var Campaign $campaign */
                        $campaign = $this->getOwnerRecord();

                        return [
                            ...$data,
                            'workspace_id' => $campaign->workspace_id,
                            'category' => CampaignRecurringCostCategory::Operating->value,
                            'currency' => $campaign->currency,
                        ];
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
