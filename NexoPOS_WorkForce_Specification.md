# NexoPOS WorkForce

> Canonical product and technical specification  
> Last updated: 2026-08-12

## 1. Purpose

WorkForce adds attendance, scheduling, timesheets and payroll-ready labor management to NexoPOS. It is a native extension of NexoPOS, not a separate HR application.

The module must reuse NexoPOS users, roles, permissions, settings, menus and accounting wherever those concepts already exist.

## 2. Final architecture decisions

### 2.1 A User is the staff member

`App\Models\User` is the only staff identity. WorkForce must not create a separate Employee resource that has to be linked to a User.

This gives administrators one workflow:

```text
Create NexoPOS User
        │
        ├── assign Roles / Positions
        ├── grant WorkForce permissions through those roles
        └── user can clock in
```

There is no second employee form, duplicated name/email/phone, optional link, or reconciliation rule.

Staff who do not operate the POS can still be regular NexoPOS users with narrowly scoped permissions. A shared kiosk may authenticate those users by PIN, badge or a future passkey without granting dashboard access.

### 2.2 Roles also serve as positions

NexoPOS Roles are both:

- security groups that grant permissions; and
- operational staff groupings such as Cashier, Cook, Warehouse or Supervisor.

WorkForce documentation calls them **Roles / Positions** where that helps administrators understand the workflow. Core labels must not be globally renamed because system/customer roles exist and a user can hold several roles.

Compensation must not be stored on a Role. Changing access rights must never silently change a person's wage. If compensation is added, it belongs in a private WorkForce payroll profile keyed directly by `user_id`.

### 2.3 No Department or Position resource in the foundation

Departments, managers and separate position records are not needed for explicit clock-in/out. They must not be added pre-emptively.

Scheduling may later reference a required `role_id`. A future organization feature may be introduced only when its reporting or scheduling value justifies its UX and maintenance cost.

### 2.4 Explicit punches are authoritative

Clock-in, break start, break end, clock-out and approved corrections are the attendance evidence.

Mouse movement, touchscreen interaction, sales, register events and page visibility are not proof that a person is working or absent.

### 2.5 POS inactivity belongs to NsPinLogin

NsPinLogin may lock a browser after inactivity for security. Locking or unlocking must never:

- start or end a WorkForce session;
- start or end a break;
- change presence, worked or payable time; or
- mark a staff member absent.

The obsolete core Cashier Idle Counter setting is not a WorkForce dependency.

### 2.6 MultiStore is deferred

The first release is single-store/global. Do not add speculative store-assignment tables or scoping. MultiStore integration will be designed as a separate phase.

## 3. Product scope

### 3.1 Foundation release: implemented

- Existing NexoPOS User as staff identity.
- Existing NexoPOS Roles as position/access groups.
- Personal clock page.
- Explicit clock-in and clock-out.
- Explicit unpaid break start and end.
- One canonical work session per attendance interval.
- Append-only punch evidence.
- One-open-session and one-open-break database invariants.
- Request idempotency.
- Maximum-session review threshold.
- NexoPOS permissions, menus and settings integration.
- UTC timestamp storage and application-timezone snapshot.

### 3.2 Planned phases

1. Manager session list, corrections and immutable audit trail.
2. Shift templates, scheduling and attendance exceptions.
3. Timesheet submission, approval and period locking.
4. Private compensation profiles and payroll-ready calculations.
5. Payroll preview/export.
6. Generic NexoPOS accounting journal support and payroll posting.
7. Shared kiosk and optional badge/passkey authentication.
8. MultiStore assignment, scoping and reporting.

### 3.3 Out of scope initially

- Statutory payroll for every jurisdiction.
- Device-surveillance productivity scoring.
- Automatic clock-out from inactivity or register close.
- Raw biometric storage.
- A second accounting ledger.

## 4. User experiences

### 4.1 Administrator

