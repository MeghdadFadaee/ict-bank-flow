<?php

namespace App\Filament\Resources\WorkflowConfigurations;

use App\Filament\Resources\WorkflowConfigurations\Pages\CreateWorkflowConfiguration;
use App\Filament\Resources\WorkflowConfigurations\Pages\EditWorkflowConfiguration;
use App\Filament\Resources\WorkflowConfigurations\Pages\ListWorkflowConfigurations;
use App\Filament\Resources\WorkflowConfigurations\Pages\ViewWorkflowConfiguration;
use App\Filament\Resources\WorkflowConfigurations\RelationManagers\LoansRelationManager;
use App\Filament\Resources\WorkflowConfigurations\Schemas\WorkflowConfigurationForm;
use App\Filament\Resources\WorkflowConfigurations\Schemas\WorkflowConfigurationInfolist;
use App\Filament\Resources\WorkflowConfigurations\Tables\WorkflowConfigurationsTable;
use App\Models\WorkflowConfiguration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class WorkflowConfigurationResource extends Resource
{
    protected static ?string $model = WorkflowConfiguration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Workflows';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return WorkflowConfigurationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WorkflowConfigurationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkflowConfigurationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LoansRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkflowConfigurations::route('/'),
            'create' => CreateWorkflowConfiguration::route('/create'),
            'view' => ViewWorkflowConfiguration::route('/{record}'),
            'edit' => EditWorkflowConfiguration::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['loanType', 'creator'])
            ->withCount(['steps', 'loans']);
    }
}
