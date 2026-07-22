<?php

namespace App\Filament\Resources\StageDefinitions\Pages;

use App\Domain\Loan\Enums\WorkflowConfigurationStatus;
use App\Filament\Resources\StageDefinitions\StageDefinitionResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditStageDefinition extends EditRecord
{
    protected static string $resource = StageDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['code']);

        if (! ($data['is_active'] ?? false)
            && $this->record->workflowConfigurationSteps()
                ->whereHas('workflowConfiguration', fn ($query) => $query
                    ->where('status', WorkflowConfigurationStatus::Published))
                ->exists()) {
            throw ValidationException::withMessages([
                'data.is_active' => 'This stage is used by a published workflow and cannot be disabled.',
            ]);
        }

        return $data;
    }
}
