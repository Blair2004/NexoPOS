# Oxen — NexoPOS AI Tools Module Plan

## 1. Purpose

Build a self-contained NexoPOS module that exposes selected NexoPOS capabilities to large language models through the Model Context Protocol (MCP).

The module should let an authenticated AI client discover store data, answer operational questions, and perform explicitly authorized actions without giving the model direct database access or unrestricted access to NexoPOS internals.

The module is named **Oxen**, a reversal of “Nexo”. It is intentionally not branded as “Oxen AI”: Oxen provides the secure tools and integration surface into which different AI providers and clients can be plugged.

The module identity is:

| Item | Proposed value |
| --- | --- |
| Namespace | `NsOxen` |
| Display name | Oxen |
| Initial version | `1.0.0` |
| Endpoint | `/mcp/pos` |
| Transport | Laravel MCP web transport |
| Authentication | Sanctum bearer tokens initially; OAuth 2.1 as a later option |

### Product direction

Oxen has three deliberately separated layers:

1. **Tool gateway:** the `NsOxen` module exposes authorized NexoPOS tools and resources. This is the required foundation.
2. **Assistant experience:** an optional Codex/GPT-like conversational interface can use OpenAI, Ollama, or another provider to discover and call Oxen tools.
3. **Commercial access platform:** a future hosted middleman can authenticate mobile applications, verify subscription/payment entitlement, and broker access to a merchant Oxen installation.

Keeping these layers separate allows Oxen to remain useful without bundling a model, committing to one AI vendor, or making a future mobile platform part of the initial trust boundary.

## 2. Current State

NexoPOS already contains an initial MCP implementation in the application core:

- `routes/ai.php` registers `/mcp/pos` with `auth:sanctum` and `throttle:mcp`.
- `app/Mcp/Servers/POSServer.php` registers tools and resources.
- `app/Mcp/Tools/` contains catalog, customer, order, reporting, media, and settings operations.
- `app/Mcp/Resources/` contains store configuration and reference data.
- `tests/Feature/Mcp/` contains direct tool tests.
- The MCP rate limiter currently allows 60 requests per minute per authenticated user or IP.

This is a useful proof of concept, but it should not be expanded in core as-is. The module project should extract and harden it while preserving the public endpoint and tool names where safe.

Important issues to resolve during extraction:

- Authentication exists at the endpoint, but authorization is not consistently enforced per tool or resource.
- Existing personal access tokens are created without MCP-specific abilities.
- Some write tools mutate Eloquent models or options directly instead of using NexoPOS services/actions.
- `update_settings` accepts arbitrary option keys and needs an explicit allowlist.
- Some tool schemas do not fully describe required handler arguments.
- Raw exception messages can leak implementation details.
- Server instructions mention operations that are not registered, while some implemented tools are not registered.
- Existing tests call handlers directly and therefore do not prove transport authentication, token abilities, authorization, or throttling.

## 3. Product Scope

### 3.1 Primary integration: external AI clients

An MCP-compatible client connects to NexoPOS over HTTPS using a user-owned access token. The client discovers only the tools and resources that are enabled and available to that user.

```text
LLM host / AI agent
        |
        | HTTPS + bearer token + MCP
        v
Oxen MCP endpoint (/mcp/pos)
        |
        +-- authentication and rate limits
        +-- token ability checks
        +-- NexoPOS role/permission checks
        +-- input validation and store scoping
        +-- action/service execution
        +-- audit recording and safe response mapping
        v
Existing NexoPOS services and domain models
```

The MCP layer is an adapter. It must call the same domain services and enforce the same invariants as the dashboard and POS APIs. It must not become a second business-logic implementation.

### 3.2 NexoPOS dashboard integration

The module adds an administrator page for:

- Enabling or disabling MCP access globally.
- Selecting which capability groups are available.
- Enabling write and destructive operations separately.
- Creating named, scoped MCP tokens for a user.
- Showing the token once, then storing only its hash through Sanctum.
- Listing token abilities, last use, creation date, and revocation controls.
- Reviewing MCP activity and failed/denied operations.
- Configuring read and write rate limits, report expiry, and confirmation policy.
- Displaying client connection instructions and the resolved MCP endpoint.

