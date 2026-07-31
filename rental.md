# NexoPOS Rental & Hire Management

## 1. Project Overview

**NexoPOS Rental & Hire Management** is a commercial module that extends NexoPOS with reservation, availability, rental-contract, dispatch, return, deposit, damage, and maintenance workflows.

The module is intended for businesses that temporarily provide physical items to customers and charge according to duration, quantity, usage, or a predefined package.

The module should reuse existing NexoPOS resources wherever possible:

* Products and variations
* Customers
* Taxes
* Payments
* Orders and invoices
* Stores and registers
* Users and permissions
* Inventory
* Reports
* Notifications
* Accounting integrations

The module should not replace the existing NexoPOS POS or inventory system. It should add a dedicated rental layer around them.

---

# 2. Target Businesses

The first version should focus on businesses with relatively straightforward rental operations:

* Construction tool rental
* Event equipment rental
* Chairs, tables, tents, and decoration rental
* Sound and lighting equipment rental
* Camera and photography equipment rental
* Clothing, costumes, and wedding dress rental
* Electronics and appliance rental
* Camping and outdoor equipment rental
* Agricultural equipment rental
* Office equipment rental
* Party and catering equipment rental

More complex industries can be supported later:

* Vehicle rental
* Heavy machinery rental
* Property rental
* Subscription-based equipment leasing
* Hotel or room reservation
* Driver-based transport rental

Vehicle, property, and accommodation rental should not be the primary focus of the first release because they introduce additional insurance, mileage, identification, legal, and scheduling requirements.

---

# 3. Main Product Positioning

> Transform NexoPOS into a complete rental-management platform for reservations, asset availability, deposits, dispatch, returns, damage tracking, and maintenance.

The primary commercial advantages are:

* Works inside an existing NexoPOS installation
* Combines retail sales and rentals
* Uses the same customer and payment resources
* Supports serialized and quantity-based rental items
* Allows businesses to sell consumables alongside rentals
* Provides dedicated rental interfaces without duplicating NexoPOS
* Supports optional Multistore integration
* Can later expose an online customer-booking interface

---

# 4. Module Boundaries

## 4.1 Responsibilities of NexoPOS Core

NexoPOS Core remains responsible for:

* Product definitions
* Product categories
* Customers
* Tax configuration
* Payment types
* Registers and cashiers
* Orders and receipts
* User authentication
* Roles and permissions
* Basic inventory
* Reports and accounting events

## 4.2 Responsibilities of the Rental Module

The Rental module is responsible for:

* Marking products as rentable
* Managing rentable asset instances
* Calculating availability
* Creating reservations
* Creating rental contracts
* Scheduling pickups and returns
* Recording deposits
* Dispatching rented items
* Processing complete and partial returns
* Calculating extensions and late fees
* Recording damage, cleaning, loss, and repair charges
* Managing asset inspections
* Blocking assets under maintenance
* Recording the full rental timeline
* Producing rental-specific reports

---

# 5. Product and Asset Concepts

The module must distinguish between a **product** and a **physical asset**.

## 5.1 Rentable Product

A rentable product defines the commercial offer.

Examples:

* Plastic chair
* Canon EOS camera
* Electric drill
* Wedding dress
* 5 kVA generator

The product stores:

* Name
* Description
* Category
* Rental price
* Tax
* Billing unit
* Deposit rules
* Late-fee rules
* Rental restrictions
* Availability settings

## 5.2 Serialized Asset

A serialized asset represents one identifiable physical item.

Example:

* Product: Canon EOS R6
* Asset number: CAMERA-00014
* Serial number: 09238484
* Condition: Good
* Current location: Main Store

Serialized assets are appropriate for:

* Cameras
* Vehicles
* Power tools
* Generators
* Dresses
* Electronics
* Expensive equipment

Each asset can independently have:

* Serial number
* Barcode or QR code
* Purchase date
* Acquisition cost
* Current condition
* Current location
* Maintenance status
* Rental history
* Photos
* Inspection history

## 5.3 Quantity-Based Rental Stock

Some items do not need individual identification.

Examples:

* 250 plastic chairs
* 100 tablecloths
* 40 plates
* 25 extension cables

For these products, the availability engine works with quantities rather than specific asset records.

## 5.4 Rental Kits

A rental kit groups multiple rentable items.

Example: Wedding Package

* 100 chairs
* 10 tables
* 10 tablecloths
* 1 decoration arch
* 4 lights

A kit may be:

* Fixed: components cannot be changed
* Customizable: staff can adjust quantities
* Priced as a package
* Priced from its components

## 5.5 Consumables

A rental transaction may contain products that are sold rather than returned.

Examples:

