<?php

namespace App\Filament\Resources\WorkflowConfigurations\Pages;

use App\Actions\WorkflowConfiguration\PublishWorkflowConfigurationAction;
use App\Actions\WorkflowConfiguration\UpdateWorkflowConfigurationAction;
use App\Filament\Resources\WorkflowConfigurations\WorkflowConfigurationResource;
use App\Models\User;
use App\Models\WorkflowConfiguration;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditWorkflowConfiguration extends EditRecord
{
    protected static string $resource = WorkflowConfigurationResource::class;

    private UpdateWorkflowConfigurationAction $updateWorkflowConfiguration;

    private PublishWorkflowConfigurationAction $publishWorkflowConfiguration;

    public function boot(
        UpdateWorkflowConfigurationAction $updateWorkflowConfiguration,
        PublishWorkflowConfigurationAction $publishWorkflowConfiguration,
    ): void {
        $this->updateWorkflowConfiguration = $updateWorkflowConfiguration;
        $this->publishWorkflowConfiguration = $publishWorkflowConfiguration;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var WorkflowConfiguration $record */
        $record = $this->record;

        $data['steps'] = $record->steps()
            ->get(['stage_definition_id', 'rules', 'is_enabled'])
            ->map(fn ($step): array => [
                'stage_definition_id' => $step->stage_definition_id,
                'rules' => $step->rules,
                'is_enabled' => $step->is_enabled,
            ])
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var WorkflowConfiguration $record */
        return $this->updateWorkflowConfiguration->handle($record, $data['name'], $data['steps']);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            Action::make('publish')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('This archives the currently published version. Existing loans remain attached to their original workflow.')
                ->action(function () {
                    $user = auth()->user();
                    abort_unless($user instanceof User, 403);

                    $published = $this->publishWorkflowConfiguration->handle($this->record, $user);
                    Notification::make()->title('Workflow published')->success()->send();

                    return redirect(WorkflowConfigurationResource::getUrl('view', ['record' => $published]));
                }),
        ];
    }
}
