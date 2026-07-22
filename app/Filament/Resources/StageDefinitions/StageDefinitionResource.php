<?php

namespace App\Filament\Resources\StageDefinitions;

use App\Filament\Resources\StageDefinitions\Pages\CreateStageDefinition;
use App\Filament\Resources\StageDefinitions\Pages\EditStageDefinition;
use App\Filament\Resources\StageDefinitions\Pages\ListStageDefinitions;
use App\Filament\Resources\StageDefinitions\Pages\ViewStageDefinition;
use App\Filament\Resources\StageDefinitions\Schemas\StageDefinitionForm;
use App\Filament\Resources\StageDefinitions\Schemas\StageDefinitionInfolist;
use App\Filament\Resources\StageDefinitions\Tables\StageDefinitionsTable;
use App\Models\StageDefinition;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class StageDefinitionResource extends Resource
{
    protected static ?string $model = StageDefinition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Stage catalog';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return StageDefinitionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StageDefinitionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StageDefinitionsTable::configure($table);
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
            'index' => ListStageDefinitions::route('/'),
            'create' => CreateStageDefinition::route('/create'),
            'view' => ViewStageDefinition::route('/{record}'),
            'edit' => EditStageDefinition::route('/{record}/edit'),
        ];
    }
}
