<?php

namespace App\Filament\Resources\StageDefinitions\Schemas;

use App\Domain\Loan\Enums\LoanStage;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StageDefinitionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Trusted stage')
                    ->description('Only application-registered stage codes can be exposed to workflow designers.')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Select::make('code')
                            ->options(collect(LoanStage::cases())
                                ->mapWithKeys(fn (LoanStage $stage): array => [$stage->value => str($stage->value)->headline()->toString()])
                                ->all())
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabledOn('edit')
                            ->dehydrated(),
                        TextInput::make('name')
                            ->label('Admin-facing name')
                            ->required()
                            ->maxLength(120),
                        Toggle::make('is_active')
                            ->label('Available in workflow designer')
                            ->default(true)
                            ->inline(false)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