1. Create or edit the person under the normal NexoPOS Users resource.
2. Keep the User active while the person is allowed to sign in and clock.
3. Assign one or more Roles / Positions.
4. Grant self-clock and self-session permissions through those roles.
5. Configure WorkForce under the normal Settings page using `manage.options`.

No WorkForce employee record is created.

Users with attendance history should be deactivated, not deleted. WorkForce prevents deletion so historical sessions remain attributable.

### 4.2 Staff

1. Sign in using the normal NexoPOS authentication flow.
2. Open WorkForce → Time Clock.
3. Clock in.
4. Start and end breaks explicitly.
5. Clock out.
6. Review recent personal sessions.

### 4.3 Manager: planned

- See who is currently working or on break.
- Review missing or unusually long sessions.
- Correct a punch with a reason.
- Approve and lock timesheets.
- See only wage data allowed by dedicated privacy permissions.

### 4.4 Shared kiosk: planned

A kiosk identifies an existing NexoPOS User by a scoped method such as user selection plus PIN, badge, NFC or passkey. It does not create shadow employees.

## 5. Attendance model

### 5.1 Canonical state flow

```text
clocked out ── Clock In ──► working
                              │
                         Start Break
                              │
                              ▼
                            break
                              │
                          End Break
                              │
                              ▼
                           working ── Clock Out ──► closed / needs review
```

### 5.2 Measurements

| Metric | Definition |
| --- | --- |
| Presence | Clock-out instant minus clock-in instant. |
| Break | Explicit recorded break duration. |
| Worked | Presence minus unpaid breaks. |
| Payable | Approved worked time after later policy calculations. |
| Overtime | Later policy-derived portion of payable time. |

All calculations use integer seconds. Money calculations must later use exact decimals or minor units, never floating point.

### 5.3 Invariants

- A User can have at most one open Work Session.
- A Work Session can have at most one open break.
- A break must end before clock-out.
- The User must be active to start a new session.
- Every state-changing request has a unique idempotency key.
- A reused key returns the original result only when user and action match.
- Historical punches are never silently rewritten.
- Closed/approved/locked periods require audited corrections or reversals.

### 5.4 Time handling

- Persist event instants in UTC.
- Snapshot the relevant timezone on the Work Session.
- Apply schedule/pay policy in the store or policy timezone.
- Test daylight-saving gaps and repeated hours before scheduling/payroll release.

## 6. Data model

### 6.1 Foundation tables

#### `workforce_work_sessions`

| Field | Purpose |
| --- | --- |
| `user_id` | Required NexoPOS User who worked. |
| `open_user_id` | Nullable unique sentinel enforcing one open session per User. |
| `status` | `working`, `break`, `closed`, `needs_review`, `approved`, `locked`. |
| `started_at`, `ended_at` | UTC attendance boundary instants. |
| duration fields | Presence, break, worked, payable and overtime seconds. |
| `timezone` | Timezone snapshot. |
| `source` | Personal, kiosk, manager or POS entry point. |
| actor fields | User who opened/closed the session. |

`user_id` and `open_user_id` use unsigned integers because that matches `nexopos_users.id`.

#### `workforce_breaks`

Stores break boundaries, duration and paid/unpaid status. `open_session_id` is nullable and unique to enforce one open break.

#### `workforce_punches`

Stores append-only action evidence with `user_id`, session, action, time, source, actor, metadata and unique idempotency key.

### 6.2 Planned tables

Add only when their phase begins:

- `workforce_shift_templates`
- `workforce_shifts`
- `workforce_timesheets`
- `workforce_timesheet_entries`
- `workforce_adjustments`
- `workforce_leave_requests`
- `workforce_pay_profiles`
- `workforce_payroll_runs`
- `workforce_payroll_entries`
- `workforce_payroll_accounting_links`

Every table that identifies staff references `nexopos_users.id` directly.

## 7. Permissions

### 7.1 Foundation permissions