* Fuel
* Batteries
* Cleaning products
* Disposable plates
* Tape
* Packaging
* Protective equipment

Consumables should use normal NexoPOS sale and inventory behavior.

---

# 6. Rental Lifecycle

The recommended lifecycle is:

1. Draft
2. Quotation
3. Reserved
4. Confirmed
5. Preparing
6. Ready for Pickup
7. Checked Out
8. Partially Returned
9. Returned
10. Closed

Alternative outcomes include:

* Canceled
* Rejected
* Expired
* No-show
* Lost
* Disputed

Operational conditions such as **overdue**, **deposit pending**, or **inspection required** should preferably be flags rather than permanent statuses.

Example:

* Status: Checked Out
* Flag: Overdue
* Flag: Outstanding balance

This prevents the main workflow from becoming unnecessarily complex.

---

# 7. Core Features

## 7.1 Rentable Product Configuration

A product can be marked as:

* Sale only
* Rental only
* Sale and rental

Rental configuration should include:

* Inventory mode

  * Serialized assets
  * Quantity-based stock
* Billing unit

  * Hour
  * Day
  * Night
  * Weekend
  * Week
  * Month
  * Fixed period
* Minimum rental duration
* Maximum rental duration
* Preparation buffer
* Return buffer
* Default deposit
* Late-fee policy
* Cleaning fee
* Included usage allowance
* Tax configuration
* Customer restrictions
* Advance-booking limit
* Same-day rental permission

## 7.2 Asset Registry

The asset registry manages serialized physical items.

Each asset should contain:

* Internal asset number
* Product
* SKU
* Barcode or QR code
* Manufacturer serial number
* Store
* Storage location
* Acquisition date
* Acquisition cost
* Replacement value
* Condition
* Availability status
* Maintenance status
* Last inspection
* Next inspection
* Notes
* Attachments
* Asset photos

Recommended asset statuses:

* Available
* Reserved
* Preparing
* Checked out
* Returning
* Inspection required
* Maintenance
* Damaged
* Lost
* Retired

## 7.3 Availability Calendar

The availability calendar is one of the module’s most important interfaces.

It should display:

* Existing reservations
* Confirmed rentals
* Checked-out assets
* Expected returns
* Preparation periods
* Maintenance blocks
* Store transfers
* Available quantities
* Overbooked periods

Calendar views:

* Day
* Week
* Month
* Timeline by product
* Timeline by serialized asset
* Timeline by store

Filters:

* Store
* Category
* Product
* Asset
* Customer
* Rental status
* Availability
* Assigned staff

## 7.4 Availability Engine

The availability engine must prevent overlapping commitments.

For quantity-based products:

```text
Available Quantity =
Physical Rental Stock
- Confirmed Reservations
- Checked-Out Quantity
- Maintenance Quantity
- Transfer Quantity
```

For serialized assets, the engine checks each asset separately.

An asset is available only when:

* It is physically available
* It has no overlapping reservation
* It is not under maintenance
* It is assigned to the selected store
* Preparation and return buffers do not overlap another rental
* It satisfies any required inspection rule

Reservations should initially hold stock for a configurable period.

Example:

* Reservation created at 10:00
* Payment required within two hours
* Reservation expires automatically at 12:00 if unpaid

## 7.5 Rental Reservation

Staff should be able to create reservations from:

* The rental calendar
* A dedicated rental screen
* The customer profile
* The regular NexoPOS POS
* A future customer portal
* A future REST API

Reservation information:

* Customer
* Rental location
* Pickup date and time
* Expected return date and time
* Products and quantities
* Assigned serialized assets
* Rental pricing
* Deposit
* Delivery requirements
* Customer notes
* Internal notes
* Required documents
* Payment status
* Reservation expiration

## 7.6 Quotations

A quotation can be created before availability is permanently reserved.

Quotation actions:

* Send to customer
* Print
* Download PDF
* Accept
* Reject
* Duplicate
* Convert into a reservation
* Convert into a rental contract
* Set expiration date

The system should optionally place a temporary availability hold while a quotation is pending.

## 7.7 Rental Contract

A confirmed reservation becomes a rental contract.

The contract should include:

* Contract number
* Customer details
* Business details
* Products and assets
* Rental dates
* Pricing
* Deposit
* Payment terms
* Pickup and return conditions
* Damage policy
* Late-return policy
* Customer signature
* Staff signature
* Attachments
* Identity-document references
* Terms and conditions

Contracts should be printable and exportable as PDF.

## 7.8 Deposits

The module should support:

* Fixed deposit
* Percentage deposit
* Deposit per item
* Deposit based on replacement value
* Customer-specific deposit
* No deposit
* Manual deposit override

A security deposit must not automatically be treated as rental revenue.

