# Week 13 Submission Checklist

Status values are `complete`, `pending`, or `blocked`.

| Requirement | Status | Evidence or next action |
| --- | --- | --- |
| Approved Project 8 scope | complete | Student confirmed instructor approval and unique assignment. |
| Separate Week 13 repository | complete | Local repository and verified empty remote are connected. |
| Flutter Web source | complete | Responsive role selection and student, office, and administrator workflow screens call the Laravel API. |
| Laravel API source | complete | Clearance workflow plus administrator automation inspection/retry endpoints are implemented and tested. |
| MariaDB/MySQL configuration example | complete | `backend/.env.example`; no secret is included. |
| Workflow boundary and non-goals | complete | `docs/PROJECT-SCOPE.md` |
| Trigger and execution decision | complete | `docs/ARCHITECTURE-DECISION-001.md` |
| Activity 1 comparison and interpretation | complete | `docs/ACTIVITY-1-TRIGGER-PATTERNS.md` |
| Flutter screen-flow diagram | complete | `docs/FLUTTER-SCREEN-FLOW.md` matches the implemented role-based UI. |
| API contract | complete | `docs/API-CONTRACT.md` matches implemented workflow, read, controlled-demo, and administrator automation routes. |
| Bounded retry and recovery policy | complete | `docs/RETRY-RECOVERY-POLICY.md` |
| Structured sanitized logs | complete | `EVID-W13-FAIL-001` and `EVID-W13-FAIL-002` contain safe actual test outcomes and correlation IDs. |
| Duplicate-processing protection | complete | Database unique keys plus executed duplicate-processing tests cover clearance and automation effects. |
| Success plus four adverse paths | complete | Normal creation plus invalid, unauthenticated, forbidden, duplicate, unavailable-office, invalid-transition, and controlled dependency-failure cases are recorded in the labeled test and UI evidence. |
| Recovered transient failure | complete | `EVID-W13-FAIL-001`; recovered on attempt 2 without duplicate effect. |
| Permanent failure for human action | complete | `EVID-W13-FAIL-002`; stopped at attempt 3 and created administrator work. |
| Reminders and stalled-step escalation | complete | Scheduler, queued job, database outcomes, and feature tests. |
| Completion notification | complete | One idempotent record after completion, verified by feature test. |
| Desktop and narrow Flutter evidence | complete | EVID-W13-APP-001 through APP-007 include real desktop workflow states and a 502-pixel-wide completed view. |
| Activity 2 failure-injection record | complete | `docs/ACTIVITY-2-CONTROLLED-FAILURE.md` plus labeled logs and tests. |
| SA7 portfolio and maintainability evaluation | complete | `docs/WEEK13-REPORT.md` maps the automation case, laboratory evidence, maintainability evaluation, limitations, and references to the verified build. |
| Chronological screenshot evidence document | complete | `C:\Users\Admin\Documents\IPT\Evidence Document\Week-13-Screenshot-Journey-and-Evidence.docx` contains 19 visually verified pages with labeled VS Code, Flutter, test, recovery, and Git-push evidence. The central file is intentionally outside this repository. |
| Defense walkthrough | complete | `docs/DEFENSE-WALKTHROUGH.md` provides a bounded live-demonstration flow, likely defense questions, and a student verification checklist. |
| Reflection and individual contribution | pending | The report contains a reflection worksheet, but the student must personalize it and verify the explanation in their own words. |
| Checkpoint commits and pushes | complete | Checkpoints 1 (`1361028`), 2 (`10c9a88`), 3 (`aceb8c6`), 4 (`f9c1fc4`), 5 (`a31c831`), and 6 (`34d1a58`) are pushed. The student approved the final documentation checkpoint represented by the current repository HEAD for push to `origin/main`. |
