# Retry and Recovery Policy

## Failure classification

| Class | Examples | Automated response | Final ownership |
| --- | --- | --- | --- |
| Validation or business-rule failure | Missing data, invalid state, duplicate approval | No retry; return or record a safe explicit outcome | Requesting student or office user |
| Authorization failure | Missing session, wrong role, wrong office or owner | No retry; reject and audit the attempt without sensitive detail | Authorized administrator for investigation if needed |
| Transient dependency failure | Temporary notification-adapter unavailability or timeout | Maximum three attempts with increasing backoff and jitter | Automation until exhausted |
| Permanent dependency failure | Repeated controlled failure or non-retryable adapter response | Stop retries; record permanent failure and human-action item | Authorized administrator |
| Internal data-integrity failure | Unique constraint or impossible transition | Roll back, log correlation identifier, and route for review | Authorized administrator and maintainer |

## Bounded policy

- Maximum attempts: 3 total executions.
- Planned backoff for the classroom demonstration: 1 second, then 2 seconds, with small jitter. Production-scale timings are outside scope.
- Each job has a stable idempotency key and a database uniqueness guard for its intended effect.
- A job records attempt number, outcome category, safe error code, correlation identifier, and timestamps.
- Logs exclude message bodies containing unnecessary personal information and all secrets.

## Recovery behavior

- A recovered transient failure produces one intended result and a trace showing failed then successful attempts.
- Manual retry is allowed only for an authorized administrator and only when the failure state is retryable.
- Permanent failure creates a human-action record with an owner, reason code, and safe next step.
- Restarting the queue worker must not create duplicate office tasks, reminders, escalations, or completion notifications.

## Demonstration cases

1. Normal notification succeeds on its first attempt.
2. Controlled transient adapter failure occurs once, then succeeds within the limit.
3. Controlled permanent adapter failure reaches the attempt limit and creates a human-action item.
4. Re-dispatch of the same job is treated as already processed and does not duplicate the effect.

## Recovery runbook

1. Locate the failure using its correlation identifier, not personal data.
2. Confirm the current database state and whether the intended effect already exists.
3. Classify the failure as transient, permanent, business-rule, authorization, or integrity-related.
4. Correct only the controlled dependency or configuration issue.
5. Use the administrator retry action when eligible; never modify queue tables by hand for ordinary recovery.
6. Verify one outcome, inspect structured logs, and run the regression checks.
7. Preserve both the failed record and the successful retest evidence.
