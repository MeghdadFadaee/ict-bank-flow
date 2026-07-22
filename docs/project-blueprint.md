# BankFlow Project Blueprint

## 1. Purpose

This document is the implementation blueprint for the BankFlow challenge. It converts the approved architectural decisions into a fixed Laravel project structure, domain model, workflow design, database schema, API boundary, and test plan.

Implementation must remain consistent with:

- [BankFlow requirements](./bank-flow.md)
- [BankFlow API reference](./api-routes.md)

When this blueprint and a general Laravel convention differ, this blueprint controls the project-specific architecture. The functional requirements and API contract remain the source of truth for observable behavior.

## 2. Fixed technical decisions

| Concern | Decision |
| --- | --- |
| Framework | Laravel 13 on PHP 8.4 |
| Database | PostgreSQL |
| Persistence | Eloquent ORM |
| Architecture | Layered Laravel architecture with loan-domain grouping |
| Application operations | Single-purpose Action classes |
| Service layer | No generic `LoanService` |
| Repository pattern | Not used; Eloquent is the only persistence implementation |
| Workflow | Ordered collection of independent Stage classes |
| Stage state | Stateless |
| Workflow definitions | Versioned database configuration per `loan_type_id` |
| Business-rule values | Validated JSON on configured Workflow steps |
| Stage implementation registry | Trusted code mapping in `config/workflow-stages.php` |
| Domain values | PHP backed Enums, except database-managed Loan types |
| Processing model | Synchronous |
| Queue | Not used |
| Transaction boundary | One database transaction for the complete processing operation |
| Concurrency control | Row lock plus database uniqueness constraints |
| Business history | Stored in `loan_histories` |
| Operational logging | Standard Laravel logging for unexpected failures |
| Tests | Pest unit and feature tests |

This database-managed configuration design supersedes the earlier proposal to store Workflow order in `config/workflows.php` and rule values in `config/loan.php`. Those files must not be introduced as a second source of truth.

### 2.1 Explicitly excluded patterns

The initial implementation must not introduce:

- a Repository layer over Eloquent;
- a generic or oversized `LoanService`;
- asynchronous workflow Jobs;
- Horizon, Kafka, RabbitMQ, or another broker;
- a generic Rule Engine or executable rule language;
- Clean Architecture or Hexagonal boilerplate;
- event sourcing, CQRS, or microservices.

These patterns solve problems outside the challenge scope and would add implementation and operational complexity without improving the required behavior.

## 3. Architectural flow

```text
HTTP Request
    ↓
Form Request (transport structure only)
    ↓
LoanController
    ↓
Single-purpose Action
    ↓
WorkflowEngine or Eloquent Model
    ↓
PostgreSQL
```

Workflow processing follows this flow:

```text
ProcessLoanAction
    ↓
Database transaction + loan row lock
    ↓
Loan's immutable WorkflowConfiguration
    ↓
WorkflowEngine
    ↓
Ordered stateless Stage instances
    ↓
Loan + LoanHistory persistence
```

### Layer responsibilities

| Layer | Responsibility |
| --- | --- |
| HTTP | Parse requests, authorize access, invoke an Action, and return API Resources |
| Actions | Implement one application use case and own its transaction boundary |
| Domain | Define enums, execution results, workflow orchestration, and business stages |
| Models | Persist loans and their execution history through Eloquent |
| Configuration models | Store versioned Workflow order and rules per Loan type |

Controllers must not contain business rules. Models must not orchestrate the Workflow. Stage classes must not produce HTTP responses or write logs as business history.

## 4. Project structure

