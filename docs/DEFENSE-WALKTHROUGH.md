# Week 13 Defense Walkthrough

## Five minute demonstration order

1. Open the role-selection screen and explain that all records are fictional and local.
2. Enter as the student and show the completed clearance with four approved offices.
3. Use `EVID-W13-APP-005` and `EVID-W13-APP-006` to explain the deficiency and resolution path.
4. Open the office queue and explain assigned-office authorization.
5. Explain the three automation triggers: scheduled reminder, stalled-task escalation, and event-driven completion.
6. Show the transient-recovery and permanent-human-action JSONL evidence.
7. Show the delivered completion record and correlation identifier in `EVID-W13-E2E-001`.
8. Resize the browser or show `EVID-W13-APP-004` to explain responsive behavior.

## Questions the student should answer

### Why use a database transaction for office-task creation

The clearance and every active office task must succeed together. A partial office list would leave the workflow inconsistent, so the transaction rolls back all records if creation fails.

### What prevents duplicate work

Clearance and office-task uniqueness constraints protect SQL records. Hashed HTTP idempotency keys protect repeated initiation. Automation deliveries use stable unique idempotency keys, and a delivered record returns without adding another attempt or effect.

### Why not retry forever

Unlimited retry hides operational failure and can overload a dependency. The implementation stops after three attempts, records safe outcomes, and creates one administrator human-action item.

### What is a correlation identifier

It is a safe identifier carried through a request or automation delivery so logs, attempts, and evidence can be connected without exposing a token or personal message body.

### How are roles enforced

Sanctum authenticates the user. Server middleware and workflow services check the authenticated role, assigned office, and clearance ownership. The API does not trust a role or owner sent by Flutter.

### What remains outside Week 13

Production identity, HTTPS and authenticated encryption, expanded hardening, performance evaluation, deployment review, formal UAT, and final defense assessment belong to later weeks.

## Student verification checklist

- Run `php artisan test` and explain at least one adverse test.
- Run `flutter analyze` and `flutter test`.
- Start MariaDB, Laravel, and Flutter from the README instructions.
- Demonstrate one student action, one office action, and one automation outcome.
- Explain the difference between a recovered transient failure and permanent human action.
- Review and personalize the reflection worksheet in the main report.
