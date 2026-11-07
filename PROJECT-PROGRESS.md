# Project Progress

## Active work

- Week: 14 — Secure Data Exchange and Encryption
- Project: Project 8, Student Clearance Workflow
- Repository: `https://github.com/malapotexhyrone-bit/IPT_Weeks-13-18_Student-Clearance-Workflow`
- Local folder: `C:\Users\Admin\Documents\IPT\IPT_Weeks-13-18_Student-Clearance-Workflow`
- Branch: `codex/week14-secure-integration`
- Week 13 baseline: commit `6a73790b433ec65d34189aa3e3d7add80280b761`, tag `v13.0.0-week13-automation`
- Checkpoint status: checkpoint 1 implemented and verified locally; not staged, committed, or pushed pending student approval

## Checkpoint 1 completed locally

- Added minimized AES-256-GCM clearance envelopes and a controlled receiver.
- Added HMAC service authentication, freshness checks, expiry checks, and replay prevention.
- Added administrator authorization to the sender and safe receipt storage.
- Added controlled localhost HTTPS scripts, ignored secret/certificate paths, and safe configuration examples.
- Ran a real TLS 1.3 sender/receiver demonstration with fictional data.
- Expanded secure-exchange coverage to 12 tests / 44 assertions.
- Passed the complete backend suite: 44 tests / 178 assertions.
- Updated affected Symfony packages to 7.4.23 after an initial seven-advisory audit; the final audit reports no known advisories.
- Preserved real setup failures and recovery rather than converting them into successful first-attempt claims.

## Evidence locations

- Week 14 index: `evidence/week-14/EVIDENCE-INDEX.md`
- Test and transport records: `evidence/week-14/test-results/`
- Setup/recovery record: `evidence/week-14/logs/`
- Real VS Code captures: `evidence/week-14/screenshots/source-code/` and `evidence/week-14/screenshots/tests/`

## Pending milestones

1. Obtain checkpoint 1 approval, then stage the listed files, commit, and push the feature branch.
2. Add password authentication and least-privilege Week 14 Flutter role flows.
3. Capture real success, alteration, replay, certificate, invalid-input, authorization, and unavailable-service journey evidence.
4. Complete Activity 1, Activity 2, SA8, IA5, the main report, reflection worksheet, and defense walkthrough.
5. Create the central Week 14 DOCX only after the full journey is complete; render and visually inspect every page.
6. After final approval, merge/push the approved Week 14 result and create tag `v14.0.0-week14-secure-integration` and its GitHub release.

## Known constraints and honest gaps

- The student reports assignment to Project 8, but the official instructor registry has not been inspected.
- The receiver is a controlled localhost integration, not a production university service.
- Browser trust and Flutter Week 14 HTTPS behavior are not yet implemented or evidenced.
- The Week 13 tag exists, but no GitHub release was found during the Week 14 starting-state inspection; no release was created without separate approval.
- No final Week 14 submission or defense readiness is claimed.
