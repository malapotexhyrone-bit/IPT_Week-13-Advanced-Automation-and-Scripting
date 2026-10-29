# Week 13 Submission Checklist

Status values are `complete`, `pending`, or `blocked`.

| Requirement | Status | Evidence or next action |
| --- | --- | --- |
| Approved Project 8 scope | complete | Student confirmed instructor approval and unique assignment. |
| Separate Week 13 repository | complete | Local repository and verified empty remote are connected. |
| Flutter Web source | pending | Scaffold complete; workflow screens remain. |
| Laravel API source | pending | Clearance initiation is implemented and tested; office review, completion, and automation endpoints remain. |
| MariaDB/MySQL configuration example | complete | `backend/.env.example`; no secret is included. |
| Workflow boundary and non-goals | complete | `docs/PROJECT-SCOPE.md` |
| Trigger and execution decision | complete | `docs/ARCHITECTURE-DECISION-001.md` |
| Activity 1 comparison and interpretation | complete | `docs/ACTIVITY-1-TRIGGER-PATTERNS.md` |
| Flutter screen-flow diagram | complete | Draft in `docs/FLUTTER-SCREEN-FLOW.md`; verify against final UI later. |
| API contract | pending | `POST /api/v1/clearances` is implemented and tested; remaining planned routes are not yet authoritative. |
| Bounded retry and recovery policy | complete | `docs/RETRY-RECOVERY-POLICY.md` |
| Structured sanitized logs | pending | Implement and preserve actual samples. |
| Duplicate-processing protection | pending | Clearance initiation has unique database guards and a duplicate test; automation-effect idempotency remains. |
| Success plus four adverse paths | pending | Initiation has one success and six adverse tests in EVID-W13-DATA-001; the complete workflow still requires adverse-path evidence. |
| Recovered transient failure | pending | Controlled injection and evidence required. |
| Permanent failure for human action | pending | Controlled injection and evidence required. |
| Reminders and stalled-step escalation | pending | Implement scheduler and tests. |
| Completion notification | pending | Implement idempotent event handling. |
| Desktop and narrow Flutter evidence | pending | Capture real application states. |
| Activity 2 failure-injection record | pending | Create after automation implementation. |
| SA7 portfolio and maintainability evaluation | pending | Complete after verified implementation. |
| Reflection and individual contribution | pending | Student review and personal explanation required. |
| Checkpoint commits and pushes | pending | Checkpoint 1 is pushed as `1361028`; checkpoint 2 awaits student review and approval. |
