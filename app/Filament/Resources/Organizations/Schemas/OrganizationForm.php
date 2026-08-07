<?php

namespace App\Filament\Resources\Organizations\Schemas;

use App\Enums\AccountTypes;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'xl' => 12])
                    ->schema([
                        Section::make('Organization profile')
                            ->description('Public identity and internal context for this sponsor account.')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Textarea::make('description')
                                    ->rows(7)
                                    ->columnSpanFull(),
                            ])
                            ->columns(12)
                            ->columnSpan(['default' => 'full', 'xl' => 8]),
                        Section::make('Account configuration')
                            ->description('Choose the eligible plan catalog and upload an optional logo.')
                            ->schema([
                                Select::make('type')
                                    ->options(collect(AccountTypes::cases())
                                        ->reject(fn (AccountTypes $type): bool => $type === AccountTypes::INDIVIDUAL)
                                        ->mapWithKeys(fn (AccountTypes $type): array => [
                                            $type->value => $type->label(),
                                        ])
                                        ->all())
                                    ->required(),
                                FileUpload::make('logo')
                                    ->image()
                                    ->disk('public')
                                    ->directory('organizations')
                                    ->maxSize(2048)
                                    ->imageEditor(),
                            ])
                            ->columns(1)
                            ->columnSpan(['default' => 'full', 'xl' => 4]),
                    ]),
            ]);
    }
}
