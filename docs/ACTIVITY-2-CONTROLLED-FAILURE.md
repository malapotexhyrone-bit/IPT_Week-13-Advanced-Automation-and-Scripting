# Activity 2 Controlled Failure Behavior

## Decision question

How should the Student Clearance Workflow demonstrate dependency failure safely while proving that duplicate effects, endless retry, and silent data loss are prevented?

## Assumptions constraints and acceptance authority

- All records are fictional and remain in the controlled local environment.
- The local audit notification gateway is the bounded dependency; it does not contact a real university, email, or SMS service.
- Laravel, the database queue, and MariaDB are available for the demonstration.
- Automation may create reminders, escalation records, completion-delivery records, and administrator work, but it may not approve an office task or resolve a deficiency.
- The authorized administrator accepts a permanent-failure result for human review. Clearing-office users cannot perform administrator recovery.

## Approaches compared

| Approach | Advantages | Disadvantages and risks | Decision |
| --- | --- | --- | --- |
| Immediate unbounded retry inside the request | Simple to demonstrate; no queue coordination | Can block the request, overload an unavailable dependency, hide operational failure, and repeat effects | Rejected |
| Database-tracked bounded queue processing | Retains attempts, supports correlation and idempotency, survives worker restart, and routes exhausted work to a person | Requires a queue worker, scheduler, uniqueness rules, and recovery ownership | Selected |
| Fail once and discard the item | Minimal implementation effort | Creates silent data loss and provides no recovery or responsible owner | Rejected |

The selected approach was judged against reliability, security, duplicate risk, observability, implementation effort, recovery ownership, and reproducibility.

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

## One page interpretation

### Observed facts

The successful case delivered once, and reprocessing the delivered record created no additional attempt. The controlled transient failure recorded a safe failure on attempt 1 and delivered on attempt 2 with the same correlation identifier. The repeated transient failure stopped on attempt 3 and created one human-action item. The non-retryable case created human action after one attempt. An office user received `403 ROLE_FORBIDDEN` when attempting administrator recovery.

### Reasoned conclusions

The important result is not that failures disappear; it is that each failure reaches a bounded, explainable state. A temporary failure can recover without duplicating its intended effect because the database enforces one idempotency key and the processor returns immediately for an already delivered record. Repeated or non-retryable failure stops automation and assigns a safe next step to an authorized administrator. The retry endpoint preserves the earlier attempts and grants only one new three-attempt cycle, so recovery does not erase history or retry forever.

The structured samples contain identifiers, automation type, attempt, outcome, safe error code, and correlation identifier. They omit message bodies, passwords, tokens, exception traces, and personal details. This makes them useful for maintenance while limiting exposure.

### Recommendation

Keep database-tracked bounded processing for the classroom prototype. Preserve the same idempotency, safe error classification, correlation identifier, and human-action boundary if an instructor-approved notification provider is added later. Before broader use, add provider-specific timeouts, operational metrics, alert thresholds, and a reviewed dead-letter or equivalent human-action procedure.

## Maintainability note

The scheduler decides *when* work is due, the delivery service decides *how* an attempt changes state, and the gateway owns the dependency call. This separation keeps the code readable and permits a future approved email or messaging adapter without changing clearance rules. The main operational dependencies remain the scheduler and database queue worker. If either stops, records remain recoverable in the database, but delivery is delayed until processing resumes.

## Limitation and next improvement

The controlled gateway proves application behavior but does not prove the reliability, latency, authentication, or rate limits of a real provider. The next improvement is to add a provider contract test with an explicit timeout and failure budget in an instructor-approved nonproduction environment.
