# BankFlow Engineering Decisions

[English](ENGINEERING_DECISIONS.md) | [فارسی](ENGINEERING_DECISIONS.fa.md)

## 1. Layered Laravel architecture

**Options considered:** controllers with embedded logic, layered Laravel with Actions, or full Clean/Hexagonal architecture with repositories and ports.

**Decision:** use thin controllers, single-purpose Actions, a loan domain module, and Eloquent models. This keeps use cases and transaction boundaries explicit without adding abstractions that the current service does not need. It is familiar to Laravel developers and straightforward to test. The limitation is tighter coupling to Laravel and Eloquent than a ports-and-adapters design.

## 2. Ordered stage pipeline instead of hard-coded branching

**Options considered:** nested conditionals in a service, a generic rule engine, or ordered stateless stage handlers.

**Decision:** model a workflow as versioned, ordered database steps resolved through a trusted PHP registry. Each handler returns a typed result and the engine owns progression. Adding a stage changes the enum, handler, registry, rule validation, stage definition/workflow version, and tests; the engine and existing handlers remain unchanged.

This design is extensible and auditable without accepting executable administrator input. It is less expressive than a general graph or rule language: the current engine supports a linear ordered pipeline with conditional skips and terminal results, not arbitrary branches or cycles.

## 3. Database-owned rules with code-owned handlers

**Options considered:** all rules in PHP configuration files, all behavior in the database, or a hybrid.

**Decision:** store workflow order and business values as validated JSON on versioned database steps, while `config/workflow-stages.php` maps trusted codes to implementation classes. Administrators can change thresholds and ordering without deploying code, but cannot inject PHP, SQL, or expressions.

The benefit is operational flexibility with a controlled security boundary. The cost is maintaining per-stage validation and an administrative publication lifecycle. Although the challenge describes reading rules from configuration, the database is deliberately the versioned business configuration source; the PHP config file only registers trusted handlers.

## 4. Immutable workflow assignment and durable history

**Options considered:** always resolve the latest workflow, copy all workflow data onto each loan, or reference a versioned configuration.

**Decision:** assign the current published `workflow_configuration_id` when a loan is created. Published versions are immutable and each history record stores the executed step and rule snapshot. This makes old decisions reproducible without duplicating the complete configuration per loan. It requires retaining referenced workflow versions and prevents destructive deletion.

## 5. Synchronous atomic processing

**Options considered:** one synchronous request, a queued job per stage, or event-driven orchestration.

**Decision:** process all remaining stages in one request and one database transaction. The API immediately returns a terminal automatic-processing status, and failures cannot leave partial state. A row lock, terminal-status guard, history lookup, and unique history constraint prevent repeated or concurrent execution.

This is the simplest reliable fit for fast, mocked checks. Its main limitation is transaction duration: real credit, fraud, or AML integrations could be slow or unavailable. Those stages should eventually move to a resumable queued state machine with short transactions, explicit retry policy, timeouts, and idempotency keys.

## 6. Eloquent and PostgreSQL

**Options considered:** in-memory storage, a repository abstraction, or direct Eloquent persistence backed by a relational database.

**Decision:** use Eloquent with PostgreSQL in the production Compose setup. Relational foreign keys, composite constraints, row locks, JSON rule storage, and a partial unique index provide the required consistency and restart durability. SQLite remains useful for tests and the self-contained challenge image.

The advantage is strong persistence with little custom infrastructure. The trade-off is that some production guarantees—especially the partial unique index and concurrency semantics—are PostgreSQL-specific and are not fully exercised by SQLite tests.

## Three defining trade-offs

1. **Simplicity over throughput:** synchronous processing is clear and atomic, but not suitable for slow external dependencies.
2. **Configurability over schema simplicity:** versioned workflow tables support safe changes and audits, but require validation and lifecycle management.
3. **Framework convention over portability:** Actions plus Eloquent reduce boilerplate, but deliberately avoid persistence independence.

## Limitations and next improvements

The largest architectural limit is the synchronous linear workflow. With another week, the priorities would be PostgreSQL-backed concurrency tests, failure-injection and rollback tests, API authentication and rate limiting, richer authorization roles, OpenAPI generation, observability around stage latency/failures, and a resumable queue design for genuine external checks.

Future growth is still supported: stable stage contracts isolate new checks; immutable versions protect existing loans; JSON rules permit stage-specific policies; and Actions provide clear seams for queues or external adapters when those needs become real.
