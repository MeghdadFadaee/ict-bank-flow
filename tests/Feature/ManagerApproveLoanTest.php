<?php

use App\Actions\Loan\ApproveLoanAction;
use App\Domain\Loan\Enums\LoanStatus;
use App\Filament\Resources\Loans\Pages\ListLoans;
use App\Models\Loan;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('a manager can approve a loan awaiting manual review from the table', function () {
    $manager = User::factory()->create();
    $loan = Loan::factory()->create([
        'status' => LoanStatus::ManualReview,
        'current_workflow_configuration_step_id' => null,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::actingAs($manager)
        ->test(ListLoans::class)
        ->assertTableActionVisible('managerApprove', $loan)
        ->callTableAction('managerApprove', $loan, [
            'note' => 'Income documents were verified by the lending manager.',
        ])
        ->assertHasNoTableActionErrors();

    $loan->refresh();

    expect($loan->status)->toBe(LoanStatus::Approved)
        ->and($loan->manager_approved_by)->toBe($manager->getKey())
        ->and($loan->manager_approved_at)->not->toBeNull()
        ->and($loan->manager_approval_note)->toBe('Income documents were verified by the lending manager.');
});

test('the manager approval action is hidden for loans not awaiting review', function () {
    $manager = User::factory()->create();
    $loan = Loan::factory()->create(['status' => LoanStatus::Submitted]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    Livewire::actingAs($manager)
        ->test(ListLoans::class)
        ->assertTableActionHidden('managerApprove', $loan);

    expect($manager->can('approve', $loan))->toBeFalse();
});

test('the action rejects stale approval attempts after status changes', function () {
    $manager = User::factory()->create();
    $loan = Loan::factory()->create(['status' => LoanStatus::Rejected]);

    expect(fn () => app(ApproveLoanAction::class)->handle(
        $loan,
        $manager,
        'Attempt to approve a loan that is no longer awaiting review.',
    ))->toThrow(ValidationException::class);

    expect($loan->refresh()->status)->toBe(LoanStatus::Rejected)
        ->and($loan->manager_approved_at)->toBeNull();
});