```text
app/
├── Actions/
│   ├── Loan/
│   │   ├── CreateLoanAction.php
│   │   ├── GetLoanAction.php
│   │   ├── GetLoanHistoryAction.php
│   │   └── ProcessLoanAction.php
│   └── WorkflowConfiguration/
│       ├── CreateWorkflowConfigurationAction.php
│       ├── PublishWorkflowConfigurationAction.php
│       └── UpdateWorkflowConfigurationAction.php
│
├── Domain/
│   └── Loan/
│       ├── Contracts/
│       │   └── StageInterface.php
│       ├── Data/
│       │   └── ExecutionResult.php
│       ├── Enums/
│       │   ├── LoanStage.php
│       │   ├── LoanStatus.php
│       │   ├── StageResultType.php
│       │   └── WorkflowConfigurationStatus.php
│       ├── Exceptions/
│       │   ├── InvalidWorkflowConfiguration.php
│       │   └── WorkflowException.php
│       └── Workflow/
│           ├── Stages/
│           │   ├── CreditCheckStage.php
│           │   ├── FraudCheckStage.php
│           │   ├── GuarantorCheckStage.php
│           │   ├── ManagerApprovalStage.php
│           │   └── ValidationStage.php
│           ├── WorkflowConfigurationResolver.php
│           └── WorkflowEngine.php
│
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   └── WorkflowConfigurationController.php
│   │   └── Api/
│   │       └── V1/
│   │           └── LoanController.php
│   ├── Requests/
│   │   ├── Admin/
│   │   │   ├── StoreWorkflowConfigurationRequest.php
│   │   │   └── UpdateWorkflowConfigurationRequest.php
│   │   └── StoreLoanRequest.php
│   └── Resources/
│       ├── LoanHistoryResource.php
│       ├── LoanProcessResource.php
│       └── LoanResource.php
│
├── Models/
│   ├── Loan.php
│   ├── LoanHistory.php
│   ├── LoanType.php
│   ├── StageDefinition.php
│   ├── WorkflowConfiguration.php
│   └── WorkflowConfigurationStep.php
│
└── Providers/
    └── AppServiceProvider.php

config/
└── workflow-stages.php

database/
├── factories/
│   ├── LoanFactory.php
│   ├── LoanHistoryFactory.php
│   ├── LoanTypeFactory.php
│   └── WorkflowConfigurationFactory.php
├── migrations/
│   ├── *_create_loan_types_table.php
│   ├── *_create_stage_definitions_table.php
│   ├── *_create_workflow_configurations_table.php
│   ├── *_create_workflow_configuration_steps_table.php
│   ├── *_create_loans_table.php
│   └── *_create_loan_histories_table.php
└── seeders/
    └── DatabaseSeeder.php

routes/
├── api.php
└── web.php

tests/
├── Feature/
│   └── Loan/
│       ├── CreateLoanTest.php
│       ├── GetLoanHistoryTest.php
│       ├── GetLoanTest.php
│       ├── HealthCheckTest.php
│       └── ProcessLoanTest.php
└── Unit/
    └── Domain/
        └── Loan/
            ├── Workflow/
            │   ├── CreditCheckStageTest.php
            │   ├── FraudCheckStageTest.php
            │   ├── GuarantorCheckStageTest.php
            │   ├── ManagerApprovalStageTest.php
            │   ├── ValidationStageTest.php
            │   └── WorkflowEngineTest.php
            ├── WorkflowConfigurationResolverTest.php
            └── WorkflowConfigurationPublishingTest.php

Dockerfile
README.md
DESIGN.md
ENGINEERING_DECISIONS.md
TESTING.md
```

### Naming rules

| Concern | Convention | Example |
| --- | --- | --- |
| Controller | Singular resource + `Controller` | `LoanController` |
| Action | Verb + resource + `Action` | `ProcessLoanAction` |
| Stage | Business name + `Stage` | `FraudCheckStage` |
| Enum | Domain concept | `LoanStatus` |
| Data object | Meaning of the returned data | `ExecutionResult` |
| Engine | Orchestrated concept + `Engine` | `WorkflowEngine` |

Do not introduce alternate names such as `WorkflowManager`, `LoanProcessor`, or `StageResult` for concepts already named here.

## 5. Domain design

Three domain concepts must remain separate:

| Concept | Purpose | Examples |
| --- | --- | --- |
| Loan status | Current lifecycle state of a loan | `APPROVED`, `REJECTED` |
| Workflow stage | Business operation being executed | `FRAUD_CHECK`, `CREDIT_CHECK` |
| Stage result | Outcome returned by a stage | `PASS`, `FAIL`, `MANUAL_REVIEW` |

### 5.1 `LoanStatus`

```text
SUBMITTED
IN_PROGRESS
MANUAL_REVIEW
APPROVED
REJECTED
```

Terminal automatic-processing statuses are:

```text
MANUAL_REVIEW
APPROVED
REJECTED
```

### 5.2 `LoanStage`

```text
VALIDATION
FRAUD_CHECK
GUARANTOR_CHECK
CREDIT_CHECK
MANAGER_APPROVAL
```

`MANUAL_REVIEW` is a result and loan status, not an executable Stage.

