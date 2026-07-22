<?php

namespace App\Filament\Resources\WorkflowConfigurations\Tables;

use App\Actions\WorkflowConfiguration\CreateWorkflowConfigurationAction;
use App\Actions\WorkflowConfiguration\PublishWorkflowConfigurationAction;
use App\Domain\Loan\Enums\WorkflowConfigurationStatus;
use App\Filament\Resources\WorkflowConfigurations\WorkflowConfigurationResource;
use App\Models\User;
use App\Models\WorkflowConfiguration;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WorkflowConfigurationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('loanType.name')
                    ->label('Loan type')
                    ->icon('heroicon-o-building-library')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->weight('semibold')
                    ->searchable(),
                TextColumn::make('version')
                    ->badge()
                    ->prefix('v')
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (WorkflowConfigurationStatus $state): string => str($state->value)->headline()->toString())
                    ->color(fn (WorkflowConfigurationStatus $state): string => match ($state) {
                        WorkflowConfigurationStatus::Draft => 'warning',
                        WorkflowConfigurationStatus::Published => 'success',
                        WorkflowConfigurationStatus::Archived => 'gray',
                    }),
                TextColumn::make('steps_count')
                    ->label('Stages')
                    ->counts('steps')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('loans_count')
                    ->label('Assigned')
                    ->counts('loans')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('published_at')
                    ->label('Published')
                    ->since()
                    ->placeholder('Not yet')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('loan_type_id')
                    ->label('Loan type')
                    ->relationship('loanType', 'name')
                    ->preload(),
                SelectFilter::make('status')
                    ->options(collect(WorkflowConfigurationStatus::cases())
                        ->mapWithKeys(fn (WorkflowConfigurationStatus $status): array => [$status->value => str($status->value)->headline()->toString()])
                        ->all()),
            ])
            ->defaultSort('version', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (WorkflowConfiguration $record): bool => $record->isEditable()),
                Action::make('copy')
                    ->label('Copy as draft')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->schema([
                        TextInput::make('name')
                            ->label('New draft name')
                            ->required()
                            ->maxLength(150),
                    ])
                    ->fillForm(fn (WorkflowConfiguration $record): array => ['name' => "{$record->name} copy"])
                    ->action(function (
                        array $data,
                        WorkflowConfiguration $record,
                        CreateWorkflowConfigurationAction $createWorkflowConfiguration,
                    ) {
                        $user = auth()->user();
                        abort_unless($user instanceof User, 403);

                        $draft = $createWorkflowConfiguration->handle(
                            $record->loanType,
                            $data['name'],
                            $user,
                            copyFrom: $record,
                        );

                        Notification::make()->title('Draft copy created')->success()->send();

                        return redirect(WorkflowConfigurationResource::getUrl('edit', ['record' => $draft]));
                    }),
                Action::make('publish')
                    ->icon('heroicon-o-rocket-launch')
                    ->color('success')
                    ->visible(fn (WorkflowConfiguration $record): bool => $record->isEditable())
                    ->requiresConfirmation()
                    ->modalHeading('Publish this workflow version?')
                    ->modalDescription('The currently published workflow for this loan type will be archived. Existing loans keep their assigned version.')
                    ->action(function (
                        WorkflowConfiguration $record,
                        PublishWorkflowConfigurationAction $publishWorkflowConfiguration,
                    ): void {
                        $user = auth()->user();
                        abort_unless($user instanceof User, 403);

                        $publishWorkflowConfiguration->handle($record, $user);

                        Notification::make()->title('Workflow published')->success()->send();
                    }),
            ])
            ->emptyStateHeading('No workflow versions yet')
            ->emptyStateDescription('Create a draft, arrange its stages, then publish it for new applications.')
            ->emptyStateIcon('heroicon-o-queue-list');
    }
}
