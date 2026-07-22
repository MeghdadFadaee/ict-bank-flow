<?php

namespace App\Models;

use App\Domain\Loan\Enums\LoanStage;
use App\Domain\Loan\Enums\StageResultType;
use Database\Factories\LoanHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'loan_id',
    'workflow_configuration_step_id',
    'stage_code',
    'rules_snapshot',
    'result',
    'reason',
    'executed_at',
])]
class LoanHistory extends Model
{
    /** @use HasFactory<LoanHistoryFactory> */
    use HasFactory;

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function workflowConfigurationStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowConfigurationStep::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage_code' => LoanStage::class,
            'rules_snapshot' => 'array',
            'result' => StageResultType::class,
            'executed_at' => 'datetime',
        ];
    }
}
