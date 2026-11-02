# Week 13 Advanced Automation and Scripting Report

## Submission details

- Student: Malapote, Xhyrone Wayne N.
- Section: BSIT 3-A
- Course: PFEBSIT 002 Integrative Programming and Technologies 2
- Project: Project 8 Student Clearance Workflow
- Week: 13 Advanced Automation and Scripting
- Repository: `IPT_Week-13-Advanced-Automation-and-Scripting`
- Verified build commit: `34d1a5844691ef5ba241dd79129cda5492da0d6c`

## Executive summary

The Week 13 build automates the bounded Student Clearance Workflow without automating office decisions. A student initiates one clearance for an active academic term, and the Laravel API creates one task for each active clearing office in a single transaction. Assigned office personnel can report a deficiency or approve an eligible task. The student can submit a resolution, the assigned office can re-review it, and the clearance completes only after every office task is approved.

The automation layer creates due reminders, escalates stalled tasks, records one completion notification, retries transient dependency failures within a three-attempt limit, and routes exhausted or permanent failures to an authorized administrator. Database uniqueness constraints, idempotency keys, correlation identifiers, structured safe logs, and retained attempt history make the behavior repeatable and explainable. The Flutter Web interface demonstrates normal, validation, unavailable-service, unauthorized, deficiency, recovery, re-review, completion, and responsive states using fictional local data.

## Objectives and bounded scope

Week 13 addresses office-task creation, reminder scheduling, stalled-step escalation, clearance-completion notification, bounded retry, recovery, structured logging, and human intervention. The workflow boundary begins when an authenticated student initiates clearance and ends when all office tasks are approved and one completion delivery is recorded.

Automation does not approve a task, resolve a deficiency, override an office decision, invent a missing record, or retry forever. Clearing-office personnel own deficiency and approval decisions. The student owns deficiency resolution. An administrator owns permanent-failure review and eligible manual retry. No production university service, real student record, email provider, SMS provider, or production credential is connected.

## Development environment

- Flutter 3.47.8 and Dart 3.13.7 for the browser client
- Laravel 12.70.2 on PHP 8.2.12 for the API and automation
- XAMPP MariaDB 10.4.32 through the MySQL driver
- Laravel Sanctum for controlled API tokens
- Git 2.55.0 with a separate Week 13 repository

Laravel 12 is compatible with the installed PHP 8.2 runtime. The local database `student_clearance_week13` contains only fictional demonstration records. Secrets remain in the ignored local `.env`; the repository contains only a sanitized `.env.example`.

## Activity 1 Trigger and execution pattern comparison

The comparison in `ACTIVITY-1-TRIGGER-PATTERNS.md` evaluates three contrasting automation patterns.

1. Clearance initiation uses a validated request and database transaction because every active office task must be created together or none should be created.
2. Pending reminders and stalled-step escalation use a scheduled scan because eligibility depends on time and recoverability after worker downtime.
3. Completion notification is event-driven because the final office approval provides an immediate and unambiguous trigger.

The selected hybrid pattern keeps the critical workflow understandable. Transactions protect consistency, schedules make time-based work inspectable, and an event-driven completion record preserves low latency. At-least-once queue behavior is handled through unique idempotency keys instead of assuming a job runs only once.

## Activity 2 Controlled failure behavior

`ACTIVITY-2-CONTROLLED-FAILURE.md` records five controlled cases. A normal delivery completed once, and duplicate processing added no attempt. A transient failure recovered on attempt 2. A repeatedly failing dependency stopped on attempt 3 and created one human-action item. A non-retryable failure went directly to human action. An office user attempting administrator recovery received `403 ROLE_FORBIDDEN` without changing state.

The saved JSONL records preserve automation type, attempt, outcome, safe error code, and correlation identifier. They exclude tokens, passwords, message bodies, stack traces, SQL, and unnecessary personal data. The failure evidence demonstrates bounded behavior rather than claiming that failures never occur.

## Architecture and workflow

The repository separates browser presentation, HTTP authorization, workflow rules, automation scheduling, delivery processing, and the notification boundary.

- Flutter Web presents role-specific views and safe recovery states.
- Laravel controllers validate requests and return bounded response shapes.
- Workflow services perform transactional state changes and correlated events.
- MariaDB stores users, offices, terms, clearances, tasks, deficiencies, events, deliveries, attempts, and human-action items.
- The scheduler finds due work and creates idempotent delivery records.
- The queue job processes one delivery through the notification gateway.
- The local audit gateway represents the controlled dependency boundary without contacting a real service.

The principal task transition is:

```text
pending -> deficiency -> awaiting_re_review -> approved
pending -------------------------------------> approved
all office tasks approved -> clearance completed
```

The final approval creates or retrieves the unique completion-delivery record only after the transaction commits. A queue worker then records the delivery attempt. If the adapter fails transiently, the same delivery remains correlated and eligible for bounded retry. If retry is exhausted or the failure is permanent, one human-action item is created.

## Role and authorization boundaries

| Role | Allowed Week 13 actions | Explicitly blocked actions |
| --- | --- | --- |
| Student | Initiate own clearance, read own clearance, submit resolution for own deficiency | Review another student, approve an office task, inspect automation failures |
| Clearing office | Read tasks assigned to its office, report deficiency, approve eligible assigned task | Review another office, resolve a student deficiency, retry automation |
| Administrator | Inspect safe permanent-failure records and request eligible retry | Approve office tasks or silently bypass failure history |

The API derives identity, role, office, and clearance ownership from the authenticated user. Client-supplied owner, office, reviewer, task status, or clearance status values are not trusted.

## API and Flutter integration

