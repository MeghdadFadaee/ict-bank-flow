<?php

namespace App\Models;

use Database\Factories\LoanTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'is_active'])]
class LoanType extends Model
{
    /** @use HasFactory<LoanTypeFactory> */
    use HasFactory;

    public function workflowConfigurations(): HasMany
    {
        return $this->hasMany(WorkflowConfiguration::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
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
