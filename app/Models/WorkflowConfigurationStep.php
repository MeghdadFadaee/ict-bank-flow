<?php

namespace App\Models;

use Database\Factories\WorkflowConfigurationStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workflow_configuration_id', 'stage_definition_id', 'position', 'rules', 'is_enabled'])]
class WorkflowConfigurationStep extends Model
{
    /** @use HasFactory<WorkflowConfigurationStepFactory> */
    use HasFactory;

    public function workflowConfiguration(): BelongsTo
    {
        return $this->belongsTo(WorkflowConfiguration::class);
    }

    public function stageDefinition(): BelongsTo
    {
        return $this->belongsTo(StageDefinition::class);
    }

    public function loanHistories(): HasMany
    {
        return $this->hasMany(LoanHistory::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'is_enabled' => 'boolean',
        ];
    }
}
