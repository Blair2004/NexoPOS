# Oxen — Secure AI Store Assistant for NexoPOS

Oxen (`NsOxen`) brings permission-aware AI assistance into NexoPOS. It helps users understand live store data, perform supported management tasks through an approval workflow, and connect external AI clients through MCP—all while preserving NexoPOS permissions, store isolation, validation, and auditability.

## Module information

| Property | Value |
| --- | --- |
| Display name | Oxen |
| Namespace | `NsOxen` |
| Version | `2.0.0` |
| Author | NexoPOS |
| Current AI provider | OpenAI |
| MCP endpoint | `/mcp/oxen` |
| Administration | Settings → Oxen Settings |

## What Oxen delivers

- A conversational assistant available from the NexoPOS dashboard.
- Answers grounded in current products, customers, orders, inventory, taxes, payments, and other store data.
- Live activity updates as the assistant starts, completes, or fails tool calls.
- Approval-controlled write operations with permission rechecks and idempotent execution.
- Shared tools for the embedded assistant and authenticated MCP clients.
- Usage controls, operation auditing, and secure credential storage.

## Embedded assistant

The dashboard assistant provides a persistent conversation experience without requiring users to leave their current workflow. It supports:

- Conversation history and contextual follow-up questions.
- Provider-side response chaining and automatic compaction for faster long-running conversations.
- Awareness of the current dashboard route and supported business entities.
- File and image attachments where supported.
- Progressive tool activity while the assistant works.
- Safe Markdown rendering and sanitized output.
- Proposal cards for reviewing, approving, rejecting, or retrying actions.

The assistant only sees tools the current user is allowed to use. Store identity and authorization are resolved by the application and are never accepted from model-generated arguments.

## Store intelligence

Oxen can ground conversations across the main areas of a NexoPOS store:

| Area | Examples |
| --- | --- |
| Catalog and inventory | Products, categories, units, stock quantities, stock history, low-stock items, and product performance |
| Customers and promotions | Customers, customer groups, rewards, wallets, coupons, and eligibility rules |
| Sales and orders | Orders, payment totals, cashier rankings, refunds, installments, and period comparisons |
| Finance | Transactions, accounts, profit summaries, yearly sales, and operational totals |
| Purchasing and operations | Providers, procurements, registers, register history, taxes, and tax groups |
| Store configuration | Store settings, media library, branding assets, and logo assignments |

## Safe store management

Supported management actions include creating and updating products, categories, providers, customers, customer groups, units, unit groups, taxes, tax groups, and coupons. Oxen can also import products, adjust product quantities and unit settings, upload or replace media, generate product imagery and store logos, update approved store settings, and generate downloadable reports.

Every mutation is handled as a proposal. The model can prepare an action, but it cannot apply the change directly. An authorized user must approve the proposal before Oxen executes it.

Destructive or externally billable actions receive an additional confirmation guard. This includes operations such as deleting media, replacing binary media content, and generating images.

## Approval and security model

Oxen follows a controlled execution flow:

1. A user asks the assistant to inspect or change store data.
2. Oxen exposes only tools allowed by the user's NexoPOS permissions.
3. Read tools execute and return grounded data; write tools create an opaque proposal.
4. The user reviews and approves the proposed action.
5. Oxen rechecks the user, store, permissions, proposal integrity, and expiration before execution.
6. The operation runs idempotently and its outcome is recorded for audit.

Additional safeguards include:

- Encrypted storage for provider credentials.
- Strict schemas and rejection of unexpected tool arguments.
- Validation against authoritative NexoPOS services, models, and relationships.
- Store-scoped access and trusted application context.
- Redaction of sensitive values from logs and model-facing data.
- Rate limits, daily message limits, token limits, and per-turn tool-call limits.
- Replay protection and conflict detection for approved operations.

## Media and branding

Oxen can upload new media and replace the contents of existing media without breaking its stored identity or references. Replacements preserve the media ID, slug, extension, and URL, validate the detected file type, regenerate image thumbnails, and use a staged filesystem swap so the original remains intact if processing fails.

Store-logo generation creates two transparent PNG assets:

- A square logo for `ns_store_square_logo`.
- A landscape logo for `ns_store_rectangle_logo` and `ns_invoice_receipt_logo`.

The existing logo configuration remains unchanged until both images have been generated, validated, and uploaded successfully.

## Reports and exports

The assistant can prepare supported operational reports and exports in HTML, PDF, or CSV format. Generated files use temporary signed download links so access remains time-bound and controlled by the application.

## MCP integration

Oxen exposes the same permission-aware tool registry through its embedded assistant and the Laravel MCP server at `/mcp/oxen`. MCP connections use Sanctum authentication, Oxen token abilities, connection checks, and the current NexoPOS store context.

This shared registry keeps validation, authorization, proposal handling, idempotency, and auditing consistent regardless of which supported client invokes a tool.

## Permissions

| Permission | Purpose |
| --- | --- |
| `ns.oxen.use` | Use the embedded assistant and permitted read tools |
| `ns.oxen.manage` | Configure Oxen settings |
| `ns.oxen.manage-tokens` | Create and manage MCP access tokens |
| `ns.oxen.view-audit` | Review usage and operation audit records |
| `ns.oxen.use-writes` | Prepare and approve supported write proposals |
| `ns.oxen.use-destructive` | Approve operations classified as destructive |

Resource-specific NexoPOS permissions still apply. Granting an Oxen permission does not bypass the permission required to manage products, taxes, coupons, customers, media, settings, or any other underlying resource.

## Configuration

After installing and activating the module:

1. Assign the appropriate Oxen permissions to the intended NexoPOS roles.
2. Open **Settings → Oxen Settings**.
3. Configure the OpenAI API key and select the text and image models.
4. Set daily message, output-token, context-token, and per-turn tool-call limits.
5. Enable the assistant for authorized users.
6. Enable write proposals only when store-management actions are required.
7. Use the available connection test before relying on the integration.

The architecture is provider-ready, while the current integration uses OpenAI for assistant responses and image generation.

## Architecture

```text
Dashboard assistant ─┐
                     ├─→ Tool registry ─→ authorization and validation ─→ NexoPOS services
MCP clients ─────────┘                       │
                                            └─→ proposals, idempotency, and audit
```

External NexoPOS modules can register additional tools through Oxen's extension API. See [Extending Oxen tools](Documentation/tools.md) for the tool contract, registration examples, and proposal workflow.

## Development

Run the focused module tests with:

```bash
php artisan test --compact --no-coverage modules/NsOxen/Tests
```

Format changed PHP files with:

```bash
vendor/bin/pint --dirty --format agent
```

When frontend assets change, build them from the project root with the repository's configured npm build command.
