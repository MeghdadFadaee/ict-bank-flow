# BankFlow Testing

[English](TESTING.md) | [فارسی](TESTING.fa.md)

BankFlow uses Pest 4. Feature tests run with Laravel's application test case and `LazilyRefreshDatabase`; `phpunit.xml` selects an in-memory SQLite database, array-backed cache/session/mail, and the synchronous queue driver.

## Run the suite

```bash
php artisan test --compact
```

Run a focused group with, for example:

```bash
php artisan test --compact tests/Feature/Loan/ProcessLoanTest.php
php artisan test --compact --filter="approves a valid personal loan"
```

## Unit coverage

Unit tests exercise each business stage independently:

- validation success and each first-failure reason;
- fraud and manual-review prefixes;
- guarantor outcomes;
- credit boundaries and overridden rules;
- manager-approval applicability, income multiplier, and pass/fail results.

These tests verify pure stage behavior without HTTP or database orchestration.

## Feature and integration coverage

Feature tests cover loan creation, deferred semantic validation, malformed requests, unavailable types, personal and business processing paths, fraud/credit manual review, conditional manager approval, terminal-state idempotency, retrieval, ordered history, documented not-found errors, and health checks.

Administrative integration tests cover authenticated panel access, read-only loan views, draft-only workflow editing, copying workflow versions, validated publication, atomic archival of the prior version, and guarded manager approval. These tests cross the HTTP/Filament, Action, Eloquent, migration, and resource boundaries.

## Known gaps

The suite does not currently exercise real concurrent requests or PostgreSQL-specific behavior such as row-lock contention and the partial published-workflow index. It also omits Docker smoke tests, frontend browser tests, performance/load tests, security testing, unexpected-exception rollback injection, and real external fraud/credit integrations (the challenge uses deterministic mocked rules).
