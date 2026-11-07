# PFEBSIT 002 Weeks 13-18 Student Clearance Workflow

This repository is the continuing Project 8 Student Clearance Workflow for PFEBSIT 002 Weeks 13-18. Each completed week is preserved as a named Git tag and GitHub release so its submitted source and evidence remain reproducible while the system advances in later weeks.

## Weekly versions

| Week | Version | Module title | Status |
| --- | --- | --- | --- |
| 13 | `v13.0.0-week13-automation` | Advanced Automation and Scripting | Complete |
| 14 | `v14.0.0-week14-secure-data-exchange` | Secure Data Exchange and Encryption | Not started |
| 15 | `v15.0.0-week15-testing-hardening` | Integration Testing and Security Hardening | Not started |
| 16 | `v16.0.0-week16-performance-deployment` | Performance Reliability and Deployment Evaluation | Not started |
| 17 | `v17.0.0-week17-uat-documentation` | Final Integration UAT Documentation and Evaluation | Not started |
| 18 | `v18.0.0-week18-final-defense` | Final Integration Review Examination and Technical Defense | Not started |

Only Week 13 is implemented in the current repository state. Future week versions will be added only when their work is requested, completed, verified, and approved.

## Current status

Week 13, **Advanced Automation and Scripting**, is technically complete through commit `f8d36cb`. The responsive Flutter Web interface is connected to the bounded Laravel workflow and its SQL-backed records. Students can initiate clearance and resolve deficiencies, assigned office personnel can report deficiencies or approve tasks, and administrators can inspect and retry eligible automation failures. Loading, empty, validation, unavailable-service, unauthorized, deficiency, re-review, completion, and responsive states are preserved in the evidence journey. The local-only demo-session route uses fictional seeded users and is disabled unless the controlled demo environment permits it.

The remaining student-owned work is to personalize the reflection, reproduce the checks, rehearse the defense walkthrough, and confirm the instructor's submission format. No instructor grade, stakeholder approval, production readiness, or completed oral defense is claimed.

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
  screenshots/source-code/    real VS Code captures
  screenshots/application/    real browser captures
  screenshots/setup/          setup proof
  api-tests/                   repeatable request evidence
  logs/                        sanitized execution logs
  test-results/                saved test output
```

## Local setup

1. Start MySQL from XAMPP.
2. Create a controlled local database named `student_clearance_week13`.
3. In `backend`, copy `.env.example` to `.env` and run `php artisan key:generate`.
4. Keep local credentials only in `.env`; never commit that file.
5. Run `composer install`, then `php artisan migrate --seed` to create the schema and fictional demonstration records.
6. Start the API with `php artisan serve --host=127.0.0.1 --port=8000`.
7. In `frontend`, run `flutter pub get`.
8. Start Flutter Web with `flutter run -d chrome --web-port=7357 --dart-define=API_BASE_URL=http://127.0.0.1:8000/api/v1`.

## Verification

```text
cd backend
php artisan test

cd ../frontend
flutter analyze
flutter test
```

The 32 Laravel tests (134 assertions) cover clearance initiation, role-scoped read APIs, office deficiency reporting, student resolution, re-review, approval, completion, validation, authorization, duplicate prevention, reminders, escalation, completion notification, recovered transient failure, exhausted retry, human-action routing, administrator recovery, and the controlled demo-session boundary. Flutter analysis reports no issues and all four widget tests pass.

## Data and safety

Only fictional test data is permitted. No production university system, real student record, live credential, or production endpoint is connected. Logs must use correlation identifiers and exclude passwords, tokens, keys, and unnecessary personal data.

## AI acknowledgment

AI assistance is used for planning, implementation support, documentation, and review. The student must inspect, run, understand, and defend the submitted work under institutional policy.
