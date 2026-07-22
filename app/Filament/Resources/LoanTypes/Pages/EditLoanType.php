<?php

namespace App\Filament\Resources\LoanTypes\Pages;

use App\Filament\Resources\LoanTypes\LoanTypeResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditLoanType extends EditRecord
{
    protected static string $resource = LoanTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['code']);

        if (($data['is_active'] ?? false)
            && ! $this->record->workflowConfigurations()->where('status', 'PUBLISHED')->exists()) {
            throw ValidationException::withMessages([
                'data.is_active' => 'Publish a workflow before activating this loan type.',
            ]);
        }

        return $data;
    }
}
