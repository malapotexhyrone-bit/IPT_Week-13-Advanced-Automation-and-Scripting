# Changelog

## Unreleased

### Added

- Fresh Week 13 repository connected to the supplied GitHub remote.
- Laravel 12 backend scaffold compatible with the installed PHP 8.2 runtime.
- Flutter Web-only frontend scaffold.
- MariaDB/MySQL example configuration without secrets.
- Week 13 scope, trigger decision, retry policy, screen flow, API draft, activity record, and evidence index.
- Laravel Sanctum token-authentication foundation and student role middleware.
- MariaDB migrations for offices, academic terms, role assignments, clearances, office tasks, and correlated clearance events.
- Fictional seed records for four clearing offices, one active term, and the three bounded roles.
- Transactional `POST /api/v1/clearances` with validation, correlation identifiers, idempotency-key hashing, duplicate protection, and safe errors.
- Seven clearance-initiation feature tests covering the normal path and six adverse paths.
- Deficiency records with open/resolved states and controlled resolution notes.
- Assigned-office deficiency reporting and approval APIs plus owning-student resolution API.
- Transactional task state transitions, correlated audit events, and single final clearance completion.
- Nine office-workflow feature tests covering normal, unauthorized, invalid, and completion paths.
- Scheduled office reminders, stalled-task escalation, and idempotent completion-notification records.
- Database-backed delivery attempts with structured safe logs, bounded backoff, and duplicate-effect guards.
- Controlled transient recovery and permanent-failure routing to a human-action item.
- Administrator-only failure inspection and eligible manual retry with preserved history.
- Ten automation feature tests; the full regression suite now passes 28 tests and 117 assertions.

### Not yet implemented

- Flutter workflow screens and final consolidated evidence.
