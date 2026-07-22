<?php

namespace App\Filament\Resources\LoanTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LoanTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Loan product identity')
                    ->description('The code is a permanent API identifier. The display name can evolve with the product.')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        TextInput::make('name')
                            ->label('Display name')
                            ->placeholder('e.g. Personal Flex Loan')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, callable $set, string $operation): void {
                                if ($operation === 'create') {
                                    $set('code', Str::of($state ?? '')->trim()->upper()->snake()->toString());
                                }
                            }),
                        TextInput::make('code')
                            ->label('Stable API code')
                            ->helperText('Uppercase letters, numbers, and underscores only. It cannot be changed later.')
                            ->required()
                            ->maxLength(50)
                            ->regex('/^[A-Z][A-Z0-9_]*$/')
                            ->unique(ignoreRecord: true)
                            ->disabledOn('edit')
                            ->dehydrated(),
                        Toggle::make('is_active')
                            ->label('Accept new applications')
                            ->helperText('Existing applications and workflow history are never affected.')
                            ->default(false)
                            ->disabledOn('create')
                            ->dehydrated()
                            ->inline(false)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
