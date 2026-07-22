<?php

namespace App\Actions\WorkflowConfiguration;

use App\Domain\Loan\Enums\WorkflowConfigurationStatus;
use App\Models\User;
use App\Models\WorkflowConfiguration;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishWorkflowConfigurationAction
{
    public function __construct(private ValidateWorkflowConfigurationAction $validateWorkflowConfiguration) {}

    public function handle(WorkflowConfiguration $workflowConfiguration, User $publisher): WorkflowConfiguration
    {
        return DB::transaction(function () use ($workflowConfiguration, $publisher): WorkflowConfiguration {
            $lockedConfiguration = WorkflowConfiguration::query()
                ->with(['loanType', 'steps.stageDefinition'])
                ->lockForUpdate()
                ->findOrFail($workflowConfiguration->getKey());

            if (! $lockedConfiguration->isEditable()) {
                throw ValidationException::withMessages([
                    'status' => 'Only a draft workflow configuration can be published.',
                ]);
            }

            $this->validateWorkflowConfiguration->handle($lockedConfiguration);

            WorkflowConfiguration::query()
                ->whereBelongsTo($lockedConfiguration->loanType)
                ->where('status', WorkflowConfigurationStatus::Published)
                ->lockForUpdate()
                ->update(['status' => WorkflowConfigurationStatus::Archived]);

            $lockedConfiguration->update([
                'status' => WorkflowConfigurationStatus::Published,
                'published_at' => now(),
                'created_by' => $publisher->getKey(),
            ]);

            return $lockedConfiguration->refresh()->load(['loanType', 'steps.stageDefinition', 'creator']);
        });
    }
}
