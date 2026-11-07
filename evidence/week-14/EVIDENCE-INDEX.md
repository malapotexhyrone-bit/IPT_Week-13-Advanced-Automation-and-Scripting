# Week 14 Evidence Index

All entries use fictional controlled data. Source screenshots show implementation; test, transport, or application evidence is required for behavior claims. No `.env`, private key, token, plaintext deficiency detail, or live credential is included.

| Evidence ID | Artifact | Requirement/test | What it proves | Status |
| --- | --- | --- | --- | --- |
| EVID-W14-CODE-001 | `screenshots/source-code/EVID-W14-CODE-001-authenticated-encryption-vscode.png` | Authenticated encryption | Real saved `SecureClearanceExchange.php` in VS Code defines AES-256-GCM and constructs fresh metadata/nonce. | complete |
| EVID-W14-TEST-001 | `screenshots/tests/EVID-W14-TEST-001-security-regression-vscode.png` | Focused and regression verification | Real VS Code view of the saved test record and its executed counts. | complete |
| EVID-W14-SEC-001 | `test-results/EVID-W14-SEC-001-backend-security-tests.txt` | Success and adverse cases | Records the focused 12-test/44-assertion result, full 44-test/178-assertion result, formatting, and test boundaries. | complete |
| EVID-W14-HTTPS-001 | `test-results/EVID-W14-HTTPS-001-controlled-transport.txt` | HTTPS and invalid certificate | Records negotiated TLS, successful CA-validated health check, failed untrusted-root request, and controlled exchange receipt. | complete |
| EVID-W14-AUDIT-001 | `test-results/EVID-W14-AUDIT-001-dependency-audit.txt` | Dependency review | Preserves the initial advisory finding, package remediation, final clean audit, and regression result. | complete |
| EVID-W14-SETUP-FAIL-001 | `logs/EVID-W14-SETUP-FAILURES-001.txt` | Failure and recovery | Records the stopped-database and detached-HTTPS startup failures and their bounded recovery. | complete |

## Pending journey evidence

Real Flutter/application captures for password login, role boundaries, secure send success, alteration/replay rejection, invalid input, certificate failure, unavailable service, desktop layout, and narrow layout will be added only when those states actually run. The central `Week-14-Screenshot-Journey-and-Evidence.docx` will be created at the end and will embed selected source images from this repository.
