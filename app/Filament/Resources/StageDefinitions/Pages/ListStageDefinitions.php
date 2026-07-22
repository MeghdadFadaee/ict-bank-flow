<?php

namespace App\Filament\Resources\StageDefinitions\Pages;

use App\Filament\Resources\StageDefinitions\StageDefinitionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStageDefinitions extends ListRecords
{
    protected static string $resource = StageDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
