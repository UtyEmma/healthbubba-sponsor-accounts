<?php

namespace App\Filament\Resources\Workspaces\RelationManagers;

use App\Enums\AccountTypes;
use App\Filament\Resources\Campaigns\Schemas\CampaignForm;
use App\Filament\Resources\Campaigns\Tables\CampaignsTable;
use App\Models\Campaign;
use App\Models\Workspace;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
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
