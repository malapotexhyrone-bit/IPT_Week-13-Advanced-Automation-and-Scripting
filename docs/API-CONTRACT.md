# API Contract

Only routes marked implemented are authoritative. Planned routes remain design targets until their code and automated tests exist.

| Status | Method and path | Authorized actor | Purpose | Key outcomes |
| --- | --- | --- | --- | --- |
| Implemented | `POST /api/v1/clearances` | Student | Initiate one clearance and create one task per active office | `201`, `409` duplicate, `422` invalid, `401` unauthenticated, `403` wrong role, `503` no active offices |
| Implemented | `POST /api/v1/auth/login` | Fictional registered actor | Verify email and password hash, apply request throttling, and issue a two-hour Sanctum token containing the server-owned role | `200`, `401` generic invalid credentials, `422` invalid input, `429` throttled |
| Implemented | `POST /api/v1/auth/logout` | Authenticated actor | Revoke the current bearer token | `204`, `401` |
| Implemented | `GET /api/v1/me` | Authenticated actor | Read the minimum role and office context required by the Flutter shell | `200`, `401` |
| Implemented | `GET /api/v1/clearances/current` | Student | Read the authenticated student's clearance and office-task summary | `200`, `404`, `401`, `403` |
| Implemented | `GET /api/v1/office-tasks` | Office personnel | List only tasks assigned to the authenticated reviewer's office | `200`, `401`, `403` |
| Implemented | `POST /api/v1/office-tasks/{officeTask}/deficiencies` | Assigned office personnel | Record one open deficiency and move the task to `deficiency` | `201`, `403`, `409` invalid state, `422` invalid |
| Implemented | `POST /api/v1/deficiencies/{deficiency}/resolution` | Owning student | Submit a resolution note and move the task to `awaiting_re_review` | `200`, `403`, `409`, `422` |
| Implemented | `POST /api/v1/office-tasks/{officeTask}/approve` | Assigned office personnel | Approve a pending or re-review task; complete the clearance after the final approval | `200`, `403`, `409` |
| Implemented | `GET /api/v1/automation/failures` | Administrator | List automation items requiring human action without exposing message bodies or student details | `200`, `403` |
| Implemented | `POST /api/v1/automation/deliveries/{automationDelivery}/retry` | Administrator | Retry an eligible transient failure while preserving earlier attempts and adding one bounded cycle | `202`, `403`, `409` |
| Implemented | `POST /api/v1/secure-exchange/send` | Administrator | Load server-owned clearance data, minimize and encrypt it, and send it through validated HTTPS | `202`, `401`, `403`, `422`, `503` |
| Implemented | `POST /api/v1/secure-exchange/receive` | HMAC-authenticated service | Verify the service request and authenticated envelope, reject stale/replayed data, and store safe receipt metadata | `202`, `401`, `409`, `422`, `503` |

## Implemented authentication controls

The login request accepts only `email` and `password`. A client-provided role is ignored: the response role and office assignment come from the matched server record. Passwords are verified through Laravel hashing and never returned or logged. Unknown email and wrong password receive the same `INVALID_CREDENTIALS` response, including a dummy hash check for the unknown-account path. The endpoint permits five attempts per minute before returning `429`. A successful login revokes the actor's earlier `flutter-web` token and issues one two-hour Sanctum token. Logout deletes the current token.

Seeded accounts do not contain a committed usable password. `tools/reset-demo-password.php` generates a strong one-time-displayed password for an existing fictional `@example.test` account and stores only its hash.

## Implemented automation controls

The scheduler creates daily reminder effects, one stalled-task escalation, and one completion notification using unique database idempotency keys. Each delivery records up to three attempts with exponential classroom backoff. An exhausted retryable failure or a non-retryable adapter response creates one human-action item. Only an administrator can inspect those items or request an eligible retry. The retry retains the attempt history and extends the limit by exactly three attempts rather than creating an unbounded loop.

## Implemented clearance initiation

Request body:

```json
{
  "term_code": "2026-2027-1"
}
```

The optional `Idempotency-Key` header must be 8 to 100 safe ASCII characters. The server stores only its SHA-256 hash. A successful response includes the clearance, active academic term, and pending office tasks. The database transaction creates the clearance, all tasks, and the `clearance_initiated` event together or creates none of them.

## Implemented office review state transitions

The server derives the reviewer's office and the student's clearance ownership from the authenticated user. It never accepts an office, reviewer, student, task status, or clearance status from the client.

```text
pending -> deficiency -> awaiting_re_review -> approved
pending -------------------------------------> approved
all office tasks approved -> clearance completed
```

A deficiency request requires a 5-to-1000-character `description`. A resolution requires a 5-to-1000-character `resolution_note`. An office cannot review another office's task, another student cannot resolve the owning student's deficiency, an open deficiency blocks approval, and a completed approval cannot be repeated. Each accepted transition and final completion writes a correlated audit event in the same database transaction.

## Common request rules

- Accept and return JSON.
- Use server-authenticated identity and role; ignore any client role or owner field.
- Accept `Idempotency-Key` on creation and manual retry requests.
- Return `X-Correlation-ID` on every response and accept a safe client-supplied value only when it matches the allowed format.
- Validation responses use an error code, safe message, field errors when applicable, and correlation identifier.

## Example safe error shape

```json
{
  "error": {
    "code": "CLEARANCE_ALREADY_EXISTS",
    "message": "A clearance already exists for this term.",
    "correlation_id": "01JEXAMPLE0000000000000000",
    "fields": {}
  }
}
```

Diagnostic exceptions, stack traces, SQL, tokens, and personal data must not appear in client responses.
