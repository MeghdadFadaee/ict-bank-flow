<?php

namespace App\Filament\Resources\Loans\RelationManagers;

use App\Domain\Loan\Enums\StageResultType;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    protected static ?string $title = 'Execution history';

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('stage_code')->label('Stage')->badge()->color('info'),
                TextEntry::make('result')
                    ->badge()
                    ->color(fn (StageResultType $state): string => match ($state) {
                        StageResultType::Pass => 'success',
                        StageResultType::Fail => 'danger',
                        StageResultType::ManualReview => 'warning',
                    }),
                TextEntry::make('reason'),
                TextEntry::make('executed_at')->dateTime(),
                TextEntry::make('rules_snapshot')
                    ->label('Rule snapshot')
                    ->formatStateUsing(fn (array $state): string => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                    ->fontFamily('mono')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('stage_code')
            ->columns([
                TextColumn::make('stage_code')
                    ->label('Stage')
                    ->badge()
                    ->color('info'),
                TextColumn::make('result')
                    ->badge()
                    ->color(fn (StageResultType $state): string => match ($state) {
                        StageResultType::Pass => 'success',
                        StageResultType::Fail => 'danger',
                        StageResultType::ManualReview => 'warning',
                    }),
                TextColumn::make('reason')->wrap(),
                TextColumn::make('executed_at')->label('Executed')->dateTime()->sortable(),
            ])
            ->defaultSort('executed_at')
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading('No stages executed yet')
            ->emptyStateIcon('heroicon-o-clock');
    }
}
