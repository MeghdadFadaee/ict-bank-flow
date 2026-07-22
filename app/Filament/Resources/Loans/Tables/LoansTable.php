<?php

namespace App\Filament\Resources\Loans\Tables;

use App\Actions\Loan\ApproveLoanAction;
use App\Domain\Loan\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LoansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('public_id')
                    ->label('Loan ID')
                    ->copyable()
                    ->weight('semibold')
                    ->searchable(),
                TextColumn::make('customer_id')
                    ->label('Customer')
                    ->description(fn ($record): string => $record->phone)
                    ->searchable(['customer_id', 'phone']),
                TextColumn::make('loanType.name')
                    ->label('Product')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('amount')
                    ->numeric()
                    ->suffix(' IRR')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('credit_score')
                    ->label('Credit')
                    ->numeric()
                    ->suffix(' / 1000')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (LoanStatus $state): string => str($state->value)->headline()->toString())
                    ->color(fn (LoanStatus $state): string => match ($state) {
                        LoanStatus::Submitted => 'gray',
                        LoanStatus::InProgress => 'info',
                        LoanStatus::ManualReview => 'warning',
                        LoanStatus::Approved => 'success',
                        LoanStatus::Rejected => 'danger',
                    }),
                TextColumn::make('currentStep.stageDefinition.name')
                    ->label('Current stage')
                    ->placeholder('Complete')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('loan_type_id')
                    ->label('Loan type')
                    ->relationship('loanType', 'name')
                    ->preload(),
                SelectFilter::make('status')
                    ->multiple()
                    ->options(collect(LoanStatus::cases())
                        ->mapWithKeys(fn (LoanStatus $status): array => [$status->value => str($status->value)->headline()->toString()])
                        ->all()),
                SelectFilter::make('workflow_configuration_id')
                    ->label('Workflow version')
                    ->relationship('workflowConfiguration', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('managerApprove')
                    ->label('Manager approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->authorize('approve')
                    ->visible(fn (Loan $record): bool => $record->status === LoanStatus::ManualReview)
                    ->modalHeading('Approve this loan?')
                    ->modalDescription('This is a final manager decision. The automatic workflow will not run again.')
                    ->modalSubmitActionLabel('Approve loan')
                    ->schema([
                        Textarea::make('note')
                            ->label('Manager decision note')
                            ->placeholder('Explain why this application is approved...')
                            ->helperText('This note is stored with the loan for audit purposes.')
                            ->required()
                            ->minLength(10)
                            ->maxLength(500)
                            ->rows(4),
                    ])
                    ->action(function (array $data, Loan $record, ApproveLoanAction $approveLoan): void {
                        $manager = auth()->user();
                        abort_unless($manager instanceof User, 403);

                        $approveLoan->handle($record, $manager, $data['note']);

                        Notification::make()
                            ->title('Loan approved')
                            ->body("{$record->public_id} was approved by manager decision.")
                            ->success()
                            ->send();
                    }),
                ViewAction::make(),
            ])
            ->emptyStateHeading('No loan applications')
            ->emptyStateDescription('Applications submitted through the API will appear here.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
