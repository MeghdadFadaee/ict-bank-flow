<?php

namespace App\Filament\Widgets;

use App\Domain\Loan\Enums\LoanStatus;
use App\Domain\Loan\Enums\WorkflowConfigurationStatus;
use App\Filament\Resources\Loans\LoanResource;
use App\Filament\Resources\WorkflowConfigurations\WorkflowConfigurationResource;
use App\Models\Loan;
use App\Models\WorkflowConfiguration;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class BankFlowStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Lending operations';

    protected ?string $description = 'A live view of application throughput and workflow readiness.';

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $totalLoans = Loan::query()->count();
        $openLoans = Loan::query()->whereIn('status', [LoanStatus::Submitted, LoanStatus::InProgress])->count();
        $manualReviewLoans = Loan::query()->where('status', LoanStatus::ManualReview)->count();
        $publishedWorkflows = WorkflowConfiguration::query()
            ->where('status', WorkflowConfigurationStatus::Published)
            ->count();

        return [
            Stat::make('Total applications', Number::format($totalLoans))
                ->description('Complete lending portfolio')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->url(LoanResource::getUrl()),
            Stat::make('Open processing', Number::format($openLoans))
                ->description($openLoans === 0 ? 'Processing queue is clear' : 'Submitted or in progress')
                ->descriptionIcon($openLoans === 0 ? 'heroicon-m-check-circle' : 'heroicon-m-arrow-path')
                ->color($openLoans === 0 ? 'success' : 'info')
                ->url(LoanResource::getUrl()),
            Stat::make('Manual review', Number::format($manualReviewLoans))
                ->description($manualReviewLoans === 0 ? 'No applications waiting' : 'Requires operational attention')
                ->descriptionIcon($manualReviewLoans === 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($manualReviewLoans === 0 ? 'success' : 'warning')
                ->url(LoanResource::getUrl()),
            Stat::make('Published workflows', Number::format($publishedWorkflows))
                ->description('Active version assignments')
                ->descriptionIcon('heroicon-m-rocket-launch')
                ->color('success')
                ->url(WorkflowConfigurationResource::getUrl()),
        ];
    }
}