| Permission | Scope |
| --- | --- |
| `workforce.attendance.clock.self` | Personal attendance actions. |
| `workforce.sessions.read.self` | Personal status and recent sessions. |
| `workforce.attendance.clock.others` | Reserved manager-assisted clocking. |
| `workforce.sessions.read.team` | Reserved manager/team visibility. |
| `workforce.sessions.adjust` | Reserved audited corrections. |

WorkForce settings use the existing `manage.options` permission.

### 7.2 Planned sensitive permissions

Wage visibility, compensation edits, payroll calculation, payroll approval, accounting posting and payroll reversal must be separated. A general attendance manager must not automatically see wages.

Authorization is required on every endpoint; hidden menus are not a security boundary.

## 8. Settings

### 8.1 Foundation

- Maximum session hours: 1–48, default 16.

Exceeding the threshold produces `needs_review`; it never causes an automatic clock-out.

### 8.2 Planned

- Week start and pay-period cadence.
- Break policy.
- Overtime policy.
- Rounding rules applied only to approved totals, never raw punches.
- Schedule tolerance and exception thresholds.
- Timesheet approval/locking rules.
- Payroll account mappings.

## 9. Scheduling and timesheets

Scheduling is future scope. A Shift references `user_id` and may reference a required core `role_id`. It records planned start/end, timezone and optional location/context.

Attendance exceptions should be deterministic:

- missing clock-in or clock-out;
- late arrival or early departure;
- unscheduled work;
- overlapping sessions;
- unusually long session;
- overtime threshold exceeded.

Timesheet flow:

```text
Work Sessions
      │
      ▼
Calculated totals and exceptions
      │
      ▼
Manager review / adjustment
      │
      ▼
Approved and locked period
      │
      ▼
Payroll-ready calculation
```

Every correction records original values, replacement values, reason, actor and timestamp.

## 10. Payroll boundary

WorkForce may eventually own:

- compensation snapshots;
- regular/overtime/break calculations;
- additions and deductions;
- employer contributions;
- payroll-run approval;
- employee-level payroll detail;
- payroll preview/export.

It must not attempt to implement every jurisdiction's tax law in the foundation.

Approved payroll entries are immutable snapshots. Later changes require a new calculation or explicit reversal, not mutation of history.

## 11. Accounting integration strategy

### 11.1 Boundary

**WorkForce calculates payroll; NexoPOS Accounting records its financial consequences.**

WorkForce must not create another chart of accounts or ledger.

### 11.2 Why the current transaction service is insufficient

Production payroll may require several balanced lines:

- debit wage expense;
- debit employer tax/benefit expense;
- credit net payroll payable;
- credit tax/deduction/benefit payables;
- later debit payables and credit bank/cash when paid.

The current service is oriented around a transaction plus a generated reflection. It does not provide a request-independent, atomic, idempotent multi-line journal with an immutable exact reversal. Using it directly risks partial entries, duplicate posting and incorrect reporting.

### 11.3 Justified generic Core enhancement

Before enabling payroll posting, Core should add a generic accounting journal contract and service. This is not WorkForce-specific; tax, inventory and other modules can use it.

The contract should provide:

- a journal header with UUID, purpose, posting date and author;
- `source_type`, `source_id` and unique idempotency key;
- two or more exact-decimal debit/credit lines;
- validation that total debit equals total credit;
- account validity/category checks;
- one database transaction for header and all lines;
- no dependency on the current HTTP request or implicit authenticated user;
- immutable posted journals;
- exact inverse journal linked by `reversal_of_id`;
- one allowed reversal per journal;
- after-commit report refresh/events.

Journal-linked lines must bypass the legacy automatic reflection listener because the caller already supplies every balanced line.

### 11.4 Reporting compatibility

For new journal entries:

- expense-account debits affect expense reporting;
- revenue-account credits affect income reporting;
- asset, liability and equity movements affect neither income nor expense totals;
- the general ledger/account summary includes every line;
- reports use posting date, not insertion time, for backdated payroll.

