<?php

namespace App\Filament\Resources\LoanTypes\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LoanTypeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Section::make('Product')
                            ->icon('heroicon-o-building-library')
                            ->schema([
                                TextEntry::make('name')->label('Display name'),
                                TextEntry::make('code')->badge()->color('gray'),
                                IconEntry::make('is_active')
                                    ->label('Accepting applications')
                                    ->boolean(),
                            ])
                            ->columnSpan(2),
                        Section::make('Portfolio')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                TextEntry::make('loans_count')
                                    ->label('Applications')
                                    ->numeric(),
                                TextEntry::make('workflow_configurations_count')
                                    ->label('Workflow versions')
                                    ->numeric(),
                                TextEntry::make('updated_at')
                                    ->label('Last changed')
                                    ->since()
                                    ->placeholder('Never'),
                            ]),
                    ]),
            ]);
    }
}
