<?php

namespace App\Filament\Resources\Workspaces;

use App\Filament\Resources\Workspaces\Pages\EditWorkspace;
use App\Filament\Resources\Workspaces\Pages\ListWorkspaces;
use App\Filament\Resources\Workspaces\Pages\ViewWorkspace;
use App\Filament\Resources\Workspaces\RelationManagers\CampaignsRelationManager;
use App\Filament\Resources\Workspaces\Schemas\WorkspaceForm;
use App\Filament\Resources\Workspaces\Schemas\WorkspaceInfolist;
use App\Filament\Resources\Workspaces\Tables\WorkspacesTable;
use App\Models\Workspace;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class WorkspaceResource extends Resource
{
    protected static ?string $model = Workspace::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Account management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('latestCampaign')
            ->withCount(['members', 'campaigns']);
    }

    public static function form(Schema $schema): Schema
    {
        return WorkspaceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WorkspaceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkspacesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CampaignsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkspaces::route('/'),
            'view' => ViewWorkspace::route('/{record}'),
            'edit' => EditWorkspace::route('/{record}/edit'),
        ];
    }
}