### 5.3 `StageResultType`

```text
PASS
FAIL
MANUAL_REVIEW
```

### 5.4 Loan types

Loan types are database records, not a PHP Enum, because administrators configure a Workflow by `loan_type_id`. The initial seeded codes are:

```text
PERSONAL
BUSINESS
```

The `code` remains a stable machine identifier even when the display name changes.

### 5.5 `WorkflowConfigurationStatus`

```text
DRAFT
PUBLISHED
ARCHIVED
```

Only draft configurations are editable. Published and archived configurations are immutable.

### 5.6 `ExecutionResult`

`ExecutionResult` is an immutable data object returned by every Stage. It is not an Eloquent model.

It contains:

| Property | Type | Purpose |
| --- | --- | --- |
| `type` | `StageResultType` | Stage outcome |
| `reason` | `string` | `SUCCESS` or a business reason code |

The Workflow Engine maps the result to the next status and stage. Stages must not directly choose HTTP status codes.

### 5.7 `StageInterface`

All Workflow stages implement one contract:

```php
interface StageInterface
{
    /** @param array<string, mixed> $rules */
    public function execute(Loan $loan, array $rules): ExecutionResult;
}
```

Stage requirements:

- one business responsibility per class;
- no mutable internal state;
- no database transaction management;
- no HTTP concerns;
- no direct selection of the next Stage;
- all configurable numbers read from the validated step rules supplied by the Engine;
- deterministic output for the same Loan and rule snapshot.

## 6. Workflow configuration

Administrators configure Workflow order and rules in the database for a specific `loan_type_id`. Configuration is versioned so edits cannot change the behavior of a Loan that is already being processed.

### 6.1 Configuration ownership

```text
LoanType
    └── WorkflowConfiguration
            └── WorkflowConfigurationStep
                    └── StageDefinition
```

Each `WorkflowConfiguration` belongs to exactly one `LoanType`. A Loan stores the exact published `workflow_configuration_id` selected when the Loan is created.

### 6.2 Lifecycle

1. An administrator creates a `DRAFT` configuration for a Loan type.
2. The administrator selects supported Stage definitions, sets their order, and supplies Stage-specific rules.
3. The system validates the complete configuration.
4. Publishing occurs inside a transaction.
5. The previously published configuration for that Loan type becomes `ARCHIVED`.
6. The selected draft becomes `PUBLISHED` and immutable.
7. New Loans use the published configuration; existing Loans keep their assigned configuration.

Administrators never edit a published configuration in place. A change creates a new version.

### 6.3 Trusted Stage registry

The database stores stable Stage codes, not PHP class names or executable expressions. `config/workflow-stages.php` maps each trusted code to an application handler:

```php
return [
    'VALIDATION' => ValidationStage::class,
    'FRAUD_CHECK' => FraudCheckStage::class,
    'GUARANTOR_CHECK' => GuarantorCheckStage::class,
    'CREDIT_CHECK' => CreditCheckStage::class,
    'MANAGER_APPROVAL' => ManagerApprovalStage::class,
];
```

An administrator can select only active `StageDefinition` records whose codes exist in this registry. Admin input must never contain PHP, SQL, a handler class name, or an unrestricted expression.

### 6.4 Per-step rules

Rules are stored as JSON on `workflow_configuration_steps`. Every Stage handler owns the schema and validation of its rules.

Examples:

```json
{
  "rejectBelow": 500,
  "manualReviewMin": 500,
  "manualReviewMax": 649,
  "approveFrom": 650
}
```

```json
{
  "activationThreshold": 500000000,
  "incomeMultiplier": 20
}
```

Publishing must fail if:

- a required Stage is missing;
- a Stage or position is duplicated;
- a configured Stage code has no registered handler;
- a rule has the wrong type or an invalid value;
- credit ranges overlap or contain an unintended gap;
- the Workflow cannot reach a terminal outcome.

### 6.5 Initial configurations

Seed initial published configurations matching the challenge requirements:

```text
PERSONAL:
VALIDATION → FRAUD_CHECK → CREDIT_CHECK → MANAGER_APPROVAL

BUSINESS:
VALIDATION → FRAUD_CHECK → GUARANTOR_CHECK
           → CREDIT_CHECK → MANAGER_APPROVAL
```

The initial rule values are:

