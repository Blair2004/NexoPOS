# NsAppointments Rebuild Plan

## Purpose

`NsAppointments` is a NexoPOS module for service businesses such as spas, barbers, salons, clinics, and similar appointment-driven operations. The module should let a business sell bookable services through a public frontend while keeping appointments, customers, orders, payments, staff schedules, permissions, and reporting aligned with NexoPOS conventions.

This document is the working implementation tracker. Update the status tables as each slice is designed, built, tested, and reviewed.

## Module Identity

- Namespace: `NsAppointments`
- Display name: `Appointments`
- Suggested module path: `modules/NsAppointments`
- PHP namespace root: `Modules\NsAppointments`
- View namespace: `NsAppointments`
- Translation namespace: `NsAppointments`
- Route name prefix: `ns-appointments`
- Permission prefix: `ns.appointments`
- Table prefix: `nexopos_appointment_`

## Product Scope

The module should provide a complete appointment commerce flow:

- Public service discovery.
- Single service booking.
- Cart and checkout.
- Optional full payment, partial payment, or no payment.
- Appointment confirmation.
- Admin calendar and appointment management.
- Staff, working hours, time off, and service assignment.
- NexoPOS product, customer, order, permission, and payment integration.

## Payment Modes

Each service and checkout must support one of these payment requirements:

| Mode | Customer pays online | Appointment after checkout | Notes |
| --- | --- | --- | --- |
| No payment | Nothing | `pending_confirmation` or `confirmed` | Controlled by settings. |
| Partial payment | Deposit amount | `confirmed` after deposit succeeds | Remaining balance is due later. |
| Full payment | Full order amount | `confirmed` after payment succeeds | Best for prepaid services. |

Suggested fields:

- `payment_requirement`: `none`, `partial`, `full`
- `required_payment`: decimal amount required now
- `paid_amount`: decimal amount paid
- `payment_status`: `unpaid`, `partially_paid`, `paid`, `refunded`
- `order_id`: related NexoPOS order when checkout creates an order

## Appointment Statuses

Suggested lifecycle:

| Status | Meaning |
| --- | --- |
| `draft` | Internal/incomplete appointment record. |
| `pending_payment` | Awaiting required payment. |
| `pending_confirmation` | Booked, but staff/admin must confirm. |
| `confirmed` | Appointment is scheduled and blocks availability. |
| `checked_in` | Customer arrived. |
| `completed` | Service was completed. |
| `cancelled` | Appointment was cancelled. |
| `no_show` | Customer did not attend. |
| `expired` | Checkout/payment hold expired. |

Blocking statuses for availability should be explicit and tested. A likely default is `pending_payment`, `pending_confirmation`, `confirmed`, and `checked_in`, depending on hold behavior.

## Public Frontend

The public frontend should be a module-local Vue application loaded through `@moduleViteAssets`.

| Page | Purpose | Status |
| --- | --- | --- |
| Home | Present business, categories, featured services, and entry points. | Not started |
| Services listing | Browse and filter bookable services. | Not started |
| Single service | Show service details, staff options, availability, and add-to-cart. | Not started |
| Cart | Review selected services, staff, resources, slots, totals, and conflicts. | Not started |
| Checkout | Capture customer details, notes, payment mode, and policy acceptance. | Not started |
| Payment | Complete required payment when mode is partial or full. | Not started |
| Confirmation | Show booking reference, appointment details, and payment status. | Not started |

Frontend requirements:

- No raw `innerHTML` UI for core booking flows.
- Use NexoPOS frontend globals and `nsHttpClient` conventions.
- Use module localization for user-facing strings.
- Provide loading, empty, validation, and error states.
- Revalidate availability before checkout.
- Keep route paths stable and shareable.

## Admin Experience

