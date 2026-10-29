# Architecture Decision 001 Automation Triggers

## Context

The workflow needs low-latency office-task creation, time-based reminders and escalation, and one completion notification. The design must tolerate duplicate delivery, unavailable dependencies, and process restarts.

## Decision

Use a hybrid design:

- An authenticated clearance-initiation request performs validated clearance and office-task creation in one database transaction, then dispatches bounded jobs after the transaction commits.
- A Laravel scheduler scans only due pending tasks for reminders and stalled-step escalation.
- A domain event after an office approval checks completion and dispatches one idempotent completion-notification job.
- Database-backed queues provide observable retry state. Permanent failure records a human-action item instead of continuing indefinitely.

## Alternatives considered

### Fully synchronous request processing

This has simple control flow and immediate responses. It was rejected for notifications because a slow or unavailable adapter would delay the user request and couple record integrity to an external action.

### Poll everything on a frequent schedule

This recovers naturally after missed events. It was rejected for task creation and completion because it adds latency, repeatedly scans unchanged rows, and makes causality less clear. Scheduled scans remain appropriate for time-based reminder and escalation rules.

### Webhook-only automation

This offers low latency between services. It was rejected because the prototype has no approved external producer, and time-based reminders still require a scheduler. Adding public webhook authentication would expand Week 13 scope.

## Consequences

- Jobs must be idempotent because database queues can deliver work more than once.
- Transaction boundaries and after-commit dispatch must be tested.
- The scheduler and queue worker become explicit operational dependencies.
- Local evidence can show queued, retried, completed, and permanently failed states without using production services.

## Review condition

Review this decision if the instructor requires strictly sequential office review, approves a real notification service, or changes the runtime environment so background workers cannot run.