| Rule | Default |
| --- | ---: |
| Credit rejection boundary | `500` |
| Manual-review minimum | `500` |
| Manual-review maximum | `649` |
| Credit approval boundary | `650` |
| Manager activation threshold | `500000000` |
| Income multiplier | `20` |

### 6.6 Adding a future Stage

Adding a new Stage such as `AML_CHECK` requires:

1. adding its `LoanStage` enum value;
2. creating a class implementing `StageInterface`;
3. registering its trusted code and class in `config/workflow-stages.php`;
4. seeding or creating its `StageDefinition`;
5. letting an administrator add it to a new draft Workflow version;
6. adding Stage unit tests and Workflow integration tests.

Existing Stage classes and the Workflow Engine must not require modification.

## 7. Stage behavior

### 7.1 `ValidationStage`

| Field | Rule | Failure reason |
| --- | --- | --- |
| `customerId` | Required and not empty | `INVALID_CUSTOMER_ID` |
| `amount` | Greater than `0` | `INVALID_AMOUNT` |
| `phone` | Exactly 11 digits, starts with `09`, digits only | `INVALID_PHONE` |
| `loanType` | `PERSONAL` or `BUSINESS` | `INVALID_LOAN_TYPE` |
| `monthlyIncome` | Greater than or equal to `0` | `INVALID_MONTHLY_INCOME` |
| `creditScore` | Between `0` and `1000`, inclusive | `INVALID_CREDIT_SCORE` |

These are business Workflow rules. They must produce a `LoanHistory` entry and must not be implemented as HTTP validation that returns `422 Unprocessable Entity`.

`StoreLoanRequest` validates only the transport contract: valid JSON shape, required keys, and values that can be safely passed into the domain. Semantic acceptance remains the responsibility of `ValidationStage`.

### 7.2 `FraudCheckStage`

| Condition | Result |
| --- | --- |
| `customerId` starts with `FRAUD` | `FAIL` |
| `customerId` starts with `REVIEW` | `MANUAL_REVIEW` |
| Otherwise | `PASS` |

### 7.3 `GuarantorCheckStage`

This Stage exists only in the `BUSINESS` Workflow.

| Condition | Result |
| --- | --- |
| `hasGuarantor` is `false` | `FAIL` |
| `hasGuarantor` is `true` | `PASS` |

### 7.4 `CreditCheckStage`

| Configured condition | Result |
| --- | --- |
| Below `manualReviewMin` | `FAIL` |
| Between `manualReviewMin` and `manualReviewMax` | `MANUAL_REVIEW` |
| At least `approveFrom` | `PASS` |

All boundaries come from the validated rules of the assigned `CREDIT_CHECK` configuration step.

### 7.5 `ManagerApprovalStage`

The Stage is skipped when:

```text
amount <= activationThreshold
```

When it applies:

| Condition | Result |
| --- | --- |
| `amount > monthlyIncome × incomeMultiplier` | `FAIL` |
| Otherwise | `PASS` |

The Engine may ask a Stage whether it applies, or the Stage may return a successful no-op result. The chosen mechanism must be consistent for all conditional Stages and must not create a history record for a Stage that was not executed.

## 8. Workflow Engine

### Responsibilities

`WorkflowEngine` is responsible for:

- loading the ordered steps from the Loan's assigned `WorkflowConfiguration`;
- resolving every `StageDefinition.code` through the trusted handler registry;
- passing each step's validated rules to its Stage handler;
- finding the current executable Stage;
- skipping Stages that do not apply;
- executing each applicable Stage once;
- recording one history entry for each executed Stage;
- stopping on `FAIL` or `MANUAL_REVIEW`;
- approving the Loan after all applicable Stages pass;
- keeping `status` and `current_workflow_configuration_step_id` consistent.

It is not responsible for:

- parsing HTTP input;
- opening the database transaction;
- defining business thresholds;
- implementing Stage-specific rules;
- returning JSON responses.

### Processing algorithm

`ProcessLoanAction` performs the following operation:

```text
BEGIN TRANSACTION
    ↓
SELECT loan FOR UPDATE
    ↓
If status is terminal, return current state
    ↓
Load Loan's assigned published WorkflowConfiguration
    ↓
Set status to IN_PROGRESS
    ↓
For every remaining applicable configuration step:
    set current_workflow_configuration_step_id
    resolve trusted Stage handler
    execute Stage with the step's rules
    insert LoanHistory with rule snapshot

    PASS          → continue
    FAIL          → status = REJECTED; current step = null; stop
    MANUAL_REVIEW → status = MANUAL_REVIEW; current step = null; stop
    ↓
All applicable Stages passed
    ↓
status = APPROVED; current step = null
COMMIT
```

