# Changelog

This changelog follows the continuing Weeks 13-18 Student Clearance Workflow. Each completed week is preserved by its own version tag and GitHub release.

## Unreleased - Week 14 Secure Data Exchange and Encryption

### Added

- Administrator-only secure-exchange sender and HMAC-authenticated controlled receiver.
- Minimized clearance payload protected with AES-256-GCM authenticated encryption.
- Certificate-validated local HTTPS scripts, ignored key material, and safe configuration placeholders.
- Short request and message validity windows, unique receipt storage, and replay rejection.
- Safe receipt records containing message metadata and a payload hash without plaintext or ciphertext.
- Twelve focused security tests covering the success path and eleven adverse or authorization cases.
- Password login with Laravel hashing, generic invalid-credential responses, five-attempt-per-minute throttling, two-hour Sanctum tokens, and token revocation on logout.
- Responsive Flutter sign-in screen whose dashboard is selected only from the server-owned role and office assignment.
- Administrator Flutter action for the protected clearance exchange plus a safe local fictional-password reset tool.
- Five authentication tests and six Flutter widget tests; the full backend suite now passes 47 tests and 201 assertions.
- Six visually inspected real Flutter browser captures covering desktop/narrow login, invalid credentials, student role, Library role, and an accepted administrator secure exchange.
- Consolidated Week 14 report covering both activities, SA8, IA5, threat and risk records, decisions, reproduction, limitations, and defense preparation.

### Security

- Updated Symfony HTTP Foundation, HTTP Kernel, Routing, and YAML from 7.4.22 to 7.4.23 after Composer reported seven advisories published on 4 November 2026.
- Re-ran Composer audit with no known advisories, Laravel Pint, and the complete 47-test, 201-assertion regression suite.

### Pending

- Central evidence DOCX, student-verified reflection, documentation checkpoint commit, final tag, and release.

## v13.0.0-week13-automation - Week 13 Advanced Automation and Scripting

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
- Responsive Flutter Web role selection and student, office-reviewer, and administrator dashboards.
- Flutter API client with in-memory demo tokens and safe loading, empty, validation, unavailable-service, and authorization states.
- Local-only fictional demo-session endpoint plus role-scoped current-user, current-clearance, and assigned-office task reads.
- CORS configuration for the controlled local Flutter origins.
- Four Flutter widget tests and four Laravel integration tests; the full backend suite now passes 32 tests and 134 assertions.
- Labeled real application screenshots for role selection, normal creation, validation, authorization failure, and unavailable-service recovery.

### Student-owned follow-up

- Personalize the reflection, reproduce the checks, rehearse the defense walkthrough, and confirm the instructor's required submission format.

### Future versions

- Week 14 through Week 18 are not implemented in this version. They will be added only through their respective approved weekly work.
