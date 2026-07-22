# BankFlow API Reference

This document describes the HTTP API contract for BankFlow. It is based on the requirements in [bank-flow.md](./bank-flow.md).

## Overview

All loan routes are versioned under `/api/v1`. The health-check route is not versioned.

### Routes

| Method | Route                            | Description                | Success status |
|--------|----------------------------------|----------------------------|----------------|
| `POST` | `/api/v1/loans`                  | Create a loan application  | `201 Created`  |
| `POST` | `/api/v1/loans/{loanId}/process` | Process a loan application | `200 OK`       |
| `GET`  | `/api/v1/loans/{loanId}`         | Get a loan application     | `200 OK`       |
| `GET`  | `/api/v1/loans/{loanId}/history` | Get processing history     | `200 OK`       |
| `GET`  | `/health`                        | Check service health       | `200 OK`       |

## General conventions

### Content type

Requests with a body must use JSON. Every response must include:

```http
Content-Type: application/json
```

### Timestamps

All timestamps use ISO 8601 format in UTC:

```text
2026-07-15T10:00:00Z
```

### Loan types

| Value      | Description   |
|------------|---------------|
| `PERSONAL` | Personal loan |
| `BUSINESS` | Business loan |

### Loan statuses

| Status          | Description                                        | Terminal for automatic processing |
|-----------------|----------------------------------------------------|-----------------------------------|
| `SUBMITTED`     | The application has been created but not processed | No                                |
| `IN_PROGRESS`   | The application is being processed                 | No                                |
| `MANUAL_REVIEW` | The application requires a manual decision         | Yes                               |
| `APPROVED`      | The application has been approved                  | Yes                               |
| `REJECTED`      | The application has been rejected                  | Yes                               |

### Loan stages

| Stage              | Description                             |
|--------------------|-----------------------------------------|
| `VALIDATION`       | Validate the submitted application data |
| `FRAUD_CHECK`      | Perform the mocked fraud check          |
| `GUARANTOR_CHECK`  | Check for a guarantor on business loans |
| `CREDIT_CHECK`     | Evaluate the applicant's credit score   |
| `MANAGER_APPROVAL` | Apply the mocked manager-approval rule  |

### Stage result type

| Result          | Effect                                                     |
|-----------------|------------------------------------------------------------|
| `PASS`          | Continue to the next applicable stage                      |
| `FAIL`          | Stop processing and set the loan status to `REJECTED`      |
| `MANUAL_REVIEW` | Stop processing and set the loan status to `MANUAL_REVIEW` |

## Create a loan application

Creates and stores a new loan application. Creating an application does not execute its workflow.

```http
POST /api/v1/loans
```

### Request body

| Field           | Type      | Required | Rules                                              |
|-----------------|-----------|----------|----------------------------------------------------|
| `customerId`    | `string`  | Yes      | Must not be empty                                  |
| `amount`        | `integer` | Yes      | Must be greater than `0`                           |
| `phone`         | `string`  | Yes      | Must contain exactly 11 digits and start with `09` |
| `loanType`      | `string`  | Yes      | Must be `PERSONAL` or `BUSINESS`                   |
| `monthlyIncome` | `integer` | Yes      | Must be greater than or equal to `0`               |
| `creditScore`   | `integer` | Yes      | Must be between `0` and `1000`, inclusive          |
| `hasGuarantor`  | `boolean` | Yes      | Indicates whether the applicant has a guarantor    |

```json
{
  "customerId": "C-1001",
  "amount": 400000000,
  "phone": "09121234567",
  "loanType": "PERSONAL",
  "monthlyIncome": 50000000,
  "creditScore": 720,
  "hasGuarantor": false
}
```

### Processing rules

- The service generates a unique `loanId`.
- The initial status is `SUBMITTED`.
- The initial workflow stage is `VALIDATION`.
- Business validation is deferred until the workflow is processed.
- An invalid field value does not produce an HTTP `400` response during creation. It causes the `VALIDATION` stage to fail when the application is processed.
- A malformed JSON document produces an HTTP `400` response.

### Success response

```http
HTTP/1.1 201 Created
Content-Type: application/json
```

```json
{
  "loanId": "L-10001",
  "status": "SUBMITTED",
  "currentStage": "VALIDATION"
}
```

#### Response fields

| Field          | Type     | Description                                    |
|----------------|----------|------------------------------------------------|
| `loanId`       | `string` | Unique identifier generated by the service     |
| `status`       | `string` | Current loan status; initially `SUBMITTED`     |
| `currentStage` | `string` | Current workflow stage; initially `VALIDATION` |

### Malformed JSON response

```http
HTTP/1.1 400 Bad Request
Content-Type: application/json
```

```json
{
  "error": "INVALID_REQUEST"
}
```

## Process a loan application

