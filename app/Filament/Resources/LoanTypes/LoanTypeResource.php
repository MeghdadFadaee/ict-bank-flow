<?php

namespace App\Filament\Resources\LoanTypes;

use App\Filament\Resources\LoanTypes\Pages\CreateLoanType;
use App\Filament\Resources\LoanTypes\Pages\EditLoanType;
use App\Filament\Resources\LoanTypes\Pages\ListLoanTypes;
use App\Filament\Resources\LoanTypes\Pages\ViewLoanType;
use App\Filament\Resources\LoanTypes\RelationManagers\LoansRelationManager;
use App\Filament\Resources\LoanTypes\RelationManagers\WorkflowConfigurationsRelationManager;
use App\Filament\Resources\LoanTypes\Schemas\LoanTypeForm;
use App\Filament\Resources\LoanTypes\Schemas\LoanTypeInfolist;
use App\Filament\Resources\LoanTypes\Tables\LoanTypesTable;
use App\Models\LoanType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LoanTypeResource extends Resource
{
    protected static ?string $model = LoanType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Loan types';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return LoanTypeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LoanTypeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoanTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            WorkflowConfigurationsRelationManager::class,
            LoansRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoanTypes::route('/'),
            'create' => CreateLoanType::route('/create'),
            'view' => ViewLoanType::route('/{record}'),
            'edit' => EditLoanType::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['loans', 'workflowConfigurations'])
            ->with(['workflowConfigurations' => fn ($query) => $query
                ->where('status', 'PUBLISHED')
                ->latest('version')]);
    }
}