If an unexpected exception occurs, the complete transaction rolls back. No partial history or half-updated Loan state may remain.

### Synchronous execution

`POST /api/v1/loans/{loanId}/process` executes all remaining Stages in the request and returns the resulting terminal state. The client does not call the endpoint once per Stage.

No Stage is dispatched to a queue in this version.

## 9. Idempotency and concurrency

The process endpoint must be safe when called repeatedly or concurrently.

### Application safeguards

- Return immediately when the Loan already has a terminal status.
- Execute only Stages without an existing history record.
- Lock the Loan row for the duration of processing.

### Database safeguard

The database must enforce:

```text
UNIQUE (loan_id, workflow_configuration_step_id)
```

on `loan_histories`.

Together, the row lock and unique constraint guarantee that:

- a configured step is not executed twice for the same Loan;
- duplicate history is not stored;
- concurrent requests cannot overwrite the final state;
- reprocessing a terminal Loan returns its existing state.

## 10. Database design

### 10.1 `loan_types`

| Column | Type | Constraints |
| --- | --- | --- |
| `id` | `bigint` | Primary key |
| `code` | `string` | Unique, stable machine identifier |
| `name` | `string` | Display name |
| `is_active` | `boolean` | Required, indexed |
| `created_at` | `timestamp` | Required |
| `updated_at` | `timestamp` | Required |

Seed `PERSONAL` and `BUSINESS`. Deactivating a type prevents new Loans but does not affect existing Loans.

### 10.2 `stage_definitions`

| Column | Type | Constraints |
| --- | --- | --- |
| `id` | `bigint` | Primary key |
| `code` | `string` | Unique; must exist in the trusted registry |
| `name` | `string` | Admin-facing display name |
| `is_active` | `boolean` | Required, indexed |
| `created_at` | `timestamp` | Required |
| `updated_at` | `timestamp` | Required |

This table controls what administrators can select. It does not store executable class names.

### 10.3 `workflow_configurations`

| Column | Type | Constraints |
| --- | --- | --- |
| `id` | `bigint` | Primary key |
| `loan_type_id` | `bigint` | Foreign key to `loan_types.id`, indexed |
| `name` | `string` | Admin-facing name |
| `version` | `integer` | Positive version number |
| `status` | `string` | Cast to `WorkflowConfigurationStatus`, indexed |
| `published_at` | `timestamp`, nullable | Set only when published |
| `created_by` | `bigint`, nullable | Foreign key to `users.id` |
| `created_at` | `timestamp` | Required |
| `updated_at` | `timestamp` | Required |

Constraints:

```text
UNIQUE (loan_type_id, version)
UNIQUE (id, loan_type_id)
```

PostgreSQL should enforce at most one `PUBLISHED` configuration per Loan type with a partial unique index. Publishing archives the previous version in the same transaction.

### 10.4 `workflow_configuration_steps`

| Column | Type | Constraints |
| --- | --- | --- |
| `id` | `bigint` | Primary key |
| `workflow_configuration_id` | `bigint` | Foreign key, indexed |
| `stage_definition_id` | `bigint` | Foreign key, indexed |
| `position` | `integer` | Positive execution order |
| `rules` | `jsonb` | Stage-specific validated rules |
| `is_enabled` | `boolean` | Required |
| `created_at` | `timestamp` | Required |
| `updated_at` | `timestamp` | Required |

Constraints:

```text
UNIQUE (workflow_configuration_id, position)
UNIQUE (workflow_configuration_id, stage_definition_id)
UNIQUE (id, workflow_configuration_id)
```

`rules` is cast to an array by Eloquent. Only draft steps may be created, updated, reordered, enabled, disabled, or deleted.

### 10.5 `loans`

