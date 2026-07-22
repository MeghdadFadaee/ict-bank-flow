<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanProcessResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'loanId' => $this->public_id,
            'status' => $this->status->value,
            'currentStage' => $this->currentStep?->stageDefinition->code,
        ];
    }
}
