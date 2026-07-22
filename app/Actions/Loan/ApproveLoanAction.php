<?php

namespace App\Actions\Loan;

use App\Domain\Loan\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ApproveLoanAction
{
    public function handle(Loan $loan, User $manager, string $note): Loan
    {
        $validated = Validator::make(['note' => $note], [
            'note' => ['required', 'string', 'min:10', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($loan, $manager, $validated): Loan {
            $lockedLoan = Loan::query()
                ->lockForUpdate()
                ->findOrFail($loan->getKey());

            if ($lockedLoan->status !== LoanStatus::ManualReview) {
                throw ValidationException::withMessages([
                    'status' => 'Only loans awaiting manual review can be approved by a manager.',
                ]);
            }

            $lockedLoan->update([
                'status' => LoanStatus::Approved,
                'current_workflow_configuration_step_id' => null,
                'manager_approved_by' => $manager->getKey(),
                'manager_approved_at' => now(),
                'manager_approval_note' => $validated['note'],
            ]);

            return $lockedLoan->refresh()->load('managerApprover');
        });
    }
}
