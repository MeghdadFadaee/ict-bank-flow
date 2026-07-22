<?php

namespace App\Filament\Resources\LoanTypes\RelationManagers;

use App\Filament\Resources\WorkflowConfigurations\WorkflowConfigurationResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class WorkflowConfigurationsRelationManager extends RelationManager
{
    protected static string $relationship = 'workflowConfigurations';

    protected static ?string $relatedResource = WorkflowConfigurationResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('version', 'desc');
    }
}
