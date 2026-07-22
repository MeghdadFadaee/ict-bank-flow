<?php

namespace App\Filament\Resources\WorkflowConfigurations\Pages;

use App\Actions\WorkflowConfiguration\CreateWorkflowConfigurationAction;
use App\Filament\Resources\WorkflowConfigurations\WorkflowConfigurationResource;
use App\Models\LoanType;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateWorkflowConfiguration extends CreateRecord
{
    protected static string $resource = WorkflowConfigurationResource::class;

    private CreateWorkflowConfigurationAction $createWorkflowConfiguration;

    public function boot(CreateWorkflowConfigurationAction $createWorkflowConfiguration): void
    {
        $this->createWorkflowConfiguration = $createWorkflowConfiguration;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $this->createWorkflowConfiguration->handle(
            LoanType::query()->findOrFail($data['loan_type_id']),
            $data['name'],
            $user,
            $data['steps'],
        );
    }
}
