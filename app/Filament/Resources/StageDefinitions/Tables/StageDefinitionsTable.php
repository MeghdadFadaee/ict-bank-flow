<?php

namespace App\Filament\Resources\StageDefinitions\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StageDefinitionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                TextColumn::make('name')
                    ->weight('semibold')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Available')
                    ->boolean(),
                TextColumn::make('workflow_configuration_steps_count')
                    ->label('Workflow usage')
                    ->counts('workflowConfigurationSteps')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('name');
    }
}