| Feature | Purpose | Status |
| --- | --- | --- |
| Appointment calendar | Day/week/month/resource calendar for staff operations. | Implemented |
| Appointment list | Search, filter, and inspect bookings. | Implemented |
| Manual booking | Admin creates appointments for walk-ins or phone calls. | Implemented |
| Reschedule/cancel | Operational appointment changes. | In progress |
| Staff management | Link staff to NexoPOS users and manage active/available/unavailable/busy state. | Tested |
| Working hours | Weekly staff availability. | Not started |
| Time off | Vacations, breaks, holidays, and unavailable periods. | Not started |
| Service settings | Make NexoPOS products bookable and define duration/payment rules. | Implemented |
| Staff assignment | Assign configured staff roles to service categories; users inherit capability through their roles. | Tested |
| Module settings | Booking window, slot size, confirmation behavior, and defaults. | Implemented |

## Data Model

Initial model set:

| Model | Table | Purpose | Status |
| --- | --- | --- | --- |
| `Appointment` | `nexopos_appointments` | Main booking record. | Tested |
| `AppointmentItem` | `nexopos_appointment_items` | Booked services/products inside an appointment. | Tested |
| `AppointmentService` | `nexopos_appointment_services` | Booking metadata for NexoPOS products. | Implemented |
| `AppointmentWorker` | `nexopos_appointment_workers` | Staff profile linked to a NexoPOS user. | Tested |
| `AppointmentCategoryRole` | `nexopos_appointment_category_roles` | Staff roles allowed to perform services in a category. | Tested |
| `AppointmentWorkingHour` | `nexopos_appointment_working_hours` | Weekly staff availability. | Not started |
| `AppointmentTimeOff` | `nexopos_appointment_time_off` | Staff unavailability periods. | Not started |
| `AppointmentResource` | `nexopos_appointment_resources` | Optional rooms, chairs, equipment, or stations. | Implemented |
| `AppointmentPayment` | `nexopos_appointment_payments` | Payment snapshots or linkage records if NexoPOS orders are not enough. | Needs decision |

Relationships must use explicit return types, casts through `casts()`, descriptive fillable fields, and tests covering core behavior.

## Backend Services

| Service | Responsibility | Status |
| --- | --- | --- |
| `AppointmentAvailabilityService` | Check conflicts and expose calendar events. | Tested |
| `BookingCartService` | Manage appointment cart state and totals. | Not started |
| `BookingCheckoutService` | Create appointments and order/payment requirements transactionally. | Not started |
| `AppointmentPaymentService` | Apply payment success/failure and update appointment status. | Not started |
| `AppointmentStatusService` | Enforce allowed appointment status transitions. | Not started |
| `StaffScheduleService` | Manage working hours and time off. | Not started |
| `BookableServiceService` | Manage product booking metadata. | Not started |

Core booking operations must use database transactions and locks so that concurrent customers cannot reserve the same staff/resource/time slot.

## API Surface

Public APIs:

- `GET /api/ns-appointments/public/catalog`
- `GET /api/ns-appointments/public/services/{service}`
- `GET /api/ns-appointments/public/availability`
- `POST /api/ns-appointments/public/checkout`
- `GET /api/ns-appointments/public/appointments/{reference}`

Admin APIs:

- `GET /api/ns-appointments/admin/appointments`
- `POST /api/ns-appointments/admin/appointments`
- `PUT /api/ns-appointments/admin/appointments/{appointment}`
- `PUT /api/ns-appointments/admin/appointments/{appointment}/status`
- `GET /api/ns-appointments/admin/calendar`
- `GET /api/ns-appointments/admin/workers`
- `POST /api/ns-appointments/admin/workers`
- `PUT /api/ns-appointments/admin/workers/{worker}`
- `GET /api/ns-appointments/admin/settings`
- `PUT /api/ns-appointments/admin/settings`

Public write endpoints still need throttling and validation. Admin endpoints need `NsRestrictMiddleware` permissions.

## Permissions

Suggested permissions:

| Permission | Purpose |
| --- | --- |
| `ns.appointments.read` | View appointments and calendar. |
| `ns.appointments.create` | Create appointments. |
| `ns.appointments.update` | Edit, reschedule, and update appointment status. |
| `ns.appointments.delete` | Delete appointments when allowed. |
| `ns.appointments.cancel` | Cancel appointments. |
| `ns.appointments.manage-staff` | Manage staff and schedules. |
| `ns.appointments.manage-services` | Configure bookable services. |
| `ns.appointments.manage-settings` | Manage module settings. |

