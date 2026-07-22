<?php

namespace App\Models;

use App\Domain\Loan\Enums\WorkflowConfigurationStatus;
use Database\Factories\WorkflowConfigurationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['loan_type_id', 'name', 'version', 'status', 'published_at', 'created_by'])]
class WorkflowConfiguration extends Model
{
    /** @use HasFactory<WorkflowConfigurationFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => WorkflowConfigurationStatus::Draft->value,
    ];

    public function loanType(): BelongsTo
    {
        return $this->belongsTo(LoanType::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowConfigurationStep::class)->orderBy('position');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isEditable(): bool
    {
        return $this->status === WorkflowConfigurationStatus::Draft;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WorkflowConfigurationStatus::class,
            'published_at' => 'datetime',
        ];
    }
}
