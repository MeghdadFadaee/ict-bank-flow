<?php

namespace App\Filament\Resources\LoanTypes\Pages;

use App\Filament\Resources\LoanTypes\LoanTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLoanType extends ViewRecord
{
    protected static string $resource = LoanTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
