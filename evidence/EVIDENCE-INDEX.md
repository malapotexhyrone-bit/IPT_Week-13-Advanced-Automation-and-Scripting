# Week 13 Evidence Index

Every entry must reference a real saved artifact. Labels are stable and are used as captions in the evidence document.

| Label | Artifact | What it proves | Status |
| --- | --- | --- | --- |
| EVID-W13-SETUP-001 | `screenshots/setup/EVID-W13-SETUP-001-vscode-project-structure.png` | Real VS Code view of the saved Week 13 repository structure | complete |
| EVID-W13-SETUP-002 | `test-results/EVID-W13-SETUP-002-baseline-tests.txt` | Executed Laravel and Flutter baseline verification, including retained wrong-directory failure and corrected retest | complete |
| EVID-W13-DATA-001 | `test-results/EVID-W13-DATA-001-clearance-initiation-tests.txt` | Executed success and adverse-path tests plus local MariaDB migration and fictional-data verification | complete |
| EVID-W13-CODE-002 | `screenshots/source-code/EVID-W13-CODE-002-clearance-initiation-vscode.jpg` | Real VS Code view of the transactional clearance-initiation service with duplicate guard and line numbers visible | complete |
| EVID-W13-DATA-002 | `test-results/EVID-W13-DATA-002-office-workflow-tests.txt` | Nine focused workflow tests and full regression result covering deficiency, resolution, office ownership, student ownership, invalid transitions, approval, and completion | complete |
| EVID-W13-CODE-003 | `screenshots/source-code/EVID-W13-CODE-003-office-workflow-vscode.png` | Real VS Code view of the transactional office-task workflow service with filename and line numbers visible | complete |
| EVID-W13-TEST-003 | `screenshots/source-code/EVID-W13-TEST-003-office-workflow-results-vscode.png` | Real VS Code view of the saved focused and regression test results | complete |
| EVID-W13-AUTO-001 | `test-results/EVID-W13-AUTO-001-automation-tests.txt` | Executed reminder, escalation, completion, retry, human-action, authorization, and idempotency tests; full regression 28 tests / 117 assertions | complete |
| EVID-W13-CODE-001 | `screenshots/source-code/EVID-W13-CODE-001-retry-policy-vscode.png` | Real VS Code view of bounded retry, safe failure classification, and permanent-failure routing with filename and line numbers | complete |
| EVID-W13-TEST-004 | `screenshots/source-code/EVID-W13-TEST-004-automation-results-vscode.png` | Real VS Code view of the labeled automation cases and full 28-test / 117-assertion regression result | complete |
| EVID-W13-APP-001 | `screenshots/application/EVID-W13-APP-001-student-normal-path.png` | Real Flutter normal path | pending implementation |
| EVID-W13-APP-002 | `screenshots/application/EVID-W13-APP-002-validation-state.png` | Real Flutter validation state | pending implementation |
| EVID-W13-APP-003 | `screenshots/application/EVID-W13-APP-003-unauthorized-state.png` | Real Flutter unauthorized or forbidden state | pending implementation |
| EVID-W13-FAIL-001 | `logs/EVID-W13-FAIL-001-transient-recovery.jsonl` | Sanitized actual test log: first attempt failed transiently and second attempt delivered with the same correlation ID | complete |
| EVID-W13-FAIL-002 | `logs/EVID-W13-FAIL-002-permanent-human-action.jsonl` | Sanitized actual test log: three bounded failures ended in human action | complete |

Screenshots support but do not replace source, saved test output, logs, expected-versus-actual records, or explanations.

The primary labeled screenshot journey is maintained outside the Git repository at `C:\Users\Admin\Documents\IPT\Evidence Document\Week-13-Screenshot-Journey-and-Evidence.docx`.