The module should maintain a deposit ledger containing:

* Deposit required
* Deposit collected
* Payment method
* Amount retained
* Amount applied to charges
* Amount refunded
* Refund method
* Refund date
* Responsible staff member

Possible deposit outcomes:

* Fully refunded
* Partially refunded
* Fully retained
* Converted into payment
* Pending inspection
* Disputed

Where the NexoPOS Accounting module is available, security deposits should be posted as a liability until refunded or applied.

## 7.9 Pickup and Check-Out

The check-out interface should guide staff through a controlled dispatch process.

Check-out steps:

1. Open the reservation.
2. Confirm the customer.
3. Verify required documents.
4. Confirm payment and deposit.
5. Scan or assign assets.
6. Complete the outgoing inspection.
7. Record accessories.
8. Record meter or usage values where applicable.
9. Capture photos.
10. Obtain customer signature.
11. Print the contract and dispatch receipt.
12. Mark the rental as checked out.

The system should block dispatch when:

* Required payment is missing
* Required deposit is missing
* The asset is unavailable
* An asset requires maintenance
* Required documents are missing
* The customer has reached their credit limit
* The customer is blocked

Authorized users may override certain restrictions, with the override recorded in the activity log.

## 7.10 Return and Check-In

The return interface should support:

* Full return
* Partial return
* Return of specific serialized assets
* Return of a partial quantity
* Early return
* Late return
* Return at another store
* Missing accessories
* Damaged asset
* Lost asset
* Additional usage charges
* Cleaning charges
* Deposit settlement

Return steps:

1. Scan the contract or search for the customer.
2. Scan returned assets.
3. Record actual return date and time.
4. Compare expected and actual quantities.
5. Perform incoming inspection.
6. Record damage and missing parts.
7. Add photos.
8. Calculate late or additional charges.
9. Collect outstanding payment.
10. Refund or retain the deposit.
11. Mark items as available, inspection required, or under maintenance.
12. Close the rental when all obligations are settled.

## 7.11 Partial Returns

A contract can remain open when only part of the rental is returned.

Example:

* 100 chairs rented
* 80 chairs returned
* 20 remain with the customer

The system should track:

* Original quantity
* Returned quantity
* Outstanding quantity
* Return events
* Additional fees
* Deposit remaining

Each partial return should generate its own return record.

## 7.12 Extensions

A customer may request an extension.

Before confirming the extension, the system must:

* Check future availability
* Detect conflicting reservations
* Calculate the additional rental charge
* Apply extension pricing
* Collect payment when required
* Update the expected return date
* Notify affected staff
* Update the customer contract

When a conflict exists, staff may:

* Reject the extension
* Shorten the extension
* Substitute the asset
* Move the next reservation to another asset
* Escalate for manager approval

## 7.13 Rescheduling

A reservation can be rescheduled when inventory is available for the new period.

The module should retain:

* Original schedule
* New schedule
* Rescheduling reason
* Price difference
* Staff member
* Customer notification status

Configurable rescheduling rules:

* Free rescheduling before a deadline
* Rescheduling fee
* Maximum number of changes
* Manager approval
* No rescheduling after dispatch

## 7.14 Cancellation and No-Show

Cancellation policies should support:

* Free cancellation
* Fixed cancellation fee
* Percentage cancellation fee
* Non-refundable reservation payment
* Deposit refund
* Store credit
* Customer-specific exception

No-show handling:

* Mark reservation as no-show
* Release reserved stock
* Retain a configurable amount
* Notify the customer
* Record the event in customer history

## 7.15 Late Returns

Late fees can be configured as:

* Fixed fee
* Hourly fee
* Daily fee
* Percentage of rental amount
* New rental period
* Product-specific fee
* Maximum capped fee

Additional options:

* Grace period
* Weekend treatment
* Business-hours calculation
* Manager override
* Automatic customer notification

The late fee should be calculated from the expected return time until the actual return time.

## 7.16 Damage and Loss Management

Damage records should include:

* Asset
* Contract
* Damage type
* Severity
* Description
* Photos
* Estimated repair cost
* Final repair cost
* Customer responsibility
* Amount charged
* Insurance information
* Maintenance ticket
* Staff member
* Resolution status

Damage statuses:

* Reported
* Under review
* Customer accepted
* Disputed
* Repairing
* Resolved
* Written off

Lost-item processing should allow:

* Replacement charge
* Deposit retention
* Additional invoice
* Asset status changed to lost
* Later recovery of the asset
* Reversal or adjustment of charges

## 7.17 Inspection Management

Inspection templates should be configurable per product category.

Example camera inspection:

* Body condition
* Lens condition
* Screen condition
* Battery included
* Charger included
* Memory card included
* Power-on test
* Photo test

