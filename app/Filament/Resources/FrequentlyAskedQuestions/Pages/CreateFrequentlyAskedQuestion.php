<?php

namespace App\Filament\Resources\FrequentlyAskedQuestions\Pages;

use App\Filament\Resources\FrequentlyAskedQuestions\FrequentlyAskedQuestionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFrequentlyAskedQuestion extends CreateRecord
{
    protected static string $resource = FrequentlyAskedQuestionResource::class;
}
