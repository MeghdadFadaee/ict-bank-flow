<?php

namespace App\Filament\Resources\LoanTypes\Pages;

use App\Filament\Resources\LoanTypes\LoanTypeResource;
use App\Models\LoanType;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;

class ListLoanTypes extends ListRecords
{
    protected static string $resource = LoanTypeResource::class;

    protected string $view = 'filament.resources.loan-types.pages.list-loan-types';

    #[Computed]
    public function loanTypeCards(): Collection
    {
        return LoanType::query()
            ->withCount([
                'loans',
                'loans as open_loans_count' => fn (Builder $query): Builder => $query
                    ->whereIn('status', ['SUBMITTED', 'IN_PROGRESS']),
            ])
            ->with(['workflowConfigurations' => fn ($query) => $query
                ->where('status', 'PUBLISHED')
                ->withCount('steps')
                ->latest('version')])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New loan type')
                ->icon('heroicon-o-plus'),
        ];
    }
}
