# Flutter Web Screen Flow

```mermaid
flowchart TD
    A[HTTPS password login] --> B{Laravel verifies password and server role}
    B -->|Student| C[Student clearance dashboard]
    B -->|Office personnel| D[Assigned office task queue]
    B -->|Administrator| E[Security and automation review]
    B -->|Invalid credentials| F[Generic invalid-credentials state]
    B -->|Repeated failures| X[Temporary rate-limit state]
    C --> G[Initiate clearance form]
    G --> H{Client validation}
    H -->|Invalid| I[Inline validation state]
    H -->|Valid| J[Loading state]
    J -->|Created| K[Clearance detail and office timeline]
    J -->|API or network failure| L[Failure state with safe retry]
    K --> M[Deficiency detail]
    M --> N[Submit resolution]
    N --> K
    D --> O{Assigned tasks}
    O -->|None| P[Empty state]
    O -->|Available| Q[Office task detail]
    Q --> R[Record deficiency]
    Q --> S[Approve task]
    E --> T[Failed and stalled automation list]
    E --> Y[Protected clearance exchange]
    Y --> Z[Administrator enters fictional clearance ID]
    Z --> AA[Laravel minimizes and encrypts server-owned record]
    AA --> AB[Validated HTTPS receiver outcome]
    T --> U[Failure detail]
    U --> V[Eligible manual retry]
    V --> W[Recovery outcome]
```

The browser never selects a trusted role. It submits credentials over the controlled HTTPS endpoint and renders the dashboard identified by Laravel. Tokens remain in memory and are revoked on logout. Every data surface must include loading, success, empty, validation, unauthorized or forbidden, network or server failure, and recovery feedback. Wide layouts use a two-column login composition; narrow layouts collapse to one scrollable column without removing fields or actions.
