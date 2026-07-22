<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'stage' => $this->stage_code->value,
            'result' => $this->result->value,
            'timestamp' => $this->executed_at->toIso8601ZuluString(),
            'reason' => $this->reason,
        ];
    }
}
