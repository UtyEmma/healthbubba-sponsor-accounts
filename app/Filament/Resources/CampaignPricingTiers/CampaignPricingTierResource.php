<?php

namespace App\Filament\Resources\CampaignPricingTiers;

use App\Filament\Resources\CampaignPricingTiers\Pages\CreateCampaignPricingTier;
use App\Filament\Resources\CampaignPricingTiers\Pages\EditCampaignPricingTier;
use App\Filament\Resources\CampaignPricingTiers\Pages\ListCampaignPricingTiers;
use App\Filament\Resources\CampaignPricingTiers\Pages\ViewCampaignPricingTier;
use App\Filament\Resources\CampaignPricingTiers\Schemas\CampaignPricingTierForm;
use App\Filament\Resources\CampaignPricingTiers\Schemas\CampaignPricingTierInfolist;
use App\Filament\Resources\CampaignPricingTiers\Tables\CampaignPricingTiersTable;
use App\Models\CampaignPricingTier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class CampaignPricingTierResource extends Resource
{
    protected static ?string $model = CampaignPricingTier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Campaign pricing';

    protected static ?string $modelLabel = 'campaign pricing tier';

    protected static ?string $pluralModelLabel = 'campaign pricing tiers';

    public static function form(Schema $schema): Schema
    {
        return CampaignPricingTierForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CampaignPricingTierInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CampaignPricingTiersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaignPricingTiers::route('/'),
            // 'create' => CreateCampaignPricingTier::route('/create'),
            // 'view' => ViewCampaignPricingTier::route('/{record}'),
            // 'edit' => EditCampaignPricingTier::route('/{record}/edit'),
        ];
    }
}