| Column | Type | Constraints |
| --- | --- | --- |
| `id` | `bigint` | Primary key |
| `public_id` | `string` | Unique; exposed as API `loanId` |
| `customer_id` | `string` | Indexed |
| `loan_type_id` | `bigint` | Foreign key to `loan_types.id`, indexed |
| `workflow_configuration_id` | `bigint` | Immutable foreign key, indexed |
| `current_workflow_configuration_step_id` | `bigint`, nullable | Foreign key, indexed |
| `amount` | `bigint` | Required |
| `phone` | `string(11)` | Required |
| `monthly_income` | `bigint` | Required |
| `credit_score` | `integer` | Required |
| `has_guarantor` | `boolean` | Required |
| `status` | `string` | Required, indexed; cast to `LoanStatus` |
| `created_at` | `timestamp` | Required |
| `updated_at` | `timestamp` | Required |

`workflow_configuration_id` is assigned when the Loan is created and must never change. The configuration's `loan_type_id` must match the Loan's `loan_type_id`, and the current step must belong to that configuration. Enforce both invariants with composite foreign keys in PostgreSQL as well as validation in the creation and processing Actions.

```text
FOREIGN KEY (workflow_configuration_id, loan_type_id)
    REFERENCES workflow_configurations(id, loan_type_id)

FOREIGN KEY (current_workflow_configuration_step_id, workflow_configuration_id)
    REFERENCES workflow_configuration_steps(id, workflow_configuration_id)
```

The API's `loanType` and `currentStage` values are derived from the related `LoanType.code` and current step's `StageDefinition.code`.

### 10.6 `loan_histories`

| Column | Type | Constraints |
| --- | --- | --- |
| `id` | `bigint` | Primary key |
| `loan_id` | `bigint` | Foreign key to `loans.id`, indexed |
| `workflow_configuration_step_id` | `bigint` | Foreign key, indexed |
| `stage_code` | `string` | Immutable audit snapshot |
| `rules_snapshot` | `jsonb` | Immutable rules used for execution |
| `result` | `string` | Cast to `StageResultType` |
| `reason` | `string` | Required |
| `executed_at` | `timestamp` | Required, indexed |
| `created_at` | `timestamp` | Required |
| `updated_at` | `timestamp` | Required |

Constraints:

```text
FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
UNIQUE (loan_id, workflow_configuration_step_id)
INDEX (loan_id, executed_at)
```

The snapshots preserve the exact Stage code and rule values used for the decision even if display metadata changes later. History is ordered by `executed_at`, then by `id` as a deterministic tie-breaker.

Published or referenced configurations and steps must never be hard-deleted. Archive them so Loan execution and audit history remain reproducible.

### Relationships

```text
LoanType hasMany WorkflowConfiguration
LoanType hasMany Loan

WorkflowConfiguration belongsTo LoanType
WorkflowConfiguration hasMany WorkflowConfigurationStep
WorkflowConfiguration hasMany Loan

WorkflowConfigurationStep belongsTo WorkflowConfiguration
WorkflowConfigurationStep belongsTo StageDefinition
WorkflowConfigurationStep hasMany LoanHistory

StageDefinition hasMany WorkflowConfigurationStep

Loan belongsTo LoanType
Loan belongsTo WorkflowConfiguration
Loan belongsTo WorkflowConfigurationStep as currentStep
Loan hasMany LoanHistory

LoanHistory belongsTo Loan
LoanHistory belongsTo WorkflowConfigurationStep
```

All Eloquent relationships require explicit Laravel relationship return types. JSON, boolean, status, and timestamp columns require model casts.

## 11. Actions

### `CreateLoanAction`

- Accepts transport-safe request data.
- Resolves the active `LoanType` by its stable code.
- Resolves that type's single published `WorkflowConfiguration`.
- Fails clearly when no published configuration exists.
- Generates a unique public Loan identifier.
- Stores the immutable `loan_type_id` and `workflow_configuration_id` assignments.
- Stores the Loan with `SUBMITTED` status and the first configured step as its current step.
- Does not execute the Workflow.

### `ProcessLoanAction`

- Opens the database transaction.
- loads and locks the Loan row;
- returns an existing terminal Loan without changing it;
- eager-loads the assigned configuration, ordered steps, and Stage definitions;
- invokes `WorkflowEngine`;
- returns the final Loan state.

### `GetLoanAction`

- Finds a Loan by `public_id`.
- Throws the application’s not-found exception when it does not exist.

### `GetLoanHistoryAction`

- Finds the Loan by `public_id`.
- Returns its history in deterministic chronological order.
- Eager-loads required relationships and avoids N+1 queries.

### `CreateWorkflowConfigurationAction`

- Creates the next draft version for a `loan_type_id`.
- Copies an existing version's steps when requested.
- Never creates a second draft version number.