Processes an application from its current workflow stage until it reaches a terminal automatic-processing status.

```http
POST /api/v1/loans/{loanId}/process
```

### Path parameters

| Parameter | Type     | Required | Description                        |
|-----------|----------|----------|------------------------------------|
| `loanId`  | `string` | Yes      | Unique loan application identifier |

This route does not accept a request body.

### Workflow rules

All applications begin with the following stages:

```text
VALIDATION → FRAUD_CHECK
```

The remaining route depends on the loan type:

```text
PERSONAL:
VALIDATION → FRAUD_CHECK → CREDIT_CHECK → [MANAGER_APPROVAL] → APPROVED

BUSINESS:
VALIDATION → FRAUD_CHECK → GUARANTOR_CHECK → CREDIT_CHECK
           → [MANAGER_APPROVAL] → APPROVED
```

`MANAGER_APPROVAL` runs only when `amount` is greater than the configured `managerApprovalThreshold`.

Processing stops immediately when a stage returns `FAIL` or `MANUAL_REVIEW`.

### Idempotency rules

This operation must be idempotent.

If the application is already `APPROVED`, `REJECTED`, or `MANUAL_REVIEW`:

- no workflow stage is executed again;
- no duplicate history record is created;
- the existing status is not changed;
- the current application state is returned with `200 OK`.

### Approved response

```http
HTTP/1.1 200 OK
Content-Type: application/json
```

```json
{
  "loanId": "L-10001",
  "status": "APPROVED",
  "currentStage": null
}
```

### Rejected response

```http
HTTP/1.1 200 OK
Content-Type: application/json
```

```json
{
  "loanId": "L-10001",
  "status": "REJECTED",
  "currentStage": null
}
```

### Manual-review response

```http
HTTP/1.1 200 OK
Content-Type: application/json
```

```json
{
  "loanId": "L-10001",
  "status": "MANUAL_REVIEW",
  "currentStage": null
}
```

### Loan-not-found response

```http
HTTP/1.1 404 Not Found
Content-Type: application/json
```

```json
{
  "error": "LOAN_NOT_FOUND"
}
```

## Get a loan application

Returns the submitted data and current processing state of a loan application.

```http
GET /api/v1/loans/{loanId}
```

### Path parameters

| Parameter | Type | Required | Description |
| --- | --- | --- | --- |
| `loanId` | `string` | Yes | Unique loan application identifier |

### Success response

```http
HTTP/1.1 200 OK
Content-Type: application/json
```

```json
{
  "loanId": "L-10001",
  "customerId": "C-1001",
  "amount": 400000000,
  "phone": "09121234567",
  "loanType": "PERSONAL",
  "monthlyIncome": 50000000,
  "creditScore": 720,
  "hasGuarantor": false,
  "status": "APPROVED",
  "currentStage": null,
  "createdAt": "2026-07-15T10:00:00Z",
  "updatedAt": "2026-07-15T10:00:03Z"
}
```

### Response fields

| Field           | Type      | Nullable | Description                                           |
|-----------------|-----------|----------|-------------------------------------------------------|
| `loanId`        | `string`  | No       | Unique loan application identifier                    |
| `customerId`    | `string`  | No       | Customer identifier                                   |
| `amount`        | `integer` | No       | Requested loan amount                                 |
| `phone`         | `string`  | No       | Applicant's mobile number                             |
| `loanType`      | `string`  | No       | Loan type                                             |
| `monthlyIncome` | `integer` | No       | Applicant's monthly income                            |
| `creditScore`   | `integer` | No       | Applicant's credit score                              |
| `hasGuarantor`  | `boolean` | No       | Whether the applicant has a guarantor                 |
| `status`        | `string`  | No       | Current loan status                                   |
| `currentStage`  | `string`  | Yes      | Current workflow stage; `null` after processing stops |
| `createdAt`     | `string`  | No       | Creation timestamp in ISO 8601 UTC format             |
| `updatedAt`     | `string`  | No       | Last-update timestamp in ISO 8601 UTC format          |

### Loan-not-found response

```http
HTTP/1.1 404 Not Found
Content-Type: application/json
```

```json
{
  "error": "LOAN_NOT_FOUND"
}
```

## Get processing history

Returns workflow stage executions in chronological order.

```http
GET /api/v1/loans/{loanId}/history
```

### Path parameters

| Parameter | Type     | Required | Description                        |
|-----------|----------|----------|------------------------------------|
| `loanId`  | `string` | Yes      | Unique loan application identifier |

### History rules

- Records are ordered by `timestamp` from oldest to newest.
- A workflow stage is recorded at most once for an application.
- Reprocessing a terminal application does not add history records.
- `reason` contains `SUCCESS` for a successful stage or the applicable business error code for a failed validation.

### Success response

