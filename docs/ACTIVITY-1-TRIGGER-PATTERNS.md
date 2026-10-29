# Activity 1 Trigger and Execution Pattern Comparison

## Scenario comparison

| Workflow | Latency and volume | Selected pattern | Alternative | Reliability and staffing consequence |
| --- | --- | --- | --- | --- |
| Create office tasks after clearance initiation | Immediate; one small fan-out per clearance | Validated request plus transactional creation and after-commit queued work | Scheduled polling for new clearances | The transaction prevents partial task sets. Queue duplication must be controlled by unique keys. Staff handle only rejected or permanently failed items. |
| Remind pending offices and escalate stalled tasks | Time-based; volume grows with pending tasks | Scheduled due-record scan with chunking and per-task idempotent jobs | One delayed job created for every task | A schedule is easier to inspect and recover after downtime. Indexes and bounded batches are required to avoid large scans. Administrators own stalled-item review. |
| Notify completion after the final approval | Immediate; one event per completed clearance | Domain event plus idempotent queued notification | Frequent completion polling | The event preserves low latency and clear causality. A unique notification key prevents duplicates. A permanent adapter failure creates human action instead of hiding the result. |

## Assumptions and constraints

- Project 8 is instructor-approved and uniquely assigned to the student.
- The implementation uses controlled fictional data in a local environment.
- Laravel scheduler, database queue, and MariaDB are available locally.
- No production mail, SMS, or university service is authorized.
- Clearing-office personnel remain responsible for office decisions.

## One-page interpretation

### Observed facts

Clearance initiation and final approval are state-change events, while reminders and escalation depend on elapsed time. The installed environment supports Laravel queues and schedules with a MariaDB database. The project scope does not authorize a real notification provider or external webhook producer.

### Reasoned conclusions

No single trigger pattern fits all three workflows. Event-driven processing is appropriate when a specific validated state change should cause immediate work. A scheduled scan is appropriate when eligibility depends on a due time and when recovery after local downtime matters more than second-level latency. Database transactions are necessary for clearance and office-task creation because a partially created office set would be difficult to explain or recover. At-least-once queue behavior means the work must tolerate a repeated delivery rather than assuming each job runs exactly once.

The hybrid design also preserves human responsibility. Automation creates and monitors tasks, but it does not approve deficiencies, override office decisions, or silently discard permanent failures. The administrator receives a human-action item when bounded automated recovery is exhausted.

### Recommendation

Implement transactional parallel office-task creation, scheduled reminder and escalation scans, and an event-driven completion notification. Add database uniqueness constraints, idempotency keys, correlation identifiers, bounded retry with backoff, sanitized structured logs, and a permanent-failure record. Revisit the pattern only if an approved deployment environment cannot run workers or if the instructor requires sequential office processing.

## Limitation and next improvement

This decision is based on the local approved environment and has not yet been validated with implementation timing evidence. The next improvement is to implement the smallest initiation-to-task path and preserve actual success, duplicate, and dependency-failure test records.
