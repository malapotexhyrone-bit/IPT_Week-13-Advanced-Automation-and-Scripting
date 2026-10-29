# Flutter Web Screen Flow

```mermaid
flowchart TD
    A[Role selection for controlled demo] --> B{Authorized role}
    B -->|Student| C[Student clearance dashboard]
    B -->|Office personnel| D[Office task queue]
    B -->|Administrator| E[Automation review]
    B -->|Invalid or missing session| F[Unauthorized state]
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
    T --> U[Failure detail]
    U --> V[Eligible manual retry]
    V --> W[Recovery outcome]
```

Every data surface must include loading, success, empty, validation, unauthorized or forbidden, network or server failure, and recovery feedback. Wide layouts may use navigation rail plus content; narrow layouts collapse to a top app bar and one-column content without removing actions.