Inspection types:

* Before dispatch
* On return
* Periodic inspection
* After maintenance

Inspection responses:

* Pass
* Fail
* Not applicable
* Text
* Numeric value
* Photo
* Signature

## 7.18 Maintenance

Maintenance can be preventive or corrective.

Maintenance features:

* Maintenance tickets
* Scheduled maintenance
* Usage-based maintenance
* Maintenance cost
* Service provider
* Start and expected completion dates
* Parts used
* Notes
* Attachments
* Asset downtime
* Return-to-service approval

Maintenance blocks the affected asset from availability.

Where quantity-based stock is used, a maintenance quantity should be removable from available stock.

---

# 8. Pricing Engine

The pricing engine should be flexible without becoming impossible to understand.

## 8.1 Supported Rental Rates

Each product may have:

* Hourly rate
* Daily rate
* Weekend rate
* Weekly rate
* Monthly rate
* Fixed-package rate
* Custom negotiated rate

Example:

* 1 day: $20
* 3 days: $50
* 1 week: $90

The pricing engine should choose the most appropriate rate according to configurable business rules.

## 8.2 Price Lists

Price lists may be assigned to:

* Customer groups
* Individual customers
* Stores
* Rental channels
* Business accounts
* Partners

Examples:

* Retail customer price
* Corporate customer price
* Event-planner price
* Partner price

## 8.3 Additional Charges

Supported charges:

* Delivery
* Collection
* Setup
* Installation
* Cleaning
* Damage waiver
* Insurance
* Fuel
* Excess usage
* Late return
* Missing accessory
* Repair
* Replacement
* Cancellation
* Rescheduling

## 8.4 Discounts

Supported discounts:

* Manual discount
* Percentage discount
* Fixed discount
* Duration discount
* Quantity discount
* Customer-group discount
* Coupon
* Promotional period
* Package discount

---

# 9. POS Integration

The module should integrate into the existing POS rather than build a second sales system.

## 9.1 POS Rental Mode

A new POS action can allow the cashier to select:

* Sale
* Rental
* Mixed sale and rental

When adding a rentable product, the cashier should choose:

* Rental dates
* Quantity
* Assigned asset, if required
* Pricing plan
* Deposit
* Pickup or delivery
* Customer

## 9.2 Mixed Orders

A single transaction may include:

* Rental items
* Sold products
* Consumables
* Delivery fees
* Setup fees
* Security deposit

The receipt must clearly separate:

* Rental revenue
* Product sales
* Refundable deposits
* Taxes
* Outstanding balance

## 9.3 NexoPOS Order Relationship

The rental record should remain the operational source of truth.

NexoPOS orders should represent financial events such as:

* Reservation payment
* Rental charge
* Additional charge
* Damage charge
* Product sale
* Deposit refund
* Final settlement

Each generated NexoPOS order should reference:

* Rental ID
* Contract number
* Transaction type
* Customer
* Store
* Staff member

This allows normal NexoPOS payment, reporting, tax, and receipt functionality to remain usable.

---

# 10. Inventory Integration

## 10.1 Rental Stock Versus Sale Stock

A product may have:

* Sale stock only
* Rental stock only
* Separate sale and rental stock
* Shared stock with manager approval

Separate stock is the safest default.

Example:

* 50 chairs reserved for rental
* 20 chairs available for direct sale

## 10.2 Inventory Movements

The module should record movements such as:

* Rental stock added
* Asset checked out
* Asset returned
* Asset moved to inspection
* Asset moved to maintenance
* Asset marked damaged
* Asset marked lost
* Asset retired
* Asset transferred between stores

A checked-out item is not sold and should not be permanently deducted from inventory.

## 10.3 Multistore Integration

With Multistore installed, the module should support:

* Store-specific rental stock
* Cross-store availability
* Pickup at one store
* Return at another store
* Asset transfers
* Central reservations
* Store-specific pricing
* Store-specific contracts
* Store-specific deposits

Multistore should be an optional integration rather than a hard dependency.

---

# 11. Customer Management

The regular NexoPOS customer resource should be reused.

The rental module can extend the customer profile with:

* Identification documents
* Document expiration
* Rental eligibility
* Deposit preference
* Credit limit
* Outstanding rental balance
* Active rentals
* Rental history
* Damage history
* No-show history
* Block status
* Internal risk notes
* Preferred pricing
* Signed agreements

Sensitive identity data should have dedicated permissions and configurable retention rules.

## 11.1 Customer Blocking

A customer may be blocked because of:

* Unpaid balance
* Lost equipment
* Repeated late returns
* Invalid identification
* Fraud concerns
* Management decision

Blocking should not delete historical transactions.

---

# 12. User Interfaces

