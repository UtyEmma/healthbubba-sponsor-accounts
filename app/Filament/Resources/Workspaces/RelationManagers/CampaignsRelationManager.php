<?php

namespace App\Filament\Resources\Workspaces\RelationManagers;

use App\Enums\AccountTypes;
use App\Enums\CampaignBoothStatus;
use App\Filament\Resources\Campaigns\Schemas\CampaignForm;
use App\Filament\Resources\Campaigns\Tables\CampaignsTable;
use App\Models\Campaign;
use App\Models\Workspace;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CampaignsRelationManager extends RelationManager
{
    protected static string $relationship = 'campaigns';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Workspace
            && $ownerRecord->type === AccountTypes::INSTITUTION;
    }

    public function form(Schema $schema): Schema
    {
        return CampaignForm::configure($schema, includeWorkspace: false);
    }

    public function table(Table $table): Table
    {
        return CampaignsTable::configure($table, includeWorkspace: false)
            ->modifyQueryUsing(static fn (Builder $query): Builder => $query->withCount([
                'booths as active_booths_count' => static fn (Builder $query): Builder => $query
                    ->whereIn('status', [
                        CampaignBoothStatus::Active,
                        CampaignBoothStatus::GracePeriod,
                        CampaignBoothStatus::Suspended,
                    ]),
                'recurringCosts as active_recurring_costs_count' => static fn (Builder $query): Builder => $query
                    ->where('is_active', true),
            ]))
            ->headerActions([
                CreateAction::make()
                    ->databaseTransaction()
                    ->after(static function (Campaign $record): void {
                        if ($record->workspace->onboarded_at === null) {
                            $record->workspace->update(['onboarded_at' => now()]);
                        }
                    }),
            ]);
    }
}
