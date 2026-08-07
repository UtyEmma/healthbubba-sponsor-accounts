<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Account\Roles;
use App\Enums\Account\Status;
use App\Enums\AccountTypes;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'xl' => 12])
                    ->schema([
                        Section::make('Profile')
                            ->description('The user identity and sign-in address.')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->columnSpanFull(),
                            ])
                            ->columns(12)
                            ->columnSpan(['default' => 'full', 'xl' => 8]),
                        Section::make('Access')
                            ->description('Control the portal, administrative role, and account state.')
                            ->schema([
                                Select::make('type')
                                    ->options(collect(AccountTypes::cases())
                                        ->mapWithKeys(fn (AccountTypes $type): array => [
                                            $type->value => $type->label(),
                                        ])
                                        ->all())
                                    ->nullable(),
                                Select::make('role')
                                    ->options(collect(Roles::cases())
                                        ->mapWithKeys(fn (Roles $role): array => [
                                            $role->value => str($role->value)->headline()->toString(),
                                        ])
                                        ->all())
                                    ->default(Roles::USER->value)
                                    ->required(),
                                Select::make('status')
                                    ->options(collect(Status::cases())
                                        ->mapWithKeys(fn (Status $status): array => [
                                            $status->value => $status->label(),
                                        ])
                                        ->all())
                                    ->default(Status::ACTIVE->value)
                                    ->required(),
                            ])
                            ->columns(1)
                            ->columnSpan(['default' => 'full', 'xl' => 4]),
                        Section::make('Security')
                            ->description('Set a password for new users or leave it blank to preserve the current password.')
                            ->schema([
                                TextInput::make('password')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                    ->maxLength(255)
                                    ->columnSpan(['default' => 'full', 'md' => 6]),
                            ])
                            ->columns(12)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
