# PFEBSIT 002 Weeks 13-18 Student Clearance Workflow

This repository is the continuing Project 8 Student Clearance Workflow for PFEBSIT 002 Weeks 13-18. Each completed week is preserved as a named Git tag and GitHub release so its submitted source and evidence remain reproducible while the system advances in later weeks.

## Weekly versions

| Week | Version | Module title | Status |
| --- | --- | --- | --- |
| 13 | `v13.0.0-week13-automation` | Advanced Automation and Scripting | Complete |
| 14 | `v14.0.0-week14-secure-integration` | Secure Data Exchange and Encryption | In progress |
| 15 | `v15.0.0-week15-testing-hardening` | Integration Testing and Security Hardening | Not started |
| 16 | `v16.0.0-week16-performance-deployment` | Performance Reliability and Deployment Evaluation | Not started |
| 17 | `v17.0.0-week17-uat-documentation` | Final Integration UAT Documentation and Evaluation | Not started |
| 18 | `v18.0.0-week18-final-defense` | Final Integration Review Examination and Technical Defense | Not started |

Week 13 remains preserved at `v13.0.0-week13-automation`. Week 14 is being developed on `codex/week14-secure-integration`; it will not receive its final tag or release until every required checkpoint is approved and the complete week is verified.

## Current status

Week 14, **Secure Data Exchange and Encryption**, is in progress. The first implementation checkpoint adds an administrator-only sender and a controlled receiver that exchange minimized clearance data over certificate-validated HTTPS. AES-256-GCM protects the payload, HMAC-SHA-256 authenticates the calling service, short validity windows reject stale traffic, and a unique receipt prevents replay. The implementation records hashes and metadata rather than plaintext or ciphertext.

The Week 14 Flutter authentication and role-based interface, full activities, SA8, IA5, report, remaining evidence journey, reflection, and student defense rehearsal are still pending. No instructor grade, official registry inspection, stakeholder approval, production readiness, or completed oral defense is claimed.

## Approved stack

- Flutter 3.47.8 and Dart 3.13.7 for the browser client
- Laravel 12 on PHP 8.2.12 for the API and automation
- XAMPP MariaDB 10.4.32 using the MySQL driver
- Git tags and GitHub releases preserving one verified version for each completed week

Laravel 12 was selected because it supports PHP 8.2. Laravel 13 requires PHP 8.3 and is therefore not used in this environment.

## Repository layout

```text
backend/                       Laravel API and automation
frontend/                      Flutter Web client
docs/                          decisions, activities, and weekly report
evidence/
  week-14/
    screenshots/source-code/  real VS Code captures
    screenshots/application/  real browser captures
    screenshots/setup/        setup proof
    screenshots/tests/        real verification captures
    api-tests/                 repeatable request evidence
    logs/                      sanitized execution logs
    test-results/              saved test output
```

## Local setup

1. Start MySQL from XAMPP.
2. Create a controlled local database named `student_clearance_workflow`.
3. In `backend`, copy `.env.example` to `.env` and run `php artisan key:generate`.
4. Keep local credentials only in `.env`; never commit that file.
5. Run `composer install`, then `php artisan migrate --seed` to create the schema and fictional demonstration records.
6. Generate the controlled localhost certificate and ignored secrets by following `docs/WEEK14-SECURE-EXCHANGE-DESIGN.md` and the scripts under `tools/`.
7. Start the certificate-validated HTTPS endpoint on `https://localhost:8443` with the Week 14 local HTTPS script.
8. Run the sender/receiver demonstration only with fictional seeded data.
9. In `frontend`, run `flutter pub get`. The Week 14 Flutter HTTPS command will be documented when its integration checkpoint is implemented and verified.

## Verification

```text
cd backend
php artisan test

cd ../frontend
flutter analyze
flutter test
```

The current backend regression result is 44 passing tests with 178 assertions. The focused secure-exchange suite passes 12 tests with 44 assertions, including accepted exchange, alteration, wrong key, invalid HMAC, expired request, expired message, replay, unauthenticated and forbidden sender calls, unavailable key, malformed envelope, and missing CA trust. Laravel Pint passes and Composer reports no known security advisories after the four affected Symfony packages were updated from 7.4.22 to 7.4.23. Flutter Week 14 verification is pending its implementation checkpoint.

## Data and safety

Only fictional test data is permitted. No production university system, real student record, live credential, or production endpoint is connected. Logs must use correlation identifiers and exclude passwords, tokens, keys, and unnecessary personal data.

## AI acknowledgment

AI assistance is used for planning, implementation support, documentation, and review. The student must inspect, run, understand, and defend the submitted work under institutional policy.