### `UpdateWorkflowConfigurationAction`

- Updates only a `DRAFT` configuration.
- Validates selected Stage definitions, positions, and Stage-specific rules.
- Replaces or updates steps atomically so a partial ordering is never exposed.

### `PublishWorkflowConfigurationAction`

- Locks the Loan type's configurations.
- Fully validates the draft and its ordered steps.
- Archives the currently published version.
- Publishes the selected draft with `published_at` and `created_by` audit data.
- Performs all status changes inside one database transaction.

## 12. HTTP and API design

The detailed contract is defined in [api-routes.md](./api-routes.md).

### Routes

```text
POST /api/v1/loans
POST /api/v1/loans/{loanId}/process
GET  /api/v1/loans/{loanId}
GET  /api/v1/loans/{loanId}/history
GET  /health
```

### Controller rules

`LoanController` must:

- remain thin;
- use route model binding or an Action-based public-ID lookup consistently;
- invoke exactly one Action per endpoint;
- return API Resources;
- contain no Workflow or validation business rules.

### HTTP validation boundary

| Failure | Handling |
| --- | --- |
| Malformed JSON or unusable transport structure | `400 INVALID_REQUEST` |
| Semantically invalid amount, phone, score, or other loan value | Loan is created; `ValidationStage` later returns `FAIL` |
| Unknown Loan public ID | `404 LOAN_NOT_FOUND` |

Do not return Laravel’s default HTML error pages or default `422` validation payload for business validation failures.

### Admin configuration boundary

The admin panel operates on `LoanType`, `WorkflowConfiguration`, and `WorkflowConfigurationStep` through the dedicated admin Controller, Form Requests, and Actions. It must support:

- listing configurations by `loan_type_id`;
- creating a draft or copying an existing version;
- selecting active Stage definitions;
- ordering steps;
- editing Stage-specific rules;
- validating a draft;
- publishing a draft;
- viewing archived versions and their assigned Loans.

All admin mutations require authorization. Published and archived configurations are read-only. The public loan API must never accept a client-supplied `workflow_configuration_id`; the server selects the published configuration for the requested Loan type.

## 13. Logging and error handling

Business history and operational logs are separate concerns.

### `loan_histories`

Stores expected business outcomes:

- successful Stage execution;
- validation failure;
- fraud rejection;
- manual-review decision;
- credit or manager rejection.

### Laravel logs

Store unexpected operational failures:

- database errors;
- invalid Workflow configuration;
- unresolvable Stage classes;
- unexpected exceptions.

Operational logs should include `loanId`, current Stage, and exception context where available, but must not replace persisted business history.

## 14. Test blueprint

The project uses Pest 4.

### Unit tests

Each Stage is tested independently with all boundary conditions.

#### `ValidationStageTest`

- valid application passes;
- empty customer ID fails;
- zero and negative amounts fail;
- valid and invalid phone formats;
- invalid loan type fails;
- negative monthly income fails;
- credit scores below `0` and above `1000` fail.

#### `FraudCheckStageTest`

- `FRAUD` prefix fails;
- `REVIEW` prefix requests manual review;
- any other prefix passes.

#### `GuarantorCheckStageTest`

- missing guarantor fails;
- existing guarantor passes.

#### `CreditCheckStageTest`

- score below manual range fails;
- lower and upper manual-range boundaries request manual review;
- score at the approval boundary passes;
- overridden configuration changes behavior.

#### `ManagerApprovalStageTest`

- Stage does not apply below or at its activation threshold;
- excessive income multiple fails;
- acceptable income multiple passes;
- overridden configuration changes behavior.

#### `WorkflowEngineTest`

- runs Stages in configured order;
- stops on `FAIL`;
- stops on `MANUAL_REVIEW`;
- approves after all Stages pass;
- skips non-applicable Stages;
- records each executed Stage once;
- does not record skipped Stages.

#### `WorkflowConfigurationResolverTest`

- resolves the Loan's assigned configuration rather than the latest version;
- resolves steps in ascending position;
- maps every Stage code to a handler implementing `StageInterface`;
- rejects missing or inactive Stage definitions;
- rejects invalid rule schemas.

#### `WorkflowConfigurationPublishingTest`

- only drafts can be changed or published;
- publishing archives the previous version;
- only one version per Loan type is published;
- invalid Stage order and rule ranges cannot be published;
- a published configuration is immutable.

