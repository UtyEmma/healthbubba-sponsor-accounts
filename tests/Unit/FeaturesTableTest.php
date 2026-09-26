<?php

use App\Filament\Resources\Features\Tables\FeaturesTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Tests\TestCase;

uses(TestCase::class);

it('configures feature drag ordering through the sort order column', function (): void {
    $table = FeaturesTable::configure(Table::make(Mockery::mock(HasTable::class)));

    expect($table->getReorderColumn())->toBe('sort_order')
        ->and($table->getDefaultSortColumn())->toBe('sort_order');
});
