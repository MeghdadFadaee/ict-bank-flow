# BankFlow

[English](README.md) | [فارسی](README.fa.md)

BankFlow is a Laravel service for submitting and processing personal and business loan applications. Each loan is assigned an immutable, versioned workflow; processing executes its configured stages synchronously and records an auditable result for every stage that ran.

The repository includes the versioned JSON API, a Filament administration panel for loans and workflow configuration, a PostgreSQL production setup, and a Pest test suite.

## Main capabilities

- Submit and inspect loan applications through `/api/v1/loans`.
- Process validation, fraud, guarantor, credit, and conditional manager-approval stages.
- Stop on rejection or manual review and safely return terminal results on repeated requests.
- Configure and publish versioned workflows per loan type through the admin panel.
- Preserve stage results and the exact rule snapshot used for each decision.
- Inspect service readiness through `GET /health`.

The complete HTTP contract is in [docs/api-routes.md](docs/api-routes.md).

## Technology

- PHP 8.4 and Laravel 13
- Filament 5 and Livewire 4
- PostgreSQL 17 in the Compose environment; SQLite is supported for tests and the standalone image
- Eloquent ORM
- Pest 4 and PHPUnit 12
- Tailwind CSS 4 and Vite 8
- Apache in the production container

## Run with Docker

The challenge-compatible standalone image uses its internal SQLite database. The entrypoint migrates and seeds the database on startup.

```bash
docker build -t bankflow .
docker run --rm -p 8080:8080 bankflow
```

The service is then available on port `8080`; verify it with:

```bash
curl http://127.0.0.1:8080/health
```

For PostgreSQL-backed execution, copy the environment file, set `APP_KEY` and `DB_PASSWORD`, then use Compose:

```bash
cp .env.example .env
docker compose up --build
```

Compose persists PostgreSQL data in the `postgres-data` volume. Both container paths run migrations and the idempotent `BankFlowSeeder` automatically.

## Local development

### Prerequisites

- PHP 8.4 with the extensions required by Laravel and the selected database
- Composer 2
- Node.js 24 and npm
- PostgreSQL, or SQLite for a lightweight local setup
- Laravel Herd or another locally configured web server

Install and configure the application:

```bash
cp .env.example .env
composer install
php artisan key:generate
npm install
npm run build
php artisan migrate --seed
```

Before migrating, update the `DB_*` values in `.env` for the database reachable from the host. To use SQLite, set `DB_CONNECTION=sqlite`, remove the other `DB_*` values, and create `database/database.sqlite` if it does not exist.

For active frontend work, run `npm run dev`. The application is already served by Laravel Herd in the standard project environment; no additional PHP development server is required.

The administration panel is mounted at `/admin`. It requires an authenticated user with a verified email address.

## API example

Create and process a loan:

```bash
curl -X POST http://127.0.0.1:8080/api/v1/loans \
  -H 'Content-Type: application/json' \
  -d '{
    "customerId": "C-1001",
    "amount": 400000000,
    "phone": "09121234567",
    "loanType": "PERSONAL",
    "monthlyIncome": 50000000,
    "creditScore": 720,
    "hasGuarantor": false
  }'
```

Use the returned `loanId`:

```bash
curl -X POST http://127.0.0.1:8080/api/v1/loans/{loanId}/process
curl http://127.0.0.1:8080/api/v1/loans/{loanId}
curl http://127.0.0.1:8080/api/v1/loans/{loanId}/history
```

## Tests and formatting

The test environment uses an in-memory SQLite database and does not require PostgreSQL.

```bash
php artisan test --compact
vendor/bin/pint --format agent
```

Run a focused test with `php artisan test --compact --filter=ProcessLoanTest`. See [TESTING.md](TESTING.md) for the test strategy and current coverage.

## Project layout

```text
app/Actions/                 Single-use application operations and transactions
app/Domain/Loan/             Enums, stage contracts, stage handlers, and engine
app/Filament/                Administrative resources, pages, and widgets
app/Http/                    API controllers, request parsing, and resources
app/Models/                  Eloquent persistence models
config/workflow-stages.php   Trusted stage-code-to-handler registry
database/                    Migrations, factories, and initial workflow seed data
docs/                        Requirements, API contract, and implementation blueprint
routes/                      Web and versioned API routes
tests/                       Pest feature and unit tests
```

## Further documentation

- [DESIGN.md](DESIGN.md) — runtime architecture, workflow model, state, and extensibility
- [ENGINEERING_DECISIONS.md](ENGINEERING_DECISIONS.md) — alternatives, trade-offs, limitations, and future work
- [TESTING.md](TESTING.md) — test scope and commands
- [docs/bank-flow.md](docs/bank-flow.md) — original challenge requirements
- [docs/project-blueprint.md](docs/project-blueprint.md) — detailed implementation blueprint
