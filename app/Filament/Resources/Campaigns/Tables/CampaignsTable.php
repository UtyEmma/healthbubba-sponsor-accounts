<?php

namespace App\Filament\Resources\Campaigns\Tables;

use App\Enums\AccountTypes;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CampaignsTable
{
    public static function configure(Table $table, bool $includeWorkspace = true): Table
    {
        $columns = [];

        if ($includeWorkspace) {
            $columns[] = TextColumn::make('workspace.name')
                ->label('Workspace')
                ->searchable()
                ->sortable();
        }

        $columns = [
            ...$columns,
            TextColumn::make('name')
                ->label('Campaign')
                ->searchable()
                ->sortable(),
            TextColumn::make('status')
                ->badge()
                ->formatStateUsing(fn ($record): string => $record->lifecycleStatus()->label())
                ->sortable(),
            TextColumn::make('slug')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('beneficiaries_count')
                ->label('Beneficiaries')
                ->counts('beneficiaries')
                ->numeric()
                ->sortable(),
            TextColumn::make('beneficiary_limit')
                ->label('Limit')
                ->numeric()
                ->sortable(),
            TextColumn::make('start_date')
                ->label('Starts')
                ->date()
                ->placeholder('Not provided')
                ->sortable(),
            TextColumn::make('end_date')
                ->label('Ends')
                ->date()
                ->placeholder('Not provided')
                ->sortable(),
            TextColumn::make('location')
                ->placeholder('Not provided')
                ->searchable()
                ->toggleable(),
            TextColumn::make('city')
                ->placeholder('Not provided')
                ->searchable()
                ->toggleable(),
            TextColumn::make('state')
                ->placeholder('Not provided')
                ->searchable()
                ->toggleable(),
            TextColumn::make('target_audience')
                ->label('Target audience')
                ->placeholder('Not provided')
                ->limit(40)
                ->toggleable(isToggledHiddenByDefault: true),
            IconColumn::make('booth_required')
                ->label('Booth')
                ->boolean()
                ->sortable(),
            TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns($columns)
            ->filters([
                SelectFilter::make('workspace')
                    ->relationship(
                        name: 'workspace',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->where('type', AccountTypes::INSTITUTION->value),
                    )
                    ->searchable()
                    ->preload()
                    ->visible($includeWorkspace),
                TernaryFilter::make('booth_required')
                    ->label('Booth required'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