### Feature tests

#### `CreateLoanTest`

- creates a Loan with the expected initial state;
- assigns the published configuration for the selected `loan_type_id`;
- rejects creation when the Loan type has no published configuration;
- does not accept a client-selected configuration ID;
- generates a unique public ID;
- returns `400 INVALID_REQUEST` for malformed JSON;
- accepts semantically invalid business values for later Workflow validation.

#### `ProcessLoanTest`

- approves a valid personal Loan;
- approves a valid business Loan with a guarantor;
- rejects each validation failure;
- handles fraud failure and manual review;
- rejects a business Loan without a guarantor;
- handles all credit-score outcomes;
- executes conditional manager approval;
- returns `404 LOAN_NOT_FOUND`;
- remains idempotent after every terminal status;
- does not create duplicate history.

#### `GetLoanTest`

- returns the complete Loan representation;
- returns `404 LOAN_NOT_FOUND` for an unknown ID.

#### `GetLoanHistoryTest`

- returns Stage history chronologically;
- returns each Stage once;
- returns `404 LOAN_NOT_FOUND` for an unknown ID.

#### `HealthCheckTest`

- returns `200 OK` with `{"status":"UP"}`.

#### Admin Workflow configuration tests

- creates and updates a draft for a Loan type;
- copies an existing version without sharing mutable steps;
- rejects duplicate Stage positions and definitions;
- rejects invalid Stage-specific rule JSON;
- publishes atomically;
- prevents modification of published and archived versions;
- keeps in-progress Loans assigned to their original version;
- assigns newly created Loans to the newly published version;
- rejects unauthorized configuration mutations.

### Persistence verification

Feature tests use the real database integration path rather than mocking Eloquent. Persistence behavior must also be verified through the Dockerized PostgreSQL environment.

## 15. Docker and runtime

The application must be buildable and runnable using:

```bash
docker build -t bankflow .
docker run -p 8080:8080 bankflow
```

Requirements:

- the service listens on port `8080`;
- PostgreSQL-backed data survives an application process restart;
- the trusted Stage registry is loaded at startup and published Workflow configurations are loaded from PostgreSQL;
- the health endpoint becomes available within 60 seconds;
- the production image contains only runtime requirements;
- migrations are run through an explicit, documented startup strategy.

Docker Compose may be provided for local development, but it must not replace the required `docker build` and `docker run` workflow.

## 16. Required documentation

| File | Purpose |
| --- | --- |
| `README.md` | Build, run, test, dependencies, and project overview |
| `DESIGN.md` | Architecture, Workflow model, persistence, and extensibility |
| `ENGINEERING_DECISIONS.md` | Decisions, alternatives, and trade-offs |
| `TESTING.md` | Unit and feature test strategy and coverage |

These files must describe the implemented system, not an aspirational design that differs from the code.

## 17. Implementation order

Implementation should proceed in small, reviewable phases:

1. Project configuration and PostgreSQL connection.
2. Domain Enums and `ExecutionResult`.
3. Loan and LoanHistory migrations, models, factories, and relationships.
4. Loan type, Stage definition, Workflow configuration, and step models.
5. Trusted Stage registry and initial Workflow seed data.
6. `StageInterface` and individual stateless Stages.
7. `WorkflowConfigurationResolver` and publishing validation.
8. `WorkflowEngine`.
9. Loan Actions and transaction/concurrency behavior.
10. Admin Workflow configuration Actions and HTTP boundary.
11. Public Form Request, API Resources, Controller, and routes.
12. Unit tests.
13. Feature and persistence tests.
14. Docker image and runtime verification.
15. Required project documentation.

Each phase must pass its focused tests before the next phase begins.

## 18. Definition of done

The implementation is complete when:

- all documented routes conform to the API contract;
- all Workflow stages execute synchronously in configured order;
- business validation is recorded as Workflow history;
- terminal processing is idempotent;
- concurrent processing cannot duplicate Stage execution;
- Loan state and history remain consistent after failures;
- data persists after application restart;
- business thresholds can change without modifying Stage code;
- administrators can publish a versioned Workflow per Loan type;
- Loans already in progress retain their originally assigned configuration;
- a new Stage can be added without rewriting the Engine or existing Stages;
- all unit and feature tests pass;
- the Docker commands in the challenge work as documented;
- required documentation matches the final implementation.
