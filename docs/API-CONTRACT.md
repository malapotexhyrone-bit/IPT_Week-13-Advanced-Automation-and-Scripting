# API Contract

Only routes marked implemented are authoritative at Checkpoint 2. Planned routes remain design targets until their code and automated tests exist.

| Status | Method and path | Authorized actor | Purpose | Key outcomes |
| --- | --- | --- | --- | --- |
| Implemented | `POST /api/v1/clearances` | Student | Initiate one clearance and create one task per active office | `201`, `409` duplicate, `422` invalid, `401` unauthenticated, `403` wrong role, `503` no active offices |
| Planned | `GET /api/v1/clearances/current` | Student | Read the authenticated student's clearance | `200`, `404`, `401` |
| Planned | `POST /api/v1/office-tasks/{task}/deficiencies` | Assigned office personnel | Record a deficiency | `201`, `403`, `409` invalid state, `422` invalid |
| Planned | `POST /api/v1/deficiencies/{deficiency}/resolution` | Owning student | Submit a resolution note | `200`, `403`, `409`, `422` |
| Planned | `POST /api/v1/office-tasks/{task}/approve` | Assigned office personnel | Approve an eligible office task | `200`, `403`, `409`, `422` |
| Planned | `GET /api/v1/admin/automation-failures` | Administrator | List stalled or permanently failed automation | `200`, `403` |
| Planned | `POST /api/v1/admin/automation-failures/{failure}/retry` | Administrator | Retry an eligible failed item | `202`, `403`, `409` |

## Implemented clearance initiation

Request body:

```json
{
  "term_code": "2026-2027-1"
}
```

The optional `Idempotency-Key` header must be 8 to 100 safe ASCII characters. The server stores only its SHA-256 hash. A successful response includes the clearance, active academic term, and pending office tasks. The database transaction creates the clearance, all tasks, and the `clearance_initiated` event together or creates none of them.

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
