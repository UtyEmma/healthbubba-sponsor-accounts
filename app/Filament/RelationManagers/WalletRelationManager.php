<?php

namespace App\Filament\RelationManagers;

use App\Models\Organization;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WalletRelationManager extends RelationManager
{
    protected static string $relationship = 'wallets';

    protected static ?string $title = 'Wallet';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'md' => 2])
                    ->schema([
                        Section::make('Wallet balance')
                            ->description('Set the available balance for this account wallet.')
                            ->schema([
                                TextInput::make('balance')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('₦')
                                    ->default(0)
                                    ->required(),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('balance')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Last updated'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(function (): bool {
                        $owner = $this->getOwnerRecord();

                        if ($owner instanceof User && $owner->isAdmin()) {
                            return false;
                        }

                        if (! $owner instanceof User && ! $owner instanceof Organization) {
                            return false;
                        }

                        return ! $owner->wallets()->exists();
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
