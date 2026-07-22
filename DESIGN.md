# BankFlow Design

[English](DESIGN.md) | [فارسی](DESIGN.fa.md)

## Architecture

BankFlow uses a layered Laravel architecture grouped around the loan domain:

```text
HTTP request
  -> Form Request / Filament page
  -> single-purpose Action
  -> WorkflowEngine or Eloquent query
  -> PostgreSQL
```

The HTTP layer owns transport concerns and JSON resources. Actions represent use cases and transaction boundaries. `app/Domain/Loan` contains framework-light domain values and workflow behavior. Eloquent models own relationships and persistence, but do not orchestrate processing.

The public API is versioned under `/api/v1`. Filament provides the authenticated administrative interface for loan inspection, loan types, stage definitions, and workflow versions.

## Workflow model and execution

A `LoanType` has multiple `WorkflowConfiguration` versions. Each configuration has ordered `WorkflowConfigurationStep` rows, and each step points to a `StageDefinition` plus a JSON rules object. Only a `PUBLISHED` workflow is assigned to a new loan. The loan retains that configuration ID, so later administrative changes cannot alter an in-flight or historical decision.

`config/workflow-stages.php` is the trusted implementation registry. It maps a stable stage code to a PHP class implementing `StageInterface`; database values never select arbitrary classes or executable expressions. Stage handlers are stateless and return an immutable `ExecutionResult` containing `PASS`, `FAIL`, or `MANUAL_REVIEW` and a reason code.

Processing is synchronous:

1. `ProcessLoanAction` opens a database transaction and locks the loan row.
2. A terminal loan is returned without further work.
3. `WorkflowEngine` reads enabled steps in `position` order.
4. Steps already present in loan history are skipped.
5. A conditional handler may declare that it does not apply; skipped stages create no history.
6. The handler executes with the step's validated rules.
7. A history row stores the stage, result, reason, execution time, and rules snapshot.
8. `FAIL` produces `REJECTED`; `MANUAL_REVIEW` produces `MANUAL_REVIEW`; otherwise the next ordered step is selected.
9. Passing all applicable steps produces `APPROVED`.

The current stage is therefore not hard-coded in a transition switch. It is the first enabled, applicable, unexecuted step in the loan's ordered workflow version.

## Rules and configuration lifecycle

Business thresholds and prefixes live in the `rules` JSON column on each configured step. Seeded workflows provide the challenge defaults. Stage-specific validation runs when an administrator creates or updates a draft and again before publication. The trusted registry remains a PHP configuration file because executable handlers are application code, while business values and order remain versioned database data.

Workflow versions move through `DRAFT -> PUBLISHED -> ARCHIVED`. Drafts are editable. Publishing validates the entire workflow and atomically archives the previous published version for that loan type. Published and archived versions are immutable.

## Persistence, state, and audit history

Eloquent persists loan types, stage definitions, workflow versions and steps, loans, and histories. Backed enums distinguish three concepts:

- loan status: `SUBMITTED`, `IN_PROGRESS`, `MANUAL_REVIEW`, `APPROVED`, `REJECTED`;
- executable stage: `VALIDATION`, `FRAUD_CHECK`, `GUARANTOR_CHECK`, `CREDIT_CHECK`, `MANAGER_APPROVAL`;
- stage result: `PASS`, `FAIL`, `MANUAL_REVIEW`.

`loans.current_workflow_configuration_step_id` exposes current progress, while `loan_histories` is the durable business audit trail. The history's rule snapshot preserves the exact inputs to a decision even if a future workflow uses different rules. Unexpected exceptions use normal Laravel logging and roll back the processing transaction.

Manual-review loans may later be approved by an authorized manager in Filament. That operation locks the loan and records the approver, timestamp, and note.

## Duplicate and concurrent processing

Idempotency is enforced at multiple levels:

- terminal statuses cause an immediate no-op response;
- executed workflow step IDs are derived from history and not run again;
- a unique constraint on `(loan_id, workflow_configuration_step_id)` prevents duplicate history rows;
- `SELECT ... FOR UPDATE` serializes concurrent processing of one loan;
- the complete workflow run and all history writes share one transaction, retried up to three times.

Database constraints also keep a loan's type, workflow, and current step consistent. PostgreSQL adds a partial unique index allowing only one published workflow per loan type.

## Adding `AML_CHECK`

Adding a stage requires:

1. add `AmlCheck` to `LoanStage`;
2. implement `StageInterface` (and `ConditionalStageInterface` if it can be skipped);
3. register `AML_CHECK` in `config/workflow-stages.php`;
4. define and validate its rule schema in `ValidateWorkflowStepDataAction`;
5. create/seed its `StageDefinition` and add it to a new draft workflow version;
6. add stage unit tests and feature tests for ordering, outcomes, history, and idempotency.

The engine and existing stage classes do not change. Publishing the new version affects only loans created afterward.

## Principal trade-offs

Synchronous, transaction-scoped execution makes correctness and API behavior easy to reason about, but long-running external checks would hold a request and database lock too long. Database-managed workflow order enables safe administration and historical reproducibility, at the cost of more schema and publication validation than static configuration. Direct Eloquent access keeps this application small and idiomatic, but would make a second persistence implementation more expensive.
