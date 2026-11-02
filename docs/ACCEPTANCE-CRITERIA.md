# Week 13 Acceptance Criteria

| Case | Preconditions | Expected measurable result | Evidence |
| --- | --- | --- | --- |
| Normal initiation | Authenticated student, active term and four active offices | HTTP 201; one clearance, four office tasks and one correlated event | EVID-W13-DATA-001 and APP-001 |
| Invalid input | Missing or malformed term code | HTTP 422; zero partial clearance or office-task records | EVID-W13-DATA-001 and APP-002 |
| Unauthenticated action | Missing or revoked token | HTTP 401; no protected data or state change | EVID-W13-DATA-001 and APP-003 |
| Forbidden action | Wrong role, wrong office or wrong student | HTTP 403; no workflow state change | EVID-W13-DATA-002 and AUTO-001 |
| Duplicate processing | Repeated initiation or delivered automation record | One intended record or effect; duplicate adds no task, event or attempt | EVID-W13-DATA-001 and AUTO-001 |
| Dependency unavailable | Controlled adapter or local API failure | Safe visible error; clearance data remains consistent; bounded retry or human action | EVID-W13-FAIL-001 through FAIL-003 |
| Recovery | First gateway attempt fails transiently | Attempt 2 delivers with the same correlation ID and no duplicate effect | EVID-W13-FAIL-001 |
| Permanent failure | Controlled adapter fails through attempt 3 | Processing stops and creates exactly one administrator human-action item | EVID-W13-FAIL-002 |
| Completion | All four office tasks approved | Parent clearance becomes completed once and one completion delivery is recorded | EVID-W13-E2E-001 and APP-007 |
| Responsive layout | Completed workflow at desktop, tablet breakpoint and 502-pixel narrow width | Core actions remain readable and no horizontal overflow occurs | Flutter breakpoint source and APP-004; tablet remains a student recheck item |
