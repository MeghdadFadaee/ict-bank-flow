<?php

namespace App\Filament\Resources\WorkflowConfigurations\Pages;

use App\Actions\WorkflowConfiguration\PublishWorkflowConfigurationAction;
use App\Filament\Resources\WorkflowConfigurations\WorkflowConfigurationResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkflowConfiguration extends ViewRecord
{
    protected static string $resource = WorkflowConfigurationResource::class;

    private PublishWorkflowConfigurationAction $publishWorkflowConfiguration;

    public function boot(PublishWorkflowConfigurationAction $publishWorkflowConfiguration): void
    {
        $this->publishWorkflowConfiguration = $publishWorkflowConfiguration;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => $this->record->isEditable()),
            Action::make('publish')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->visible(fn (): bool => $this->record->isEditable())
                ->requiresConfirmation()
                ->action(function (): void {
                    $user = auth()->user();
                    abort_unless($user instanceof User, 403);

                    $this->record = $this->publishWorkflowConfiguration->handle($this->record, $user);
                    Notification::make()->title('Workflow published')->success()->send();
                }),
        ];
    }
}