The page should use existing dashboard layout, components, permissions, semantic theme tokens, module localization, and module Vite assets. Vue components inside `#dashboard-content` must register through `nsExtraComponents`; they must not mount a nested Vue application.

### 3.3 Optional POS integration

An embedded POS assistant is not required for the first release. The first release provides tools to external AI clients and a dashboard configuration surface.

A later Oxen assistant may add a header or cart button that opens a NexoPOS-native, Codex/GPT-like panel: streaming conversation, visible tool activity, approval cards, progress, and recoverable errors. It should call a server-side orchestration endpoint and display suggestions or draft actions. It must not expose provider keys or bearer tokens in browser JavaScript or let an LLM mutate the live browser cart through undocumented DOM access.

Any cart-related action should use established POS APIs such as `POS.updateProduct`, queues, and hooks. Financial or inventory mutations remain authoritative on the server.

### 3.4 Future mobile and commercial-platform integration

A mobile application can eventually use Oxen, but it should not receive a merchant-wide MCP token or connect directly with unrestricted credentials. The proposed middleman platform should act as a control plane and broker:

```text
Mobile application
        |
        | user/session authentication
        v
Commercial platform
        +-- subscription/payment entitlement
        +-- tenant and device identity
        +-- capability policy and revocation
        +-- request correlation and metering
        |
        | short-lived, merchant-scoped authorization
        v
Merchant Oxen gateway
        |
        v
NexoPOS services
```

The platform should verify commercial entitlement, but NexoPOS remains authoritative for domain authorization. A paid subscription must never automatically grant permission to view customers, create orders, issue refunds, or manipulate inventory.

This future path requires decisions about tenant discovery, inbound versus outbound connectivity, offline behavior, webhook signing, short-lived credentials, revocation, merchant consent, regional privacy requirements, and who operates the public relay. It should remain outside Version 1 until the MCP gateway is secure and stable.

## 4. Capability Model

Capabilities should be released in risk-based phases.

### Phase 1: read-only operations

| Area | Candidate tools/resources | Required domain permission |
| --- | --- | --- |
| Catalog | Search/get products, categories, units, stock availability | Product read permission |
| Customers | Search/get customers, groups, wallet summary | Customer read permission |
| Orders | Search/get orders and payment status | Order read permission |
| Operations | Low-stock list, dashboard summary | Stock/report permissions |
| Reports | Generate a bounded temporary report | Report permission |
| Reference data | Store profile, tax groups, payment types | Corresponding read permission |

Phase 1 is the minimum viable product. All applicable tools receive the MCP `IsReadOnly` annotation and return bounded, paginated, structured responses.

### Phase 2: low-risk managed writes

| Area | Candidate tools | Additional controls |
| --- | --- | --- |
| Categories | Create and update category metadata | Create/update category permission, audit |
| Products | Update selected product metadata | Field allowlist, validation, audit |
| Media | Upload and attach media | MIME/size rules, storage policy, audit |
| Customers | Create/update allowed profile fields | Explicit field allowlist, duplicate checks |

Write operations are disabled by default and require both an MCP token ability and the corresponding NexoPOS permission.

### Phase 3: high-risk operations

| Area | Examples | Required safeguards |
| --- | --- | --- |
| Inventory | Quantity adjustments, transfers | Reason, location/register context, idempotency, transaction, lock |
| Orders | Create order, hold, void, refund | Draft/preview, explicit confirmation, service reuse, audit |
| Payments | Add/refund payment | Narrow permission, register context, confirmation, idempotency |
| Settings | Update specifically supported settings | Key/type allowlist, secret redaction, before/after audit |
| Deletion | Product/category/media deletion | Destructive annotation, dependency checks, explicit confirmation |

These tools should not ship until their underlying NexoPOS service path and rollback/failure behavior are covered by focused tests.

### Explicit non-goals

- No arbitrary SQL, Eloquent model, option, filesystem, Artisan, or shell execution tool.
- No generic `execute_action` tool accepting a class or method name.
- No unrestricted settings writer.
- No direct access to passwords, API secrets, payment credentials, full tokens, or sensitive logs.
- No automatic sale, payment, refund, deletion, or stock mutation based only on conversational intent.
- No client-side MCP secret embedded in the POS page.