The module should have dedicated interfaces while remaining integrated into NexoPOS navigation.

## 12.1 Rental Dashboard

Dashboard widgets:

* Rentals starting today
* Returns expected today
* Overdue rentals
* Reservations awaiting payment
* Assets requiring inspection
* Assets under maintenance
* Outstanding balances
* Deposits awaiting refund
* Revenue for selected period
* Most-rented products
* Utilization rate

## 12.2 Reservation Calendar

Provides visual availability and scheduling.

## 12.3 Rental List

Filters:

* Status
* Date
* Customer
* Store
* Product
* Staff member
* Payment status
* Overdue
* Deposit status

## 12.4 Rental Editor

Used to create and modify:

* Quotations
* Reservations
* Contracts
* Extensions
* Rescheduling

## 12.5 Dispatch Workbench

Optimized for barcode scanning and outgoing inspection.

## 12.6 Return Workbench

Optimized for:

* Scanning returns
* Partial returns
* Damage inspection
* Additional charges
* Deposit settlement

## 12.7 Asset Registry

Manages serialized equipment and its history.

## 12.8 Maintenance Board

Views:

* Open tickets
* Planned maintenance
* Overdue maintenance
* Waiting for parts
* Completed maintenance

## 12.9 Customer Rental Profile

Shows:

* Active reservations
* Checked-out items
* Overdue items
* Deposits
* Outstanding balances
* Rental history
* Damage records

## 12.10 Settings

Settings should be grouped into:

* General
* Availability
* Pricing
* Deposits
* Contracts
* Check-out
* Returns
* Late fees
* Damage
* Maintenance
* Notifications
* Numbering
* Permissions
* Integrations

---

# 13. Notifications

Notifications should be event-based.

Supported channels can include:

* Email
* SMS
* WhatsApp through a compatible provider
* In-app notification
* Webhook

Notification events:

* Quotation created
* Reservation created
* Reservation confirmed
* Payment required
* Reservation expiring
* Pickup reminder
* Rental ready
* Contract checked out
* Return reminder
* Rental due today
* Rental overdue
* Extension accepted
* Extension rejected
* Rental returned
* Damage reported
* Additional payment required
* Deposit refunded
* Maintenance completed

Templates should support variables such as:

```text
{customer_name}
{contract_number}
{pickup_date}
{return_date}
{amount_due}
{deposit_amount}
{store_name}
{rental_items}
```

---

# 14. Documents and Printing

Printable documents:

* Quotation
* Reservation confirmation
* Rental contract
* Dispatch note
* Asset checklist
* Return receipt
* Damage report
* Deposit receipt
* Deposit refund receipt
* Payment reminder
* Customer statement
* Maintenance report

Documents should support:

* Business logo
* Custom footer
* Terms and conditions
* Customer signature
* Staff signature
* QR code
* Contract barcode
* Custom paper sizes
* PDF export

---

# 15. Reports

## 15.1 Operational Reports

* Active rentals
* Upcoming pickups
* Expected returns
* Overdue rentals
* Partial returns
* Canceled reservations
* No-shows
* Assets under maintenance
* Damaged assets
* Lost assets

## 15.2 Financial Reports

* Rental revenue
* Additional-charge revenue
* Late-fee revenue
* Damage-fee revenue
* Deposit liability
* Deposits refunded
* Deposits retained
* Outstanding rental balances
* Revenue by product
* Revenue by category
* Revenue by customer
* Revenue by store
* Revenue by staff member

## 15.3 Asset Reports

* Asset utilization
* Rental frequency
* Asset revenue
* Asset maintenance cost
* Asset profitability
* Asset downtime
* Asset condition
* Asset lifecycle
* Assets approaching replacement value
* Never-rented assets

## 15.4 Customer Reports

* Top rental customers
* Customers with overdue equipment
* Customers with outstanding balances
* Frequent late-return customers
* Blocked customers
* Customer rental history

---

# 16. Roles and Permissions

Suggested permissions:

```text
rental.view-dashboard
rental.view
rental.create
rental.edit
rental.cancel
rental.confirm
rental.reschedule
rental.extend
rental.checkout
rental.return
rental.partial-return
rental.override-availability
rental.override-price
rental.override-deposit
rental.waive-late-fee
rental.record-damage
rental.resolve-damage
rental.refund-deposit
rental.retain-deposit
rental.manage-assets
rental.manage-maintenance
rental.manage-settings
rental.view-reports
rental.view-sensitive-customer-data
rental.block-customer
```

Sensitive actions should be recorded in the NexoPOS activity log.

---

# 17. Suggested Data Model

The exact naming should follow NexoPOS module conventions.

## 17.1 Rental Products

