# Week 13 Project Scope

## Decision question

How can a student-clearance prototype create and monitor office tasks reliably while keeping office decisions and exceptional cases under authorized human control?

## Actors and initial permissions

| Actor | Permitted actions in the bounded prototype |
| --- | --- |
| Student | Initiate one clearance for the controlled term, view only their own clearance, read deficiencies assigned to them, and submit a deficiency-resolution note. |
| Clearing-office personnel | View tasks assigned to their office, record a deficiency, request re-review, or approve an eligible task. They cannot act for another office. |
| Authorized administrator | Configure the controlled office list, inspect automation failures, retry an eligible failed job, route permanent failures for human action, and view overall status. |

The backend is authoritative for identity, ownership, role, office assignment, state transitions, and validation. The Flutter client displays permissions but does not enforce them by itself.

## Workflow boundary

1. A student submits a valid clearance initiation request.
2. The backend creates one clearance and one task for each configured office using a single database transaction.
3. Offices review their assigned tasks in parallel.
4. An office either records a deficiency or approves its task.
5. A student submits a resolution for a deficiency; the same office re-reviews it.
6. Pending tasks receive scheduled reminders.
7. Stalled tasks are escalated to an authorized administrator.
8. When every required office task is approved, the clearance becomes completed and one completion notification is recorded.

## Trusted inputs and validation

- Authenticated server-side user identifier and role
- Server-controlled office membership and active office list
- Valid academic term from controlled configuration
- Text length and allowed-state validation for deficiency and resolution notes
- Server-generated correlation and idempotency identifiers

Client-provided role, ownership, completion state, office assignment, retry count, and timestamps are never trusted.

## Outputs

- Clearance and office-task records
- Auditable state-transition records
- Reminder, escalation, and completion-notification outcomes
- Structured sanitized logs with correlation identifiers
- Human-action records for permanent failures

## Dependencies

- Local MariaDB database
- Laravel queue and scheduler processes
- A bounded notification adapter that records controlled outcomes rather than contacting real people
- Flutter Web client connecting only to the local approved API

## Human intervention points

- Office review and approval
- Deficiency assessment after a student resolution
- Administrator review of stalled tasks
- Administrator handling of permanent automation failure
- Any correction that changes a recorded office decision

## Non-goals for Week 13

- Production deployment or connection to university systems
- Real email, SMS, push notification, or student records
- Payment, grades, enrollment eligibility, identity verification, or document upload
- Mobile packaging
- Week 14 cryptography beyond established framework defaults and safe configuration foundations
- Automated approval of deficiencies or sensitive exceptions

## Acceptance paths

- Normal: valid initiation through all office approvals and one completion notification
- Invalid: missing or malformed input returns a safe validation error with no partial records
- Unauthorized or forbidden: unauthenticated or wrong-role action is rejected without state change
- Unavailable service: bounded notification failure is observable and does not corrupt clearance state
- Recovery: a transient failure succeeds within the retry limit without duplicate processing
- Permanent failure: retries stop and the item is routed for authorized human action