The Flutter client calls implemented JSON endpoints for controlled demo sessions, current-user context, current clearance, assigned-office queues, initiation, deficiency reporting, student resolution, office approval, automation failure inspection, and eligible retry. The demo-session endpoint is local-only and uses fictional seeded users. Tokens stay in memory and are not stored in the repository.

The browser interface includes explicit loading, empty, validation, unavailable-service, unauthorized, and success states. Real evidence shows an invalid term rejected inline, a CORS origin mismatch surfaced as a safe network error, the corrected preflight allowed the exact local origin, a revoked token produced an authentication-required view, and a 502-pixel-wide window reflowed the completed workflow without horizontal overflow.

## Retry and recovery policy

Each automation delivery has one stable idempotency key and correlation identifier. The classroom retry limit is three attempts per cycle. A retryable adapter failure schedules the next attempt with bounded one-to-two-second classroom backoff. A delivered record is terminal and duplicate processing returns without creating another effect. An exhausted retryable failure or non-retryable failure creates one administrator human-action item.

The administrator retry endpoint is role-protected. It preserves earlier attempts and grants exactly one additional three-attempt cycle only when the item is eligible. It does not erase history or create an unbounded loop. The scheduler and database queue are operational dependencies; if either stops, work is delayed but the database records remain available for controlled recovery.

## Verification and evidence

The final automated verification passed:

- Laravel Pint formatting check: passed
- Laravel regression suite: 32 tests and 134 assertions passed
- Dart formatting: 10 files checked and 0 changed
- Flutter analyzer: no issues found
- Flutter widget tests: 4 passed
- Sensitive-token pattern scan before Checkpoint 5: no matches
- Git whitespace checks before each pushed checkpoint: passed

The live end-to-end journey used fictional student Ari Santos. The Library reviewer reported a deficiency, the student submitted a resolution, the Library task moved to awaiting re-review, every office task was approved, the clearance completed, and the completion notification was delivered in one attempt. `EVID-W13-E2E-001` records the expected and actual outcomes. Application screenshots `EVID-W13-APP-000` through `EVID-W13-APP-007` show the real browser states.

The failure portfolio includes a recovered transient failure and a permanent failure routed for human action. `EVID-W13-FAIL-001` and `EVID-W13-FAIL-002` preserve sanitized structured outcomes with correlation identifiers. `EVID-W13-FAIL-003` preserves the real unavailable-service UI state before the CORS correction.

## Maintainability evaluation

The implementation favors conventional Laravel and Flutter structure over additional frameworks. Small controllers delegate workflow changes to focused services. The notification gateway isolates the bounded external dependency. Database constraints enforce important duplicate rules even if an HTTP request or queue job is repeated. Feature tests cover the service boundary and role restrictions, while Flutter widget tests cover critical presentation states.

An authorized maintainer should verify the scheduler, queue worker, database connection, environment flags, CORS origins, and protected configuration before running the project. Changes to office lists should be made through controlled data rather than hard-coded UI behavior. A future approved notification provider should implement the gateway contract and retain the same safe error classification, idempotency, and retry policy.

The main maintainability risks are operational rather than hidden in the UI: a stopped scheduler delays due-record discovery, a stopped queue delays delivery, and an incorrect environment flag could expose the demo-session route. These risks are visible in configuration and recoverable records. Production identity integration, provider-specific monitoring, load testing, HTTPS validation, cryptographic exchange, broader hardening, deployment evaluation, and formal UAT remain later-week work.

## Limitations

- The build is a controlled local prototype, not a production-readiness claim.
- No real university identity provider, student information system, mail, SMS, or webhook is connected.
- Local HTTP does not demonstrate the HTTPS and authenticated-encryption requirements that belong to Week 14.
- The tests demonstrate functional and bounded failure behavior, not production capacity or availability.
- Human instructor assessment, stakeholder UAT, and examination results have not been performed or invented.
- The student must personally review the report, execute the setup, and explain the controls before submission or defense.

## Repository history

| Checkpoint | Commit | Purpose |
| --- | --- | --- |
| 1 | `1361028` | Framework setup, scope, architecture, activities, and evidence foundation |
| 2 | `10c9a88` | Authenticated clearance initiation and SQL data model |
| 3 | `aceb8c6` | Deficiency, resolution, approval, and completion workflow |
| 4 | `f9c1fc4` | Reminder, escalation, bounded retry, and human-action recovery |
| 5 | `a31c831` | Flutter Web integration and role-scoped read APIs |
| 6 | `34d1a58` | Complete clearance journey and responsive application evidence |

## Individual responsibility and AI acknowledgment

This is an individual Project 8 submission. AI assistance supported planning, implementation, debugging, test preparation, evidence organization, and documentation. The repository history and saved evidence show the implemented states but do not replace the student's responsibility. Malapote, Xhyrone Wayne N. must review the code, reproduce the checks, understand the design decisions, revise the reflection in a personal voice, and acknowledge assistance according to institutional policy.

## Student reflection worksheet

The following prompts require the student's personal review before submission.

1. Explain why scheduled scans are used for reminders while completion notification is event-driven.
2. Identify the evidence that proves a transient failure recovered without creating a duplicate effect.
3. Explain why permanent failure creates administrator work instead of retrying forever.
4. Describe how the API prevents one office from reviewing another office's task.
5. State one implementation decision you would keep and one improvement you would make before broader use.
6. Confirm which commands and screens you personally reproduced and can demonstrate without assistance.

## References

- PFEBSIT 002 Weeks 13 to 18 Student Module Flutter Web
- Project Options Weeks 13 to 18 Individual Student Project Guide Project 8
- Laravel 12 framework documentation used through installed framework conventions
- Flutter and Dart SDK documentation used through installed SDK conventions
- Repository source, tests, evidence index, structured logs, and checkpoint history