```http
HTTP/1.1 200 OK
Content-Type: application/json
```

```json
[
  {
    "stage": "VALIDATION",
    "result": "PASS",
    "timestamp": "2026-07-15T10:00:00Z",
    "reason": "SUCCESS"
  },
  {
    "stage": "FRAUD_CHECK",
    "result": "PASS",
    "timestamp": "2026-07-15T10:00:01Z",
    "reason": "SUCCESS"
  },
  {
    "stage": "CREDIT_CHECK",
    "result": "PASS",
    "timestamp": "2026-07-15T10:00:02Z",
    "reason": "SUCCESS"
  }
]
```

### History record fields

| Field       | Type     | Description                                              |
|-------------|----------|----------------------------------------------------------|
| `stage`     | `string` | Name of the executed workflow stage                      |
| `result`    | `string` | `PASS`, `FAIL`, or `MANUAL_REVIEW`                       |
| `timestamp` | `string` | Execution time in ISO 8601 UTC format                    |
| `reason`    | `string` | Reason or business error code associated with the result |

### Loan-not-found response

```http
HTTP/1.1 404 Not Found
Content-Type: application/json
```

```json
{
  "error": "LOAN_NOT_FOUND"
}
```

## Health check

Reports whether the service is available.

```http
GET /health
```

### Success response

```http
HTTP/1.1 200 OK
Content-Type: application/json
```

```json
{
  "status": "UP"
}
```

## Business rules

### Validation

The `VALIDATION` stage applies the following rules:

| Field           | Rule                                                          | Failure reason           |
|-----------------|---------------------------------------------------------------|--------------------------|
| `customerId`    | Required and not empty                                        | `INVALID_CUSTOMER_ID`    |
| `amount`        | Greater than `0`                                              | `INVALID_AMOUNT`         |
| `phone`         | Exactly 11 digits, starts with `09`, and contains digits only | `INVALID_PHONE`          |
| `loanType`      | `PERSONAL` or `BUSINESS`                                      | `INVALID_LOAN_TYPE`      |
| `monthlyIncome` | Greater than or equal to `0`                                  | `INVALID_MONTHLY_INCOME` |
| `creditScore`   | Between `0` and `1000`, inclusive                             | `INVALID_CREDIT_SCORE`   |

Any validation failure produces a `FAIL` stage result and changes the application status to `REJECTED`.

### Fraud check

The `FRAUD_CHECK` stage is mocked using `customerId`:

| Rule                 | Result          |
|----------------------|-----------------|
| Starts with `FRAUD`  | `FAIL`          |
| Starts with `REVIEW` | `MANUAL_REVIEW` |
| Any other value      | `PASS`          |

### Guarantor check

The `GUARANTOR_CHECK` stage runs only for `BUSINESS` loans:

| Rule                      | Result |
|---------------------------|--------|
| `hasGuarantor` is `false` | `FAIL` |
| `hasGuarantor` is `true`  | `PASS` |

### Credit check

The default credit-score rules are:

| Rule                       | Result          |
|----------------------------|-----------------|
| `creditScore < 500`        | `FAIL`          |
| `500 <= creditScore < 650` | `MANUAL_REVIEW` |
| `creditScore >= 650`       | `PASS`          |

The score boundaries must be loaded from configuration and must not be hard-coded.

### Manager approval

`MANAGER_APPROVAL` runs when the requested amount is greater than the configured `managerApprovalThreshold`.

| Rule                                        | Result |
|---------------------------------------------|--------|
| `amount > monthlyIncome × incomeMultiplier` | `FAIL` |
| Otherwise                                   | `PASS` |

Both `managerApprovalThreshold` and `incomeMultiplier` must be loaded from configuration.

## Error codes

### HTTP errors

| HTTP status       | Error code        | Description                                          |
|-------------------|-------------------|------------------------------------------------------|
| `400 Bad Request` | `INVALID_REQUEST` | The request body is not valid JSON                   |
| `404 Not Found`   | `LOAN_NOT_FOUND`  | No loan application exists for the supplied `loanId` |

HTTP error responses use this shape:

```json
{
  "error": "LOAN_NOT_FOUND"
}
```

### Business validation errors

Business validation errors do not produce HTTP `400` responses during loan creation. They are recorded as the `reason` of the failed `VALIDATION` history entry.

| Error code               | Description                        |
|--------------------------|------------------------------------|
| `INVALID_AMOUNT`         | The requested amount is invalid    |
| `INVALID_PHONE`          | The mobile number is invalid       |
| `INVALID_CUSTOMER_ID`    | The customer identifier is invalid |
| `INVALID_LOAN_TYPE`      | The loan type is invalid           |
| `INVALID_CREDIT_SCORE`   | The credit score is invalid        |
| `INVALID_MONTHLY_INCOME` | The monthly income is invalid      |