## 5. Module Architecture

Proposed layout:

```text
modules/NsOxen/
├── config.xml
├── NsOxenModule.php
├── Providers/
│   └── ModuleServiceProvider.php
├── Mcp/
│   ├── Servers/
│   │   └── POSServer.php
│   ├── Tools/
│   │   ├── Catalog/
│   │   ├── Customers/
│   │   ├── Orders/
│   │   ├── Reports/
│   │   └── Administration/
│   ├── Resources/
│   └── Prompts/
├── Actions/
├── Authorization/
│   ├── Capability.php
│   └── AuthorizeMcpOperation.php
├── Data/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Models/
│   └── McpAuditEvent.php
├── Migrations/
├── Permissions/
├── Settings/
├── Routes/
│   ├── api.php
│   └── web.php
├── Resources/
│   ├── Views/
│   ├── ts/
│   └── css/
└── Tests/
    ├── Feature/
    └── Unit/
```

Each MCP tool should follow this pipeline:

1. Advertise a complete JSON input schema and, where useful, an output schema.
2. Hide itself with `shouldRegister()` when the capability is disabled or unavailable to the actor.
3. Validate again in `handle()`; JSON Schema is not the security boundary.
4. Require the correct Sanctum token ability.
5. Require the corresponding NexoPOS role permission.
6. Resolve store, register, warehouse, and currency context from trusted server-side state.
7. Pass validated data to a small action that reuses an existing NexoPOS service.
8. Execute mutations in a transaction and use a lock where concurrency affects stock or money.
9. Write a redacted audit event.
10. Return stable structured content and a short model-readable summary.

## 6. Authentication and Authorization

### 6.1 Authentication

Keep `auth:sanctum` for the initial web endpoint. Require HTTPS outside local development.

OAuth 2.1 through Laravel Passport is a future option for third-party integrations needing delegated authorization, discovery, and token lifecycle standards. Adding Passport changes dependencies and should be a separate approved project.

### 6.2 Dual authorization

Every operation must pass both checks:

```text
Token ability allows the capability
                AND
NexoPOS user role allows the domain operation
```

Recommended token abilities:

- `mcp:connect`
- `mcp:read`
- `mcp:write`
- `mcp:destructive`
- Optional domain abilities such as `mcp:products`, `mcp:customers`, `mcp:orders`, and `mcp:reports`

Recommended module permissions:

- `nexopos.mcp.connect`
- `nexopos.mcp.manage`
- `nexopos.mcp.view-audit`
- `nexopos.mcp.issue-tokens`
- `nexopos.mcp.use-writes`
- `nexopos.mcp.use-destructive`

Module permissions supplement domain permissions; they never replace them. For example, updating a product requires `mcp:write`, `nexopos.mcp.use-writes`, and the existing product-update permission.

Use `shouldRegister()` to avoid advertising forbidden capabilities, but repeat authorization inside `handle()` so direct calls cannot bypass enforcement.

### 6.3 Store and register context

Multi-store and register-sensitive operations must never trust a free-form store or register ID from the model. The module should:

- Derive accessible stores from the authenticated user and active NexoPOS context.
- Require an explicit store when a user can access several stores.
- Reject cross-store IDs before querying or mutating data.
- Require an open, user-accessible register for register-bound sales or payments.
- Include `store_id` and `register_id` in audit events when applicable.

## 7. Mutation Safety and Human Confirmation

Every mutating tool accepts an `idempotency_key`. The module stores or caches the operation fingerprint and returns the original result for an exact retry. Reusing a key with different arguments is rejected.

Consequential operations use a two-step contract:

1. A preview/draft tool validates the intended action and returns normalized effects, totals, warnings, and a short-lived confirmation token.
2. A commit tool requires that confirmation token and the idempotency key.

This applies at minimum to stock adjustments, completed orders, payments, refunds, settings changes, and deletions. MCP destructive/idempotent annotations describe behavior to the client but do not replace server enforcement.

The module should never infer confirmation from vague text such as “go ahead” inside a tool argument. Confirmation must be represented by the dedicated, server-issued token.

## 8. Data and Response Contracts

### Input rules