```text
rental_product_settings
- id
- product_id
- rental_enabled
- rental_inventory_mode
- default_billing_unit
- minimum_duration
- maximum_duration
- preparation_buffer
- return_buffer
- default_deposit_type
- default_deposit_value
- late_fee_policy_id
- configuration
- created_at
- updated_at
```

## 17.2 Assets

```text
rental_assets
- id
- product_id
- store_id
- asset_number
- serial_number
- barcode
- status
- condition
- acquisition_date
- acquisition_cost
- replacement_value
- last_inspection_at
- next_inspection_at
- metadata
- created_at
- updated_at
```

## 17.3 Rentals

```text
rentals
- id
- uuid
- contract_number
- customer_id
- store_id
- register_id
- created_by
- assigned_to
- status
- pickup_at
- expected_return_at
- actual_return_at
- currency
- subtotal
- discount
- tax
- charges_total
- deposit_required
- deposit_collected
- deposit_refunded
- deposit_retained
- amount_paid
- balance
- payment_status
- notes
- customer_notes
- terms_snapshot
- created_at
- updated_at
```

## 17.4 Rental Lines

```text
rental_lines
- id
- rental_id
- product_id
- variation_id
- description
- inventory_mode
- quantity
- returned_quantity
- billing_unit
- duration
- unit_price
- subtotal
- discount
- tax
- total
- metadata
```

## 17.5 Assigned Assets

```text
rental_line_assets
- id
- rental_line_id
- asset_id
- checkout_at
- return_at
- outgoing_condition
- incoming_condition
- status
```

## 17.6 Pricing Rules

```text
rental_pricing_rules
- id
- product_id
- customer_group_id
- store_id
- name
- billing_unit
- minimum_duration
- maximum_duration
- price
- priority
- starts_at
- ends_at
- configuration
```

## 17.7 Deposits

```text
rental_deposits
- id
- rental_id
- amount
- collected_amount
- refunded_amount
- retained_amount
- status
- payment_type_id
- collected_at
- refunded_at
- processed_by
- notes
```

## 17.8 Returns

```text
rental_returns
- id
- rental_id
- store_id
- processed_by
- returned_at
- status
- additional_charges
- refund_amount
- notes
```

## 17.9 Return Lines

```text
rental_return_lines
- id
- rental_return_id
- rental_line_id
- asset_id
- quantity
- condition
- late_fee
- damage_fee
- cleaning_fee
- missing_fee
- notes
```

## 17.10 Inspections

```text
rental_inspections
- id
- rental_id
- asset_id
- inspection_template_id
- type
- status
- inspected_by
- inspected_at
- notes
```

## 17.11 Damage Records

```text
rental_damages
- id
- rental_id
- asset_id
- return_id
- severity
- description
- estimated_cost
- final_cost
- customer_charge
- status
- reported_by
- resolved_by
- resolved_at
```

## 17.12 Maintenance

```text
rental_maintenance
- id
- asset_id
- type
- status
- scheduled_at
- started_at
- completed_at
- service_provider
- estimated_cost
- final_cost
- description
- resolution
```

## 17.13 Activity Timeline

```text
rental_activities
- id
- rental_id
- user_id
- action
- old_value
- new_value
- metadata
- created_at
```

---

# 18. Events and Extension Points

The module should dispatch events that other modules can consume.

Suggested events:

```text
RentalCreated
RentalQuoted
RentalReserved
RentalConfirmed
RentalPaymentReceived
RentalPreparing
RentalReady
RentalCheckedOut
RentalPartiallyReturned
RentalReturned
RentalClosed
RentalCanceled
RentalNoShow
RentalExtended
RentalRescheduled
RentalBecameOverdue
RentalDamageReported
RentalDepositCollected
RentalDepositRefunded
RentalDepositRetained
RentalBalanceUpdated
RentalAssetAssigned
RentalAssetReleased
RentalAssetMaintenanceStarted
RentalAssetMaintenanceCompleted
```

This will allow integrations with:

* Notifications
* Accounting
* Marketing
* Webhooks
* Customer loyalty
* Sales commissions
* External customer portals

---

# 19. REST API

The module should provide authenticated endpoints for future mobile and web applications.

Suggested endpoint groups:

```text
GET    /api/rentals
POST   /api/rentals
GET    /api/rentals/{rental}
PUT    /api/rentals/{rental}
POST   /api/rentals/{rental}/confirm
POST   /api/rentals/{rental}/checkout
POST   /api/rentals/{rental}/return
POST   /api/rentals/{rental}/extend
POST   /api/rentals/{rental}/reschedule
POST   /api/rentals/{rental}/cancel

GET    /api/rental-products
GET    /api/rental-products/{product}/availability

GET    /api/rental-assets
GET    /api/rental-assets/{asset}
POST   /api/rental-assets/{asset}/maintenance

POST   /api/rentals/{rental}/deposit
POST   /api/rentals/{rental}/deposit/refund
POST   /api/rentals/{rental}/charges
```

