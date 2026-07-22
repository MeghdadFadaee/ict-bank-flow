<?php

namespace App\Models;

use Database\Factories\StageDefinitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'is_active'])]
class StageDefinition extends Model
{
    /** @use HasFactory<StageDefinitionFactory> */
    use HasFactory;

    public function workflowConfigurationSteps(): HasMany
    {
        return $this->hasMany(WorkflowConfigurationStep::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
