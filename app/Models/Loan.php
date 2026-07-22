<?php

namespace App\Models;

use App\Domain\Loan\Enums\LoanStatus;
use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'public_id',
    'customer_id',
    'loan_type_id',
    'workflow_configuration_id',
    'current_workflow_configuration_step_id',
    'amount',
    'phone',
    'monthly_income',
    'credit_score',
    'has_guarantor',
    'status',
    'manager_approved_by',
    'manager_approved_at',
    'manager_approval_note',
])]
class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => LoanStatus::Submitted->value,
    ];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function loanType(): BelongsTo
    {
        return $this->belongsTo(LoanType::class);
    }

    public function workflowConfiguration(): BelongsTo
    {
        return $this->belongsTo(WorkflowConfiguration::class);
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowConfigurationStep::class, 'current_workflow_configuration_step_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(LoanHistory::class);
    }

    public function managerApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_approved_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'monthly_income' => 'integer',
            'credit_score' => 'integer',
            'has_guarantor' => 'boolean',
            'status' => LoanStatus::class,
            'manager_approved_at' => 'datetime',
        ];
    }
}
