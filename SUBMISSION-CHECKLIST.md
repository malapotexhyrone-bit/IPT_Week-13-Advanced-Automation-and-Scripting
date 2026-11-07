# Week 14 Submission Checklist

Status values are `complete`, `pending`, or `blocked`.

| Requirement | Status | Evidence or next action |
| --- | --- | --- |
| Continuing Weeks 13-18 repository | complete | Week 13 history and tag are preserved; Week 14 work is isolated on `week14-secure-integration`. |
| Project 8 approval and registration | complete | The student confirmed instructor approval and registration on 2026-11-07; the official registry itself was not inspected. |
| Data classification and minimization | complete | `docs/WEEK14-SECURE-EXCHANGE-DESIGN.md` and `SecureClearanceExchange.php` define included and excluded fields. |
| Administrator-only sender | complete | Sanctum and administrator-role middleware protect the send endpoint; unauthenticated and forbidden tests pass. |
| Controlled sender/receiver integration | complete | Laravel sender and receiver exchange the minimized payload and create one safe receipt. |
| HTTPS with certificate and hostname validation | complete | Controlled localhost TLS 1.3 verification succeeded with the configured CA; the untrusted-certificate request failed as expected. |
| Authenticated encryption | complete | AES-256-GCM uses a random 96-bit nonce, 128-bit tag, and authenticated metadata. |
| Calling-service authentication | complete | HMAC-SHA-256 covers the request timestamp and exact JSON body before decryption. |
| Keys outside source control | complete | Fresh values are stored only in ignored `.env` and `backend/storage/https`; tracked files contain placeholders and scripts. |
| Alteration, wrong-key, expiry, and replay rejection | complete | Focused security suite passes 12 tests / 44 assertions. |
| Unavailable-key, malformed-input, and missing-CA cases | complete | Controlled 503/422/503 outcomes are covered without creating receipts. |
| Full backend regression | complete | 47 tests / 201 assertions pass after the authentication work and security dependency update. |
| Dependency audit and remediation | complete | Initial seven Symfony advisories were preserved as evidence; affected packages were updated to 7.4.23 and the final audit reports none. |
| Safe logs and receipts | complete | Receipts store metadata and a SHA-256 hash only; secret scan found no live key or credential in tracked candidates. |
| Password authentication | complete | Laravel hashing, generic credential errors, throttling, two-hour Sanctum token, and logout revocation pass five tests / 31 assertions. |
| Least-privilege Flutter role flows | complete | Flutter renders the student, assigned Library-office, or administrator dashboard only from the authenticated server response; six widget tests and real browser captures verify the three role-bound views. |
| Success plus at least four adverse paths in build evidence | complete | Real browser captures preserve accepted exchange and invalid-credential behavior; automated evidence covers alteration, wrong key, invalid HMAC, expiry, replay, unavailable key, malformed input, missing CA, and authorization rejection. |
| Activity 1: review five cryptographic uses | complete | The main report corrects five proposed uses, compares alternatives, states assumptions, includes an adverse case, and preserves a labeled one-page interpretation. |
| Activity 2: secure-exchange review | complete | The main report covers source, configuration, HTTPS, tamper, secret, dependency, browser, and residual-risk review with a labeled one-page interpretation. |
| SA8 Secure Integration Architecture Case | complete | The main report includes the threat summary, data flow, control rationale, alternatives, consequences, decision record, acceptance outcomes, and limitations. |
| IA5 Secure Data Exchange Build | complete | The main report maps the working build, code excerpts, protected configuration, tests, failures, evidence, and reproduction steps. |
| Main Week 14 report | complete | `docs/WEEK14-SECURE-INTEGRATION-REPORT.md` consolidates the activities, SA8, IA5, journey, limitations, maintenance, and defense preparation. |
| Central evidence DOCX | pending | Create `Evidence Document/Week-14-Screenshot-Journey-and-Evidence.docx` only at the end, then render and inspect every page. |
| Student reflection and individual contribution | pending | An evidence-backed worksheet is prepared, but the student must personalize and verify the answers in their own words. |
| Checkpoint 1 commit and push | complete | Approved and preserved as `7cf96482235715d81f6b99dea45d21b44cf6fe98` on `week14-secure-integration`. |
| Checkpoint 2 commit and push | complete | Approved and pushed as `5f7bbc030578bd557b94ee342bd65f7a23be54eb` on `week14-secure-integration`. |
| Checkpoint 3 documentation commit and push | complete | Approved by the student on 2026-11-08 and included in the current documentation checkpoint; the resulting hash and remote status are recorded in the checkpoint handover. |
| Final tag and GitHub release | pending | Create `v14.0.0-week14-secure-integration` only after every required Week 14 checkpoint is complete and approved. |