Public booking endpoints should be separate from administrative endpoints and protected with appropriate rate limits.

---

# 20. Background Jobs and Scheduled Tasks

Scheduled tasks should handle:

* Expiring unpaid reservations
* Releasing temporary stock holds
* Sending pickup reminders
* Sending return reminders
* Detecting overdue rentals
* Recalculating late fees
* Sending overdue notifications
* Detecting inspection deadlines
* Detecting maintenance deadlines
* Updating rental metrics
* Cleaning expired availability locks

Long-running operations should use queued jobs.

---

# 21. Important Edge Cases

The implementation must account for:

* Customer returns an asset to another store
* Customer returns only part of a quantity
* Customer returns an item early
* Customer wants an extension that conflicts with another reservation
* Asset is damaged immediately before pickup
* Assigned asset becomes unavailable
* Reservation contains serialized and quantity-based products
* Customer pays using several payment methods
* Deposit is paid separately from rental charges
* Deposit refund uses another payment method
* Customer loses one item from a kit
* A returned asset requires inspection before becoming available
* Staff accidentally checks out the wrong asset
* Rental dates cross tax or pricing periods
* Rental price is manually overridden
* Customer disputes damage
* Customer keeps the asset permanently
* Asset is returned after being marked lost
* Reservation is canceled after preparation has started
* Item is transferred between stores during a reservation
* Product is deleted while historical rentals reference it
* Customer record is anonymized but accounting history must remain

---

# 22. Audit and Security

Sensitive actions must be logged:

* Availability override
* Price override
* Deposit override
* Deposit refund
* Deposit retention
* Late-fee waiver
* Damage-fee waiver
* Customer blocking
* Contract modification after signature
* Asset-status change
* Rental deletion
* Return reversal

Financial rental records should not normally be permanently deleted.

Instead, use:

* Cancellation
* Reversal
* Adjustment
* Archived status

---

# 23. MVP Scope

The initial release should solve the complete basic rental workflow without attempting every advanced use case.

## MVP Features

* Mark products as rentable
* Serialized and quantity-based rental stock
* Asset registry
* Availability checking
* Rental calendar
* Reservations
* Rental contracts
* Basic hourly, daily, weekly, and fixed pricing
* Deposits
* Pickup and check-out
* Full and partial returns
* Late fees
* Damage recording
* Basic inspections
* Maintenance blocking
* Customer rental history
* Rental receipts and contracts
* Basic reports
* Permissions
* NexoPOS payment integration
* NexoPOS order integration
* Notification events

## Excluded From MVP

* Public customer portal
* Native mobile application
* Route planning
* Driver management
* Electronic identity verification
* Insurance integrations
* GPS asset tracking
* Subscription leasing
* Usage-meter billing
* Advanced dynamic pricing
* Digital-signature provider integrations
* Vehicle-specific workflows
* Marketplace listing of rental businesses

---

# 24. Implementation Phases

## Phase 1: Foundation

* Module scaffolding
* Permissions
* Settings
* Rentable product configuration
* Serialized asset registry
* Quantity-based rental inventory
* Rental status machine
* Module events

## Phase 2: Reservations and Availability

* Availability engine
* Calendar
* Reservation editor
* Quotations
* Temporary holds
* Conflict detection
* Rescheduling

## Phase 3: Pricing and Payments

* Pricing rules
* Discounts
* Taxes
* Deposits
* NexoPOS order integration
* Partial payments
* Receipts and contracts

## Phase 4: Dispatch and Returns

* Asset assignment
* Check-out workbench
* Outgoing inspections
* Return workbench
* Partial returns
* Late fees
* Deposit settlement

## Phase 5: Damage and Maintenance

* Damage reports
* Photos and attachments
* Repair charges
* Maintenance tickets
* Maintenance calendar
* Asset downtime

## Phase 6: Reporting and Stabilization

* Dashboard
* Operational reports
* Financial reports
* Asset-utilization reports
* Audit logs
* Automated notifications
* Documentation
* Tests
* Demo data

## Phase 7: Pro Features

* Customer portal
* Online reservations
* Online payment
* Electronic signatures
* Advanced price lists
* Multistore transfer workflows
* API integrations
* Webhooks
* Customer document management

---

# 25. Testing Strategy

## Unit Tests

* Duration calculation
* Pricing calculation
* Deposit calculation
* Late-fee calculation
* Availability calculation
* Buffer calculation
* Reservation expiration
* Partial-return quantities
* Deposit settlement

