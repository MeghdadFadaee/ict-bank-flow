<?php

namespace App\Filament\Resources\WorkflowConfigurations\Schemas;

use App\Domain\Loan\Enums\LoanStage;
use App\Models\StageDefinition;
use App\Models\WorkflowConfiguration;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;

class WorkflowConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Workflow identity')
                    ->description('Drafts are isolated versions. Publishing never changes loans already assigned to an older version.')
                    ->icon('heroicon-o-finger-print')
                    ->schema([
                        Select::make('loan_type_id')
                            ->label('Loan type')
                            ->relationship('loanType', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (?WorkflowConfiguration $record): bool => $record !== null)
                            ->dehydrated(),
                        TextInput::make('name')
                            ->placeholder('e.g. Personal lending — summer policy')
                            ->required()
                            ->maxLength(150),
                        Placeholder::make('version_summary')
                            ->label('Version')
                            ->content(fn (?WorkflowConfiguration $record): string => $record === null
                                ? 'Assigned automatically when the draft is created'
                                : "Version {$record->version} · {$record->status->value}"),
                    ])
                    ->columns(3),
                Section::make('Execution path')
                    ->description('Drag stages into execution order. Rule fields change with the selected stage.')
                    ->icon('heroicon-o-arrows-right-left')
                    ->schema([
                        Repeater::make('steps')
                            ->hiddenLabel()
                            ->schema([
                                Select::make('stage_definition_id')
                                    ->label('Stage')
                                    ->options(fn (): array => StageDefinition::query()
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->required()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, mixed $state): void {
                                        $stageCode = StageDefinition::query()->whereKey($state)->value('code');
                                        $set('rules', self::defaultRules($stageCode));
                                    }),
                                Toggle::make('is_enabled')
                                    ->label('Enabled')
                                    ->default(true)
                                    ->inline(false),
                                Grid::make(4)
                                    ->schema(fn (Get $get): array => self::ruleFields(
                                        StageDefinition::query()->whereKey($get('stage_definition_id'))->value('code'),
                                    ))
                                    ->key('stageRules')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): string => StageDefinition::query()
                                ->whereKey($state['stage_definition_id'] ?? null)
                                ->value('name') ?? 'Choose a stage')
                            ->addActionLabel('Add workflow stage')
                            ->reorderable()
                            ->collapsible()
                            ->minItems(1)
                            ->required(),
                    ]),
            ]);
    }

    /**
     * @return array<int, TextInput|Toggle|Placeholder>
     */
    private static function ruleFields(?string $stageCode): array
    {
        return match ($stageCode) {
            LoanStage::FraudCheck->value => [
                TextInput::make('rules.fraudPrefix')
                    ->label('Reject prefix')
                    ->required()
                    ->maxLength(50),
                TextInput::make('rules.manualReviewPrefix')
                    ->label('Manual-review prefix')
                    ->required()
                    ->maxLength(50),
            ],
            LoanStage::GuarantorCheck->value => [
                Toggle::make('rules.guarantorRequired')
                    ->label('Guarantor required')
                    ->default(true)
                    ->inline(false),
            ],
            LoanStage::CreditCheck->value => [
                TextInput::make('rules.rejectBelow')
                    ->label('Reject below')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(1000)
                    ->required(),
                TextInput::make('rules.manualReviewMin')
                    ->label('Review from')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(1000)
                    ->required(),
                TextInput::make('rules.manualReviewMax')
                    ->label('Review through')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(1000)
                    ->required(),
                TextInput::make('rules.approveFrom')
                    ->label('Approve from')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(1000)
                    ->required(),
            ],
            LoanStage::ManagerApproval->value => [
                TextInput::make('rules.activationThreshold')
                    ->label('Activation threshold')
                    ->helperText('Manager approval is skipped at or below this amount.')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('IRR')
                    ->formatStateUsing(fn (mixed $state): mixed => $state)
                    ->required()
                    ->columnSpan(2),
                TextInput::make('rules.incomeMultiplier')
                    ->label('Income multiplier')
                    ->numeric()
                    ->minValue(1)
                    ->suffix('×')
                    ->required(),
                Placeholder::make('threshold_preview')
                    ->label('Default policy')
                    ->content(fn (Get $get): string => Number::format((int) ($get('rules.activationThreshold') ?? 0)).' IRR'),
            ],
            LoanStage::Validation->value => [
                Placeholder::make('validation_policy')
                    ->label('Built-in validation')
                    ->content('Transport-safe values are checked against the BankFlow validation policy.')
                    ->columnSpanFull(),
            ],
            default => [
                Placeholder::make('choose_stage')
                    ->label('Stage rules')
                    ->content('Choose a stage to configure its policy.')
                    ->columnSpanFull(),
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function defaultRules(?string $stageCode): array
    {
        return match ($stageCode) {
            LoanStage::FraudCheck->value => [
                'fraudPrefix' => 'FRAUD',
                'manualReviewPrefix' => 'REVIEW',
            ],
            LoanStage::GuarantorCheck->value => ['guarantorRequired' => true],
            LoanStage::CreditCheck->value => [
                'rejectBelow' => 500,
                'manualReviewMin' => 500,
                'manualReviewMax' => 649,
                'approveFrom' => 650,
            ],
            LoanStage::ManagerApproval->value => [
                'activationThreshold' => 500_000_000,
                'incomeMultiplier' => 20,
            ],
            default => [],
        };
    }
}
