<?php

namespace App\Filament\Resources\FrequentlyAskedQuestions;

use App\Filament\Resources\FrequentlyAskedQuestions\Pages\CreateFrequentlyAskedQuestion;
use App\Filament\Resources\FrequentlyAskedQuestions\Pages\EditFrequentlyAskedQuestion;
use App\Filament\Resources\FrequentlyAskedQuestions\Pages\ListFrequentlyAskedQuestions;
use App\Filament\Resources\FrequentlyAskedQuestions\Pages\ViewFrequentlyAskedQuestion;
use App\Filament\Resources\FrequentlyAskedQuestions\Schemas\FrequentlyAskedQuestionForm;
use App\Filament\Resources\FrequentlyAskedQuestions\Schemas\FrequentlyAskedQuestionInfolist;
use App\Filament\Resources\FrequentlyAskedQuestions\Tables\FrequentlyAskedQuestionsTable;
use App\Models\FrequentlyAskedQuestion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class FrequentlyAskedQuestionResource extends Resource
{
    protected static ?string $model = FrequentlyAskedQuestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $navigationLabel = 'FAQs';

    protected static ?string $modelLabel = 'frequently asked question';

    protected static ?string $pluralModelLabel = 'frequently asked questions';

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'question';

    public static function form(Schema $schema): Schema
    {
        return FrequentlyAskedQuestionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FrequentlyAskedQuestionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FrequentlyAskedQuestionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFrequentlyAskedQuestions::route('/'),
            'create' => CreateFrequentlyAskedQuestion::route('/create'),
            'view' => ViewFrequentlyAskedQuestion::route('/{record}'),
            'edit' => EditFrequentlyAskedQuestion::route('/{record}/edit'),
        ];
    }
}
