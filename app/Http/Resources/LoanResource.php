<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'loanId' => $this->public_id,
            'customerId' => $this->customer_id,
            'amount' => $this->amount,
            'phone' => $this->phone,
            'loanType' => $this->loanType->code,
            'monthlyIncome' => $this->monthly_income,
            'creditScore' => $this->credit_score,
            'hasGuarantor' => $this->has_guarantor,
            'status' => $this->status->value,
            'currentStage' => $this->currentStep?->stageDefinition->code,
            'createdAt' => $this->created_at->toIso8601ZuluString(),
            'updatedAt' => $this->updated_at->toIso8601ZuluString(),
        ];
    }
}
