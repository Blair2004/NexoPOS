# Driver Order Update API

This document describes the API endpoint for drivers to update order delivery status and handle payment on delivery.

## Endpoint

```
PUT /api/drivers/orders/{order}
```

## Authentication

Requires authentication. The user should have appropriate permissions to update driver orders.

## Request Parameters

### Required Fields

| Field | Type | Description |
|-------|------|-------------|
| `is_delivered` | boolean | Whether the order was successfully delivered (0 or 1) |
| `delivery_proof` | string | URL or path to delivery proof document/image |
| `note` | string | Delivery notes or comments |

### Optional Fields

| Field | Type | Description |
|-------|------|-------------|
| `payment_method` | string | Payment method identifier (must exist in payment types) |
| `paid_on_delivery` | boolean | Whether payment was received on delivery (0 or 1) |
| `driver_id` | integer | Driver ID (if different from order's assigned driver) |

## Request Example

```json
{
    "is_delivered": 1,
    "delivery_proof": "https://example.com/delivery-proof.jpg",
    "note": "Package delivered to customer at front door",
    "payment_method": "cash-payment",
    "paid_on_delivery": 1
}
```

## Response

### Success Response

```json
{
    "status": "success",
    "message": "Order delivery status has been updated successfully.",
    "data": {
        "order": {
            // Updated order object
        },
        "delivery_proof": {
            // Created/updated delivery proof record
        }
    }
}
```

### Error Response

```json
{
    "status": "error",
    "message": "Error description"
}
```

## Business Logic

### Delivery Status Update

- If `is_delivered` is `true`, the order delivery status is set to `delivered`
- If `is_delivered` is `false`, the order delivery status is set to `failed`

### Payment Handling

When `paid_on_delivery` is provided:

- If `true` and order is delivered:
  - Creates a payment entry for the remaining due amount
  - Updates order payment status if fully paid
  - Uses the provided payment method or defaults to cash

- If `false`:
  - Marks delivery as failed regardless of `is_delivered` value

### Commission Processing

When an order transitions from `ongoing` to `delivered` status and is paid:
- Automatically creates a driver earning record
- Calculates commission based on system settings (fixed rate or percentage)
- Commission is marked as pending until manually processed

## Database Changes

### OrderDeliveryProof Table

Records are created/updated in the `nexopos_orders_delivery_proof` table with:
- `is_delivered`: boolean delivery status
- `delivery_proof`: proof document URL
- `note`: delivery notes
- `paid_on_delivery`: payment status
- `order_id`: associated order
- `driver_id`: assigned driver

### Order Updates

- `delivery_status`: Updated based on delivery success
- `payment_status`: Updated if payment received on delivery
- Driver commission earnings are created automatically

## Validation Rules

- `is_delivered`: Required, must be boolean (0 or 1)
- `delivery_proof`: Required, must be string
- `note`: Required, must be string
- `payment_method`: Optional, must exist in payment types table
- `paid_on_delivery`: Optional, must be boolean (0 or 1)
- `driver_id`: Optional, must exist in users table

## Error Scenarios

1. **Invalid payment method**: Returns error if payment_method doesn't exist
2. **Missing required fields**: Returns validation error
3. **Database transaction failure**: Returns error with rollback
4. **Commission creation failure**: Logged as warning, doesn't fail the operation
