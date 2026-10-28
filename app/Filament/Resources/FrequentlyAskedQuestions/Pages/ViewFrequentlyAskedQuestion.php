<?php

namespace App\Filament\Resources\FrequentlyAskedQuestions\Pages;

use App\Filament\Resources\FrequentlyAskedQuestions\FrequentlyAskedQuestionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFrequentlyAskedQuestion extends ViewRecord
{
    protected static string $resource = FrequentlyAskedQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
