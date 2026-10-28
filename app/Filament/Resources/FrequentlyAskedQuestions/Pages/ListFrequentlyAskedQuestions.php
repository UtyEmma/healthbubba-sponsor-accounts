<?php

namespace App\Filament\Resources\FrequentlyAskedQuestions\Pages;

use App\Filament\Resources\FrequentlyAskedQuestions\FrequentlyAskedQuestionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFrequentlyAskedQuestions extends ListRecords
{
    protected static string $resource = FrequentlyAskedQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
