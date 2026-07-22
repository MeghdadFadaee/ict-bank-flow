<?php

namespace App\Filament\Resources\StageDefinitions\Pages;

use App\Filament\Resources\StageDefinitions\StageDefinitionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStageDefinition extends ViewRecord
{
    protected static string $resource = StageDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
