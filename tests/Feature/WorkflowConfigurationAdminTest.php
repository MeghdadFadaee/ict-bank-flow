<?php

use App\Actions\WorkflowConfiguration\CreateWorkflowConfigurationAction;
use App\Actions\WorkflowConfiguration\PublishWorkflowConfigurationAction;
use App\Domain\Loan\Enums\LoanStage;
use App\Domain\Loan\Enums\WorkflowConfigurationStatus;
use App\Filament\Resources\WorkflowConfigurations\Pages\ViewWorkflowConfiguration;
use App\Models\LoanType;
use App\Models\StageDefinition;
use App\Models\User;
use App\Models\WorkflowConfiguration;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('a workflow with configured rules can be viewed', function () {
    $user = User::factory()->create();
    $stage = StageDefinition::factory()->forStage(LoanStage::FraudCheck)->create();
    $workflow = WorkflowConfiguration::factory()
        ->withStep($stage, 1, [
            'fraudPrefix' => 'FRAUD',
            'guarantorRequired' => true,
        ])
        ->create();

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::actingAs($user)
        ->test(ViewWorkflowConfiguration::class, ['record' => $workflow->getRouteKey()])
        ->assertOk()
        ->assertSee('Fraud Prefix: FRAUD')
        ->assertSee('Guarantor Required: Yes');
});

test('copying a workflow creates an isolated next draft version', function () {
    $user = User::factory()->create();
    $loanType = LoanType::factory()->create(['code' => 'PERSONAL']);
    $stage = StageDefinition::factory()->forStage(LoanStage::Validation)->create();
    $source = WorkflowConfiguration::factory()
        ->for($loanType, 'loanType')
        ->published()
        ->withStep($stage, 1)
        ->create(['version' => 1]);

    $draft = app(CreateWorkflowConfigurationAction::class)->handle(
        $loanType,
        'Next personal workflow',
        $user,
        copyFrom: $source,
    );

    expect($draft->version)->toBe(2)
        ->and($draft->status)->toBe(WorkflowConfigurationStatus::Draft)
        ->and($draft->steps)->toHaveCount(1)
        ->and($draft->steps->first()->getKey())->not->toBe($source->steps->first()->getKey());
});

test('publishing archives the previous version atomically', function () {
    $user = User::factory()->create();
    $loanType = LoanType::factory()->create(['code' => 'PERSONAL']);
    $stages = collect(LoanStage::cases())->mapWithKeys(fn (LoanStage $stage): array => [
        $stage->value => StageDefinition::factory()->forStage($stage)->create(),
    ]);

    $published = WorkflowConfiguration::factory()
        ->for($loanType, 'loanType')
        ->published()
        ->create(['version' => 1]);

    $draft = WorkflowConfiguration::factory()
        ->for($loanType, 'loanType')
        ->create(['version' => 2]);

    $stepDefinitions = [
        [LoanStage::Validation, []],
        [LoanStage::FraudCheck, ['fraudPrefix' => 'FRAUD', 'manualReviewPrefix' => 'REVIEW']],
        [LoanStage::CreditCheck, ['rejectBelow' => 500, 'manualReviewMin' => 500, 'manualReviewMax' => 649, 'approveFrom' => 650]],
        [LoanStage::ManagerApproval, ['activationThreshold' => 500_000_000, 'incomeMultiplier' => 20]],
    ];

    foreach ($stepDefinitions as $index => [$stage, $rules]) {
        $draft->steps()->create([
            'stage_definition_id' => $stages[$stage->value]->getKey(),
            'position' => $index + 1,
            'rules' => $rules,
            'is_enabled' => true,
        ]);
    }

    app(PublishWorkflowConfigurationAction::class)->handle($draft, $user);

    expect($draft->refresh()->status)->toBe(WorkflowConfigurationStatus::Published)
        ->and($draft->published_at)->not->toBeNull()
        ->and($published->refresh()->status)->toBe(WorkflowConfigurationStatus::Archived);
});

test('publishing rejects a credit policy with a range gap', function () {
    $user = User::factory()->create();
    $loanType = LoanType::factory()->create(['code' => 'PERSONAL']);
    $stages = collect(LoanStage::cases())->mapWithKeys(fn (LoanStage $stage): array => [
        $stage->value => StageDefinition::factory()->forStage($stage)->create(),
    ]);
    $draft = WorkflowConfiguration::factory()->for($loanType, 'loanType')->create();

    $definitions = [
        [LoanStage::Validation, []],
        [LoanStage::FraudCheck, ['fraudPrefix' => 'FRAUD', 'manualReviewPrefix' => 'REVIEW']],
        [LoanStage::CreditCheck, ['rejectBelow' => 500, 'manualReviewMin' => 500, 'manualReviewMax' => 649, 'approveFrom' => 700]],
        [LoanStage::ManagerApproval, ['activationThreshold' => 500_000_000, 'incomeMultiplier' => 20]],
    ];

    foreach ($definitions as $index => [$stage, $rules]) {
        $draft->steps()->create([
            'stage_definition_id' => $stages[$stage->value]->getKey(),
            'position' => $index + 1,
            'rules' => $rules,
            'is_enabled' => true,
        ]);
    }

    expect(fn () => app(PublishWorkflowConfigurationAction::class)->handle($draft, $user))
        ->toThrow(ValidationException::class);

    expect($draft->refresh()->status)->toBe(WorkflowConfigurationStatus::Draft);
});
