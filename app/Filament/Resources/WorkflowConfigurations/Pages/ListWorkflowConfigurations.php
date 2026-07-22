<?php

namespace App\Filament\Resources\WorkflowConfigurations\Pages;

use App\Domain\Loan\Enums\WorkflowConfigurationStatus;
use App\Filament\Resources\WorkflowConfigurations\WorkflowConfigurationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListWorkflowConfigurations extends ListRecords
{
    protected static string $resource = WorkflowConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New workflow draft')->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All versions'),
            'draft' => Tab::make('Drafts')
                ->icon('heroicon-o-pencil-square')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', WorkflowConfigurationStatus::Draft)),
            'published' => Tab::make('Published')
                ->icon('heroicon-o-check-badge')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', WorkflowConfigurationStatus::Published)),
            'archived' => Tab::make('Archive')
                ->icon('heroicon-o-archive-box')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', WorkflowConfigurationStatus::Archived)),
        ];
    }
}