Default assignment should be to administrator roles only unless a different operational role is explicitly chosen.

## Settings

Suggested settings:

- Business display name and public booking enablement.
- Default timezone.
- Booking window minimum notice.
- Booking window maximum days ahead.
- Slot interval.
- Default confirmation mode: automatic or manual.
- Default payment requirement: none, partial, or full.
- Default deposit type: fixed amount or percentage.
- Default deposit value.
- Cart hold duration.
- Cancellation window.
- Public guest checkout enabled or disabled.
- Reminder enablement and timing.

Category classification is implemented as module settings:

- Sellable product categories: regular products the SPA/salon can sell but that do not generate appointment slots.
- Service categories: NexoPOS product categories considered bookable services.
- Resource categories: NexoPOS product categories considered bookable rooms, chairs, equipment, or stations.
- Staff roles: NexoPOS roles whose users become assignable appointment workers.

The current implementation prevents a category from being configured as more than one type at the same time.

## Implementation Progress

Use these statuses:

- `Not started`
- `In progress`
- `Blocked`
- `Implemented`
- `Tested`
- `Reviewed`

### Phase 1: Clean Module Foundation

| Task | Status | Notes |
| --- | --- | --- |
| Confirm replacement strategy for old `NexoAppointments` module. | Implemented | Old module was removed and replaced with `NsAppointments`. |
| Scaffold `NsAppointments` module with NexoPOS generator. | Implemented | Generated with `php artisan make:module --no-interaction`. |
| Define `config.xml`, module class, provider, and namespaces. | Implemented | Namespace, routes, views, settings, and provider use `NsAppointments`. |
| Add route files and base dashboard menu. | Implemented | Public booking route, dashboard calendar/list/create routes, staff API, POS reservation API, and settings menu entries exist. |
| Add permissions migration. | Implemented | Module permissions are created and assigned to administrator/store administrator roles. |
| Add module Vite setup. | Implemented | Module Vite config and POS footer asset injection exist. |
| Add initial smoke tests. | Tested | Focused tests cover staff roles, category staff assignment, service-category form field, and category type overlap validation. |

### Phase 2: Domain And Availability

| Task | Status | Notes |
| --- | --- | --- |
| Create migrations for appointments, items, services, workers, hours, time off, and resources. | In progress | Appointments, items, services, role-based category assignment, worker availability, and resources exist. Working hours and time off are pending. |
| Create models and relationships. | In progress | Appointment, AppointmentItem, AppointmentService, AppointmentWorker, and AppointmentResource exist with core relations. |
| Implement service booking metadata. | Implemented | Product metadata table and product form fields exist for duration, buffers, payment mode, and resources. |
| Implement staff schedules and time off. | Not started | Weekly schedule plus exceptions. |
| Implement availability checks. | Tested | Staff/resource overlap checks include appointment items; focused tests cover overlap behavior. |
| Implement slot generation. | Tested | Public slots respect configured business days, open/close times, notice, window, duration, buffers, staff, and resource availability. |
| Add feature/unit tests for availability. | In progress | Focused overlap and public slot tests exist; concurrent hold tests are pending. |

### Phase 3: Admin MVP

| Task | Status | Notes |
| --- | --- | --- |
| Build service configuration UI/API. | Implemented | Product CRUD now exposes appointment service and resource metadata fields. |
| Build staff management UI/API. | Tested | Native CRUD lists role-backed workers and manages enabled, available, unavailable, and busy states. |
| Build working hours/time off UI/API. | Not started | Operational scheduling. |
| Build appointment list. | Implemented | Native NexoPOS CRUD table is registered at dashboard/ns-appointments/appointments. |
| Build calendar endpoint and view. | Implemented | Dashboard calendar view and protected admin calendar API exist. |
| Build manual appointment creation. | Implemented | Native NexoPOS CRUD create/edit form exists with availability validation. |
| Integrate reservation order type with the POS. | Tested | POS initial and add-to-cart queues load resources and server-authoritative service context through `nsHttpClient`. |
| Collect reservation details before payment. | Tested | POS popup selects date, optional room, and category-assigned staff, then revalidates the canonical schedule. |
| Persist POS reservations. | Tested | Order precheck and creation listeners are auto-discovered; appointment items preserve merged quantities and per-unit staff. |
| Add authorization tests. | Not started | Deny unauthorized users. |

