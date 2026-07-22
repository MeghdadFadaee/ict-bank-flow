<?php

namespace App\Filament\Resources\Loans\Schemas;

use App\Domain\Loan\Enums\LoanStatus;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LoanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Section::make('Application state')
                            ->icon('heroicon-o-signal')
                            ->schema([
                                TextEntry::make('public_id')->label('Loan ID')->copyable()->weight('semibold'),
                                TextEntry::make('status')
                                    ->badge()
                                    ->formatStateUsing(fn (LoanStatus $state): string => str($state->value)->headline()->toString())
                                    ->color(fn (LoanStatus $state): string => match ($state) {
                                        LoanStatus::Submitted => 'gray',
                                        LoanStatus::InProgress => 'info',
                                        LoanStatus::ManualReview => 'warning',
                                        LoanStatus::Approved => 'success',
                                        LoanStatus::Rejected => 'danger',
                                    }),
                                TextEntry::make('currentStep.stageDefinition.name')
                                    ->label('Current stage')
                                    ->placeholder('Processing complete'),
                                TextEntry::make('workflowConfiguration.name')
                                    ->label('Workflow')
                                    ->formatStateUsing(fn (string $state, $record): string => "{$state} · v{$record->workflowConfiguration->version}"),
                            ])
                            ->columns(2)
                            ->columnSpan(2),
                        Section::make('Timeline')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                TextEntry::make('created_at')->label('Submitted')->dateTime(),
                                TextEntry::make('updated_at')->label('Last activity')->since(),
                                TextEntry::make('histories_count')->label('Executed stages')->numeric(),
                            ]),
                    ]),
                Section::make('Applicant and terms')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        TextEntry::make('customer_id')->label('Customer ID')->copyable(),
                        TextEntry::make('phone')->copyable(),
                        TextEntry::make('loanType.name')->label('Loan type'),
                        TextEntry::make('amount')->numeric()->suffix(' IRR'),
                        TextEntry::make('monthly_income')->label('Monthly income')->numeric()->suffix(' IRR'),
                        TextEntry::make('credit_score')->label('Credit score')->numeric()->suffix(' / 1000'),
                        IconEntry::make('has_guarantor')->label('Has guarantor')->boolean(),
                    ])
                    ->columns(4),
                Section::make('Manager approval')
                    ->icon('heroicon-o-check-badge')
                    ->visible(fn ($record): bool => $record->manager_approved_at !== null)
                    ->schema([
                        TextEntry::make('managerApprover.name')->label('Approved by')->placeholder('Deleted user'),
                        TextEntry::make('manager_approved_at')->label('Approved at')->dateTime(),
                        TextEntry::make('manager_approval_note')
                            ->label('Decision note')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
