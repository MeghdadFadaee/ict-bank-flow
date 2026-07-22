<?php

namespace App\Filament\Resources\WorkflowConfigurations\Schemas;

use App\Domain\Loan\Enums\WorkflowConfigurationStatus;
use App\Models\WorkflowConfigurationStep;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WorkflowConfigurationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Section::make('Workflow')
                            ->icon('heroicon-o-queue-list')
                            ->schema([
                                TextEntry::make('name')->weight('semibold'),
                                TextEntry::make('loanType.name')->label('Loan type'),
                                TextEntry::make('version')->badge()->prefix('Version '),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (WorkflowConfigurationStatus $state): string => str($state->value)->headline()->toString())
                                    ->color(fn (WorkflowConfigurationStatus $state): string => match ($state) {
                                        WorkflowConfigurationStatus::Draft => 'warning',
                                        WorkflowConfigurationStatus::Published => 'success',
                                        WorkflowConfigurationStatus::Archived => 'gray',
                                    }),
                            ])
                            ->columns(2)
                            ->columnSpan(2),
                        Section::make('Audit')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                TextEntry::make('creator.name')->label('Created by')->placeholder('System'),
                                TextEntry::make('published_at')->dateTime()->placeholder('Not published'),
                                TextEntry::make('loans_count')->label('Assigned loans')->numeric(),
                            ]),
                    ]),
                Section::make('Execution path')
                    ->description('The rule snapshot shown here is the policy attached to this exact version.')
                    ->icon('heroicon-o-arrows-right-left')
                    ->schema([
                        RepeatableEntry::make('steps')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('position')->label('#')->badge()->color('gray'),
                                TextEntry::make('stageDefinition.name')->label('Stage')->weight('semibold'),
                                TextEntry::make('stageDefinition.code')->label('Code')->badge()->color('info'),
                                IconEntry::make('is_enabled')->label('Enabled')->boolean(),
                                TextEntry::make('rules')
                                    ->label('Rules')
                                    ->state(fn (WorkflowConfigurationStep $record): string => collect($record->rules)
                                        ->map(fn (mixed $value, string $key): string => str($key)->headline().': '.(is_bool($value) ? ($value ? 'Yes' : 'No') : $value))
                                        ->implode(' · '))
                                    ->placeholder('No configurable rules')
                                    ->columnSpanFull(),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }
}