## Feature Tests

* Create reservation
* Confirm reservation
* Prevent overlapping booking
* Dispatch serialized asset
* Dispatch quantity-based product
* Process full return
* Process partial return
* Process late return
* Record damage
* Refund deposit
* Retain part of a deposit
* Block asset under maintenance
* Reschedule reservation
* Extend active rental
* Enforce permissions

## Integration Tests

* NexoPOS order creation
* Customer relationship
* Payment recording
* Product inventory
* Tax calculation
* Multistore compatibility
* Accounting events
* Notification events

## Browser Tests

* Calendar interactions
* Barcode-scanning workflow
* Check-out workflow
* Return workflow
* Partial-return workflow
* Asset assignment
* Contract printing

---

# 26. Demo Data

The module should ship with optional demonstration data for an event-equipment rental business.

Example catalog:

* Plastic chair
* Round table
* Folding table
* Event tent
* LED light
* Speaker
* Wireless microphone
* Extension cable
* Generator
* Decoration package

Demo scenarios:

* Future reservation
* Reservation awaiting deposit
* Rental ready for pickup
* Active rental
* Partial return
* Overdue rental
* Damaged asset
* Asset under maintenance
* Deposit awaiting refund

A complete demo will make the module easier to understand and sell.

---

# 27. Documentation

The documentation should include:

1. Installation
2. Requirements
3. Permissions
4. Initial configuration
5. Creating rentable products
6. Creating serialized assets
7. Configuring rental prices
8. Creating a reservation
9. Checking availability
10. Collecting a deposit
11. Checking out an order
12. Processing a return
13. Processing a partial return
14. Extending a rental
15. Rescheduling a rental
16. Recording damage
17. Refunding a deposit
18. Managing maintenance
19. Understanding reports
20. Multistore integration
21. Troubleshooting
22. Developer events and API

---

# 28. Commercial Packaging

## Product Name

Recommended primary name:

**NexoPOS Rental & Hire Management**

Alternative names:

* NexoPOS Rentals
* Nexo Hire
* NexoPOS Equipment Rental
* NexoPOS Rental Manager

“Rental & Hire Management” is descriptive and understandable across different English-speaking markets.

## Suggested Pricing

### Standard License

Suggested price: **$59–$69**

Includes:

* Reservations
* Availability
* Rental contracts
* Deposits
* Check-out
* Returns
* Assets
* Maintenance
* Basic reports

### Pro License or Add-on Pack

Suggested price: **$79–$99**

Additional features could include:

* Online booking portal
* Advanced pricing
* Digital signatures
* Advanced reports
* Webhooks
* Multistore rental coordination
* Customer documents
* Additional automation

A single complete product may initially be easier to maintain than separate editions.

## Potential Bundle

### Rental Business Bundle

* Rental & Hire Management
* Multistore
* Stock Transfers
* Racks Manager
* Bulk Importer
* Sales Commissions
* Nexo Print Server Adapter

Suggested bundle price: **$109–$149**, depending on the included modules.

---

# 29. Recommended First Market

The first demo and marketing materials should target:

> Event equipment and general tool rental businesses.

This market demonstrates nearly all important module features:

* Serialized equipment
* Quantity-based products
* Rental packages
* Deposits
* Deliveries
* Partial returns
* Damage
* Cleaning
* Maintenance
* Late returns

It is broader and less legally complex than vehicle rental.

---

# 30. Future Roadmap

Possible later extensions:

* Customer self-service portal
* Mobile employee application
* Mobile barcode scanning
* Delivery route management
* Driver assignment
* GPS-tracked assets
* Electronic identity verification
* Electronic signatures
* Recurring equipment subscriptions
* Meter and mileage billing
* Insurance and damage-waiver products
* Marketplace for public rental listings
* Customer reviews
* Online availability widget
* Embedded booking form
* Rental franchise management
* AI-assisted damage comparison using before-and-after photos
* AI-generated contract summaries
* AI customer reminders
* Predictive maintenance
* Demand and utilization forecasting

---

# 31. Final Recommendation

The first version should prioritize a dependable operational workflow:

```text
Product Configuration
→ Availability
→ Reservation
→ Payment and Deposit
→ Contract
→ Check-Out
→ Return
→ Inspection
→ Additional Charges
→ Deposit Settlement
→ Closure
```

The most important technical components are:

1. A reliable availability engine
2. Clear separation between products and physical assets
3. Proper handling of refundable deposits
4. Strong partial-return support
5. Controlled asset check-out and check-in
6. Complete audit history
7. Integration with existing NexoPOS orders, customers, payments, and inventory

The module should feel like a specialized extension of NexoPOS rather than an independent application placed beside it.
