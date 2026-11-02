# Student Clearance Workflow Week 13

This repository contains the Week 13 version of Project 8 for PFEBSIT 002. It is an individual Student Clearance Workflow prototype focused on reliable automation and scripting.

## Current status

Checkpoint 3 implements the bounded clearance lifecycle through overall completion. The Laravel API authenticates with Sanctum, validates clearance initiation, creates one task per active office, restricts reviews to the assigned office, lets only the owning student resolve a deficiency, blocks invalid transitions, and completes the clearance after every task is approved. Each accepted transition is transactional and correlated. Flutter workflow screens and the Week 13 reminder, escalation, retry, recovery, and notification automation remain pending.

## Approved stack

- Flutter 3.47.8 and Dart 3.13.7 for the browser client
- Laravel 12 on PHP 8.2.12 for the API and automation
- XAMPP MariaDB 10.4.32 using the MySQL driver
- Git with a separate Week 13 repository

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
8. Start Flutter Web with `flutter run -d chrome --web-port=7357`.

## Verification

```text
cd backend
php artisan test

cd ../frontend
flutter analyze
flutter test
```

The implemented tests cover clearance initiation plus office deficiency reporting, student resolution, re-review, approval, completion, validation, wrong-role access, wrong-office access, wrong-student access, duplicate prevention, invalid transitions, and unavailable configuration. Later checkpoints add Flutter integration, notifications, recovered transient failure, and permanent failure routed for human action.

## Data and safety

Only fictional test data is permitted. No production university system, real student record, live credential, or production endpoint is connected. Logs must use correlation identifiers and exclude passwords, tokens, keys, and unnecessary personal data.

## AI acknowledgment

AI assistance is used for planning, implementation support, documentation, and review. The student must inspect, run, understand, and defend the submitted work under institutional policy.
