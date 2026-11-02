# Activity 2 — Controlled Failure Behavior

## Controlled setup

The notification boundary is an interface so the test can replace the local audit adapter without changing workflow code. The controlled adapter throws only a safe error code. It does not contact a real service or use real student data. The automation record stores its stable idempotency key, correlation identifier, attempt count, next eligible attempt, and final status.

## Demonstration results

| Case | Injection | Expected | Actual | Evidence |
| --- | --- | --- | --- | --- |
| Normal | No failure | One delivered result | Delivered on attempt 1; duplicate processing added no attempt | `EVID-W13-AUTO-001` |
| Transient recovery | First adapter call fails | Retry within the three-attempt limit, then one result | Attempt 1 scheduled a retry; attempt 2 delivered | `EVID-W13-FAIL-001` |
| Exhausted transient | Adapter fails repeatedly | Stop after attempt 3 and create administrator work | Attempts 1–2 scheduled retries; attempt 3 created one human-action item | `EVID-W13-FAIL-002` |
| Non-retryable | Adapter rejects immediately | No automated retry | One attempt, then human action | `EVID-W13-AUTO-001` |
| Unauthorized recovery | Office user requests retry | Reject without changing state | `403 ROLE_FORBIDDEN` | `EVID-W13-AUTO-001` |

## Interpretation

The important result is not that failures disappear; it is that each failure reaches a bounded, explainable state. A temporary failure can recover without duplicating its intended effect because the database enforces one idempotency key and the processor returns immediately for an already delivered record. Repeated or non-retryable failure stops automation and assigns a safe next step to an authorized administrator. The retry endpoint preserves the earlier attempts and grants only one new three-attempt cycle, so recovery does not erase history or retry forever.

The structured samples contain identifiers, automation type, attempt, outcome, safe error code, and correlation identifier. They omit message bodies, passwords, tokens, exception traces, and personal details. This makes them useful for maintenance while limiting exposure.

## Maintainability note

The scheduler decides *when* work is due, the delivery service decides *how* an attempt changes state, and the gateway owns the dependency call. This separation keeps the code readable and permits a future approved email or messaging adapter without changing clearance rules. The main operational dependencies remain the scheduler and database queue worker. If either stops, records remain recoverable in the database, but delivery is delayed until processing resumes.
