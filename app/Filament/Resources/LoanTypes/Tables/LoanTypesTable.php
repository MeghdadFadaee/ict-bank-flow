<?php

namespace App\Filament\Resources\LoanTypes\Tables;

use App\Models\LoanType;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class LoanTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('name')
                    ->description(fn (LoanType $record): string => $record->is_active ? 'Accepting new applications' : 'Paused for new applications')
                    ->weight('semibold')
                    ->searchable(),
                TextColumn::make('loans_count')
                    ->label('Applications')
                    ->counts('loans')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('workflow_configurations_count')
                    ->label('Versions')
                    ->counts('workflowConfigurations')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('is_active')
                    ->label('Availability')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Paused')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Availability')
                    ->trueLabel('Active products')
                    ->falseLabel('Paused products'),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('toggleAvailability')
                    ->label(fn (LoanType $record): string => $record->is_active ? 'Pause applications' : 'Resume applications')
                    ->icon(fn (LoanType $record): string => $record->is_active ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (LoanType $record): string => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalDescription(fn (LoanType $record): string => $record->is_active
                        ? 'New applications will no longer be accepted for this loan type. Existing loans stay untouched.'
                        : 'New applications can be assigned to the currently published workflow again.')
                    ->action(function (LoanType $record): void {
                        Gate::authorize('update', $record);

                        if (! $record->is_active && ! $record->workflowConfigurations()->where('status', 'PUBLISHED')->exists()) {
                            Notification::make()
                                ->title('Publish a workflow first')
                                ->body('This product cannot accept applications until it has a published workflow version.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update(['is_active' => ! $record->is_active]);

                        Notification::make()
                            ->title($record->is_active ? 'Applications resumed' : 'Applications paused')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Create your first loan product')
            ->emptyStateDescription('A loan type becomes ready after it has an active published workflow.')
            ->emptyStateIcon('heroicon-o-building-library');
    }
}