- Prefer stable IDs plus optional human-readable lookup tools.
- Reject unknown fields.
- Use enums for statuses, payment types, order types, and adjustment types.
- Bound search terms, date ranges, page size, upload size, and report size.
- Use decimal-safe money handling consistent with NexoPOS; do not let the LLM perform authoritative totals.
- Validate relationships in the active store context.

### Output rules

- Use `Response::structured()` or structured content with stable keys.
- Include IDs, human-readable labels, currency, timezone, and timestamps where relevant.
- Paginate lists and report `next_cursor` or pagination metadata.
- Return safe error codes such as `VALIDATION_FAILED`, `FORBIDDEN`, `NOT_FOUND`, `CONFLICT`, and `CONFIRMATION_REQUIRED`.
- Log internal exceptions with a correlation ID; return a safe message instead of the exception text.
- Redact passwords, secrets, internal tokens, payment credentials, and protected customer data.

## 9. Audit and Observability

Add a module-owned audit table, for example `nexopos_mcp_audit_events`, with guarded, repeat-safe migrations.

Suggested fields:

| Field | Purpose |
| --- | --- |
| `id` | Audit record identifier |
| `correlation_id` | End-to-end operation reference |
| `user_id` | Authenticated NexoPOS actor |
| `token_id` | Sanctum token used, never the token value |
| `store_id` / `register_id` | Operational scope when relevant |
| `tool_name` | Stable MCP tool name |
| `capability` | Read, write, or destructive group |
| `input_hash` | Hash of normalized arguments |
| `redacted_input` | Allowlisted, redacted diagnostic context |
| `status` | Success, denied, validation error, conflict, or failure |
| `result_reference` | Created/updated entity reference when safe |
| `duration_ms` | Execution duration |
| `created_at` | Event timestamp |

Never store bearer tokens, passwords, payment credentials, or arbitrary unredacted prompts. Establish retention and pruning settings before enabling writes.

Use separate read and write rate limiters. Writes and report generation should have lower limits than searches. Rate-limit keys should include the authenticated user/token and, where relevant, store context.

## 10. Tool and Resource Design Guidelines

- Keep tools narrow and intention-revealing: `search_products`, `preview_stock_adjustment`, `commit_stock_adjustment`.
- Prefer resources for stable reference data and tools for parameterized queries or actions.
- Add read-only, destructive, idempotent, and open-world annotations accurately.
- Keep tool descriptions operational and explicit about side effects.
- Use dependency injection for services and actions.
- Reuse Eloquent API resources or dedicated response mappers to control exposed fields.
- Cache only safe, store-aware reference data and invalidate it when source data changes.
- Do not cache permission-sensitive or customer-specific results globally.
- Reconcile the registered tool inventory with the server instructions in an automated test.

## 11. Migration from Core to Module

The endpoint should remain `/mcp/pos` to avoid breaking configured clients.

Recommended migration sequence:

1. Inventory every existing core tool/resource, its actual inputs, outputs, permissions, and tests.
2. Classify each as retain, redesign, defer, or remove.
3. Scaffold `NsOxen` using the NexoPOS module generator.
4. Implement module configuration, permissions, authorization, audit, and token management first.
5. Move Phase 1 read-only primitives into the module and preserve safe tool names/contracts.
6. Register the module server at `/mcp/pos` only when the module is enabled.
7. Remove the core route registration in the same release to avoid duplicate endpoints.
8. Move and strengthen tests under `modules/NsOxen/Tests` using the host test runner.
9. Add Phase 2 writes only after the read-only module is stable.
10. Deprecate unsafe or inconsistent existing contracts with explicit release notes rather than silently changing their meaning.

During the transition, there must be exactly one owner of `/mcp/pos`.

## 12. Testing Strategy

### Contract and unit coverage

- Tool names, descriptions, annotations, input schemas, and output schemas.
- Validation for required, unknown, malformed, oversized, and cross-store inputs.
- Stable structured response and error shapes.
- Capability-to-token-ability and capability-to-permission mapping.
- Audit redaction and normalized input hashing.
- Idempotency replay and key-conflict behavior.

### Feature coverage