Legacy non-journal behavior should remain compatible.

### 11.5 WorkForce posting adapter

WorkForce calls the generic service through a module adapter. The module stores returned journal IDs in a unique link table and uses stable keys such as:

```text
workforce:payroll-run:{uuid}:accrual
workforce:payroll-run:{uuid}:payment
```

Aggregate posting per payroll run/account is the default. Employee detail remains in WorkForce. Until the generic service exists, WorkForce can preview and export payroll-ready totals but must not silently post them.

## 12. Core versus module changes

### 12.1 Module-owned

- Attendance tables and services.
- WorkForce permissions.
- Time Clock UI and API.
- WorkForce settings registration.
- UserCrud help text explaining Roles / Positions.
- User deletion protection when attendance exists.
- Future schedules, timesheets, payroll detail and accounting link records.

### 12.2 Core changes allowed only when generic

- The accounting journal contract described above.
- A reusable extension point only if a future high-stakes module field cannot be saved atomically through existing mechanisms.

No WorkForce-specific attendance fields or business rules belong in Core.

## 13. Security, privacy and deletion

- Use existing NexoPOS permissions and middleware.
- Validate server-side even when UI controls are hidden.
- Keep wage permissions separate from attendance permissions.
- Use restrictive foreign keys for attendance history; never cascade-delete it.
- Block deletion of a User with WorkForce history and instruct administrators to deactivate the account.
- Do not expose private compensation in general staff endpoints.
- Do not store raw biometric data. Future biometric login must use standards such as WebAuthn/passkeys.
- Throttle kiosk/PIN authentication independently from attendance actions.

## 14. Concurrency and correctness

- Lock the User when opening a session.
- Enforce the open-session invariant with a unique database key.
- Lock the current session while changing its state.
- Enforce idempotency in the database.
- Reject a key reused for a different user or action.
- Use transactions for every multi-record attendance change.
- Use exact decimal/minor-unit money and currency snapshots for payroll.
- Lock approved periods against edits.

## 15. Required tests

### Foundation

- A normal active NexoPOS User clocks without an Employee record.
- An inactive User cannot clock in.
- Unauthorized API calls are denied.
- A User cannot open two sessions.
- A session cannot have two open breaks.
- Clock-out fails during an open break.
- Repeated idempotent requests return one result.
- Reusing a key for another action/user fails.
- Presence, break, worked and payable totals are exact.
- UTC and timezone snapshots are correct.
- A User with attendance history cannot be deleted.
- Role form help describes Roles / Positions.

### Future scheduling/payroll

- Overlap and exception rules.
- Locked timesheet immutability.
- Permission denial for wage/payroll actions.
- Exact decimal calculations and snapshots.
- Balanced multi-line accounting posting.
- Transaction rollback on any line failure.
- Concurrent duplicate posting returns one journal.
- Liability credits do not inflate income.
- Exact reversal and refusal of a second reversal.

## 16. Acceptance criteria

The foundation is correct when:

1. An administrator creates staff only once, as a NexoPOS User.
2. Assigning core Roles / Positions controls WorkForce access.
3. No Employee, Department or Position resource exists in WorkForce.
4. An authorized active User can clock in, break and clock out.
5. Attendance is independent from POS inactivity and NsPinLogin locking.
6. Concurrency and retries cannot create duplicate open sessions or punches.
7. Historical attendance cannot be lost by deleting a User.
8. Settings use `manage.options`.
9. MultiStore remains explicitly deferred.
10. Payroll cannot post until the generic accounting journal contract exists.

## 17. Summary

WorkForce extends the NexoPOS User rather than duplicating it. Core Roles provide both position naming and access control, explicit punches create canonical attendance, and NsPinLogin inactivity locking remains a separate security concern. Future payroll stays employee-detailed inside WorkForce while financial posting uses a generic, atomic and idempotent NexoPOS accounting journal service.
