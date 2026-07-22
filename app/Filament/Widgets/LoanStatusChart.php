<?php

namespace App\Filament\Widgets;

use App\Domain\Loan\Enums\LoanStatus;
use App\Models\Loan;
use Filament\Widgets\ChartWidget;

class LoanStatusChart extends ChartWidget
{
    protected ?string $heading = 'Portfolio by status';

    protected ?string $description = 'Distribution across the automatic processing lifecycle.';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $counts = Loan::query()
            ->select('status')
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $statuses = LoanStatus::cases();

        return [
            'datasets' => [[
                'label' => 'Loans',
                'data' => array_map(fn (LoanStatus $status): int => (int) $counts->get($status->value, 0), $statuses),
                'backgroundColor' => ['#94a3b8', '#38bdf8', '#f59e0b', '#10b981', '#ef4444'],
                'borderWidth' => 0,
            ]],
            'labels' => array_map(fn (LoanStatus $status): string => str($status->value)->headline()->toString(), $statuses),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