- MCP discovery exposes only permitted and enabled tools/resources.
- Unauthenticated requests receive `401`.
- Missing token abilities and missing NexoPOS permissions are denied.
- Read, write, and destructive settings are independently enforced.
- Rate-limit behavior returns `429` at the configured boundary.
- Denied and invalid mutations produce no database or filesystem side effects.
- Store/register isolation is enforced.
- Preview tokens expire and cannot be reused incorrectly.
- Concurrent inventory/payment requests do not double-apply.
- Audit records exist for success, denial, conflict, and failure without secrets.

Use Laravel MCP's server test API with authenticated actors instead of only constructing tool handlers directly. Keep transport-level tests for middleware and throttling. Run module tests through the host runner:

```bash
php artisan test --compact modules/NsOxen/Tests
```

Use the MCP Inspector for manual discovery and client-compatibility checks, but do not run the blocking MCP server command as an automated test.

## 13. Delivery Milestones

### Milestone 0: decisions and inventory

- Confirm module name and ownership.
- Decide whether compatibility with existing tool names is required.
- Produce the tool/permission/ability matrix.
- Identify the authoritative NexoPOS service for each operation.
- Decide audit retention and write confirmation policy.

### Milestone 1: secure read-only module

- Scaffold and activate the module.
- Register `/mcp/pos` from the module.
- Add settings, scoped token issuance/revocation, permissions, and audit.
- Extract and harden Phase 1 tools/resources.
- Add authorization, discovery, transport, and contract tests.

### Milestone 2: managed writes

- Add category, selected product metadata, customer, and media writes.
- Introduce idempotency and write-specific rate limits.
- Add before/after audit details and side-effect tests.

### Milestone 3: transactional POS operations

- Add preview/commit workflows for inventory, orders, payments, refunds, and deletion.
- Enforce register and store context.
- Add locking, concurrency, recovery, and confirmation tests.

### Milestone 4: optional embedded assistant

- Add a permission-gated POS entry point.
- Provide a Codex/GPT-like streaming conversation and explicit tool-activity timeline.
- Add provider adapters without coupling Oxen tools to a specific model vendor.
- Use the existing POS Vue tree and supported hooks.
- Keep orchestration and credentials on the server.
- Require human review before applying consequential actions.

### Milestone 5: mobile platform and commercial entitlement

- Define tenant, merchant, device, and end-user identities.
- Add merchant-controlled pairing and revocation.
- Issue short-lived, audience-bound, capability-scoped credentials.
- Verify subscription/payment entitlement without replacing NexoPOS permissions.
- Add signed requests/webhooks, replay prevention, usage metering, and privacy controls.
- Decide whether installations accept inbound traffic or maintain an outbound connection to the platform.

## 14. Definition of Done for Version 1

Version 1 is complete when:

- MCP is owned entirely by the enabled module and `/mcp/pos` has one registration.
- Only Phase 1 read-only capabilities are enabled by default.
- Every primitive has complete schemas, accurate annotations, authorization, safe errors, and structured responses.
- Tokens are scoped and revocable, and their values are shown only at creation.
- Tool discovery respects module settings, token abilities, and NexoPOS permissions.
- Store isolation, bounded queries, rate limits, redaction, and auditing are tested.
- The dashboard provides configuration, token management, audit visibility, and connection instructions.
- Focused module PHPUnit tests pass and the MCP Inspector can authenticate, discover, and call an allowed tool.

## 15. Decisions Needed Before Implementation

1. Should existing `/mcp/pos` clients and current tool names remain backward compatible?
2. Is Sanctum token authentication sufficient for Version 1, or is third-party OAuth a launch requirement?
3. Which read-only domains belong in Version 1: catalog, customers, orders, reports, accounting, workforce, appointments, and multi-store?
4. Should Version 1 include any write capability, or should all writes wait for Milestone 2?
5. What audit retention period and privacy policy should apply to customer-related tool calls?
6. Which operations require two-step server confirmation in addition to client-side confirmation?
7. When should the optional Codex/GPT-like assistant become part of the Oxen roadmap?
8. Should the future mobile platform proxy every tool call, or only issue short-lived credentials for direct access?
9. Will the commercial platform be mandatory for mobile access or an optional hosted service alongside self-hosted access?
