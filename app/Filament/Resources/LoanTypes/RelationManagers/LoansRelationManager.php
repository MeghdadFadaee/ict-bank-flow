<?php

namespace App\Filament\Resources\LoanTypes\RelationManagers;

use App\Filament\Resources\Loans\LoanResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class LoansRelationManager extends RelationManager
{
    protected static string $relationship = 'loans';

    protected static ?string $relatedResource = LoanResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc');
    }
}
