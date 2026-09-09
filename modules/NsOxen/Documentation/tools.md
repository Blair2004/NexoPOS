# Extending Oxen tools

Oxen 2.0 uses one registry for the embedded assistant, `GET /api/oxen/tools`, and `/mcp/oxen`. An enabled module may register tool classes from its service provider:

```php
use Modules\NsOxen\Facades\Oxen;

public function boot(): void
{
    Oxen::registerTools([LookupBookings::class]);
}
```

Each class implements `OxenToolContract`. Its `definition()` supplies a globally unique snake-case name (third-party modules should prefix it), title, Markdown description, category, source module, JSON input/output schemas, Laravel validation rules, Oxen token ability, NexoPOS permissions, risk, and confirmation policy. `execute()` receives an `OxenExecutionContext` and returns an `OxenToolResult`.

## Security and context

Treat the execution context as authoritative. It contains the actor, token abilities, active store, conversation, correlation ID, and idempotency key. Never accept `store_id` in a tool schema or infer a different store from model input. Oxen filters discovery by the current actor and repeats ability, permission, write enablement, destructive permission, active-store, validation, ownership, and expiry checks immediately before execution.

Read tools may execute during the model loop. Write and destructive calls from the embedded assistant create encrypted, opaque action proposals; they have no side effects until their owner clicks the action button. Destructive, money, refund, stock, finalization, register, deletion, and external-send actions must set `requiresConfirmation` so the client opens `nsConfirmPopup` before calling the execute endpoint.

Use an idempotency key for every mutation. Financial, order, procurement receipt, inventory, and register handlers must also use a database transaction and row locks around the affected aggregate. Return stable references rather than sensitive records.

## Schemas, results, and errors

Input and output schemas must both be JSON Schema objects. Keep result sets bounded and explicitly ordered. Validate again inside domain services when invariants depend on current state. Generated CSV/PDF artifacts must use bounded data and short-lived signed URLs.

Throw `OxenException` with a stable public code for expected failures. Do not include secrets, SQL, provider payloads, or exception internals. Unexpected failures are reported server-side and returned as `TOOL_FAILED`.

## Example

```php
final class LookupBookings implements OxenToolContract
{
    public function definition(): OxenToolDefinition
    {
        return new OxenToolDefinition(
            name: 'appointments_lookup_bookings',
            title: 'Lookup bookings',
            description: 'Find upcoming bookings for the active store.',
            category: 'Bookings',
            sourceModule: 'NsAppointments',
            inputSchema: ['type' => 'object', 'properties' => [], 'additionalProperties' => false],
            outputSchema: ['type' => 'object', 'properties' => ['bookings' => ['type' => 'array']]],
            rules: [],
            ability: 'oxen:read',
            permissions: ['nexopos.appointments.read'],
        );
    }

    public function execute(OxenExecutionContext $context, array $input): OxenToolResult
    {
        return new OxenToolResult(['bookings' => []]);
    }
}
```

## Testing

Tests should prove registry registration and duplicate rejection, schema validity, permission filtering, trusted store behavior, inventory parity across UI/OpenAI/MCP, bounded results, safe failures, and handler output shape. Mutation tests must additionally cover zero side effects before approval, confirmation, rejection, expiry, replay, idempotency conflicts, ownership and store isolation, permission changes, locks, and audit records.
