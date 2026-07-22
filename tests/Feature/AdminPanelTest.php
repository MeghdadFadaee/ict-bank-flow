<?php

use App\Domain\Loan\Enums\WorkflowConfigurationStatus;
use App\Models\Loan;
use App\Models\LoanType;
use App\Models\User;
use App\Models\WorkflowConfiguration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('an authenticated administrator can open the admin resources', function () {
    $user = User::factory()->create();
    LoanType::factory()->create([
        'code' => 'PERSONAL',
        'name' => 'Personal Loan',
    ]);

    $this->actingAs($user)
        ->get('/admin/loan-types')
        ->assertSuccessful()
        ->assertSee('Shape every lending journey from one place.')
        ->assertSee('Personal Loan');

    $this->actingAs($user)
        ->get('/admin/workflow-configurations')
        ->assertSuccessful();

    $this->actingAs($user)
        ->get('/admin/workflow-configurations/create')
        ->assertSuccessful()
        ->assertSee('Execution path');

    $this->actingAs($user)
        ->get('/admin/stage-definitions')
        ->assertSuccessful();

    $this->actingAs($user)
        ->get('/admin/loans')
        ->assertSuccessful();
});

test('loans are read only in the admin panel', function () {
    $user = User::factory()->create();
    $loan = Loan::factory()->create();

    expect($user->can('view', $loan))->toBeTrue()
        ->and($user->can('create', Loan::class))->toBeFalse()
        ->and($user->can('update', $loan))->toBeFalse()
        ->and($user->can('delete', $loan))->toBeFalse();

    $this->actingAs($user)
        ->get('/admin/loans/create')
        ->assertNotFound();

    $this->actingAs($user)
        ->get('/admin/loans/'.$loan->public_id)
        ->assertSuccessful()
        ->assertSee($loan->public_id);
});

test('only draft workflow configurations are editable', function () {
    $user = User::factory()->create();
    $draft = WorkflowConfiguration::factory()->create();
    $published = WorkflowConfiguration::factory()->published()->create();

    expect($draft->status)->toBe(WorkflowConfigurationStatus::Draft)
        ->and($user->can('update', $draft))->toBeTrue()
        ->and($user->can('update', $published))->toBeFalse();
});

test('unverified users cannot access the admin panel', function () {
    $user = User::factory()->unverified()->create();

    expect($user->canAccessPanel(filament()->getPanel('admin')))->toBeFalse();
});