### Phase 4: Public Booking MVP

| Task | Status | Notes |
| --- | --- | --- |
| Build public home page. | Implemented | Public route loads a module-local Vue SPA focused on booking. |
| Build services listing. | Implemented | Category filtering and service cards are implemented. |
| Build single service page. | Implemented | Selected service detail, payment requirement, and add-to-cart are implemented. |
| Build cart state and API. | Implemented | Frontend cart is local and server availability is revalidated before checkout. |
| Build checkout without payment. | Implemented | Guest checkout creates a manual-due/unpaid NexoPOS reservation order and appointment. |
| Build confirmation page. | Implemented | Checkout confirmation displays reference, schedule, status, and payment instructions. |
| Add frontend build verification. | Tested | Module-local Vite production build passes for booking and POS entrypoints. |
| Add public flow feature tests. | In progress | Catalog, service validation, and slot generation tests exist; full checkout tests are pending. |

### Phase 5: Payment Modes

| Task | Status | Notes |
| --- | --- | --- |
| Decide exact NexoPOS order/payment integration point. | Implemented | Public checkout creates a NexoPOS reservation order and records required payment as due. |
| Implement no-payment checkout. | Implemented | Appointments enter `pending_confirmation` when no payment is required. |
| Implement partial-payment checkout. | Implemented | Required deposit amount is calculated and appointment enters `pending_payment`. |
| Implement full-payment checkout. | Implemented | Full service price is required and appointment enters `pending_payment`. |
| Handle payment success callback/return. | Implemented | Appointment payment status syncs when the related NexoPOS order payment status changes. |
| Handle payment failure/expiration. | Not started | Release slot after hold expires. |
| Add payment mode tests. | Not started | No payment, partial, full, failure. |

### Phase 6: Polish And Operations

| Task | Status | Notes |
| --- | --- | --- |
| Add customer cancellation/reschedule rules. | Not started | Respect cancellation window. |
| Add reminders. | Not started | Email first, SMS only if provider exists. |
| Add reporting widgets. | Not started | Today, upcoming, revenue, no-show rate. |
| Add localization files. | Not started | Module-owned strings. |
| Add import/migration path from old module if needed. | Not started | Only if old data must be preserved. |
| Run full focused module test suite. | Tested | 18 NsAppointments feature tests pass with 63 assertions. |
| Run full app test suite if approved. | Not started | Optional after focused tests pass. |

## Open Decisions

| Decision | Options | Current choice |
| --- | --- | --- |
| Old module handling | Delete, archive, migrate data, or keep disabled. | Needs decision |
| Guest checkout | Allow guests or require account. | Allow guests |
| Confirmation mode | Automatic, manual, or per-service. | Manual confirmation |
| Payment provider path | Existing NexoPOS order payment, external gateway, or both. | Existing NexoPOS order, manual due for MVP |
| Cart persistence | Session, database, or local frontend state plus server validation. | Local frontend state plus server validation |
| Multiple services per appointment | Allow or one service per booking. | Allow, unless operationally risky |
| Resources | Rooms/chairs optional or required per service. | Optional |
| Staff selection | Customer chooses staff or system assigns any available. | Support both |

## Quality Gates

Before a phase is considered complete:

- Relevant Laravel docs were checked with Boost `search-docs` before code changes.
- Code follows NexoPOS module conventions and sibling module patterns.
- Controllers are thin and validation lives in Form Requests.
- Authorization is enforced server-side.
- Migrations are repeat-safe and rollback-safe.
- Business behavior is covered by focused PHPUnit tests.
- PHP files are formatted with `vendor/bin/pint --dirty --format agent`.
- Frontend assets build successfully when frontend code changes.
- Final diff is reviewed for namespace, route, permission, and asset alignment.
