# NexoPOS POS lifecycle (module mastery guide)

**Required reading for any module that touches the live POS cart.**  
Verify every extension point against `resources/ts/pos-init.ts` and a maintained module (`modules/NsGastro`, `modules/NsAppointments`) because these APIs can evolve.

This file is the POS chapter of the `create-nexopos-module` skill. Load it whenever the task mentions POS, cart, order type, payment queue, product row, unit price, line fee, or checkout.

## Contents

- [Decision matrix](#decision-matrix)
- [Source of truth](#source-of-truth)
- [Hooks cheat sheet](#hooks-cheat-sheet)
- [Loading a module script on the POS](#loading-a-module-script-on-the-pos)
- [Actions and filters](#actions-and-filters)
- [Lifecycle sequence](#lifecycle-sequence)
- [Cart buttons](#cart-buttons)
- [Product row components](#product-row-components)
- [Unit price vs once-per-line extra](#unit-price-vs-once-per-line-extra)
- [addToCartQueue and productData](#addtocartqueue-and-productdata)
- [Initial queue](#initial-queue)
- [Custom order types](#custom-order-types)
- [Payment and submission](#payment-and-submission)
- [Complete cart script example](#complete-cart-script-example)
- [Reference modules](#reference-modules)
- [Debugging checklist](#debugging-checklist)

## Decision matrix

| Need | Do this | Do not |
| --- | --- | --- |
| UI under a cart product (staff, room, notes) | Filter `ns-pos-product-row-components` + host Vue template | Raw HTML inject; nested `createApp` |
| Fee **per unit** (× quantity) | Unit price filters / mode `custom` | Put once-fees in unit price |
| Fee **once per line** (room, setup, cover) | Filter `ns-pos-product-line-extra` | Bake into unit price |
| Extra data when product is added | `POS.addToCartQueue` class | Only mutate `this.product` without merging `productData` |
| Cart bottom / header buttons | `POS.cartButtons` / `POS.cartHeaderButtons` on `ns-after-cart-reset` | Register only once and forget reset |
| Custom Walk-in / Booking type | PHP `ns-orders-types` + enable in options + optional `orderTypeQueue` | Compare `order.type` as a string |
| Validate before Pay | Class in `ns-pay-queue` | Async work in `ns-order-before-submit` expecting await |
| Attach fields on submit | `ns-order-before-submit` (sync only) | Unawaited HTTP in that action |

**Pricing rule of thumb**

```text
line_total = (unit_price × quantity − discount) + line_extra
order.subtotal = Σ line_total
```

- `unit_price` → multiplies with quantity  
- `line_extra` → once per cart line (tax base includes it)

## Source of truth

| Layer | Path |
| --- | --- |
| POS engine | `resources/ts/pos-init.ts` (`window.POS`) |
| POS app mount | `resources/ts/pos.ts` |
| Cart UI | `resources/ts/pages/dashboard/pos/ns-pos-cart.vue` |
| POS page | `resources/views/pages/dashboard/orders/pos.blade.php` |
| Example: buttons / kitchen | `modules/NsGastro` |
| Example: product-row + line-extra + order types | `modules/NsAppointments` |

After changing core POS sources, rebuild core assets (`npm run build`) or use Vite `public/hot`. Stale `public/build/assets/pos-init-*.js` / `ns-pos-cart-*.js` silently omit new hooks.

## Hooks cheat sheet

| Hook | Kind | When | Typical use |
| --- | --- | --- | --- |
| `ns-pos-header` | action | Header renders | Header buttons |
| `ns-before-cart-reset` | action | Before reset | Core restores default buttons |
| `ns-after-cart-reset` | action | After reset | Module cart/header buttons (priority ≥ 20) |
| `ns-after-cart-changed` | action | Products/order mutated | Core `refreshCart()` |
| `ns-cart-after-refreshed` | action | After refresh | Side effects on totals |
| `ns-pos-product-row-components` | filter | Each cart product row | Vue meta under product |
| `ns-pos-product-sale-price` | filter | Unit price (normal) | Adjust sale base (quantities) |
| `ns-pos-product-wholesale-price` | filter | Unit price (wholesale) | Adjust wholesale base |
| `ns-pos-product-custom-price` | filter | Unit price (custom) | Adjust custom base (quantities) |
| `ns-pos-product-unit-price` | filter | Final unit before × qty | Last unit adjustment (**product** arg) |
| `ns-pos-product-line-extra` | filter | Once-per-line fee | Room/setup fee (**product** arg) |
| `ns-after-product-computed` | action | After line totals | Observe only; prefer filters for money |
| `ns-pay-queue` | filter | Pay click | Insert validation/collection classes |
| `ns-order-before-submit` | action | Immediately before POST | Sync field attach |
| `ns-order-submit-successful` / `failed` | action | After HTTP | Notifications, cleanup |

PHP (server): `Hook::addFilter('ns-orders-types', …)` for order type catalog.

## Loading a module script on the POS

Inject a module view only on the POS route with `RenderFooterEvent`:

```php
use App\Events\RenderFooterEvent;

final class RenderFooterEventListener
{
    public function handle(RenderFooterEvent $event): void
    {
        if ($event->routeName === ns()->routeName('ns.dashboard.pos')) {
            $event->output->addView('ExampleModule::pos.footer');
        }
    }
}
```

Load the module entry from `Resources/Views/pos/footer.blade.php`:

```blade
@moduleViteAssets('Resources/ts/pos.ts', 'ExampleModule')
```

Do not use the removed dashboard-footer string hook. Follow the repository's listener discovery convention and use `@moduleViteAssets`, not `@vite`, for module assets.

## Actions and filters

Frontend hooks are created with `@wordpress/hooks` and exposed as `window.nsHooks`.

- Use an **action** for notification or in-place mutation when no returned value is consumed: `addAction(name, namespace, callback, priority?)` and `doAction(name, ...args)`.
- Use a **filter** to replace or transform a value: `addFilter(name, namespace, callback, priority?)` and `applyFilters(name, initialValue, ...args)`.
- Return the transformed value from every filter callback.
- Give each registration a stable module-specific namespace.
- Use priority to order callbacks; lower priorities run earlier. Core defaults generally use priority `10`.
- Remove long-lived hooks during component teardown when a component may mount repeatedly.
- Never place awaited work in an action callback and assume the caller waits. `doAction()` is synchronous from the POS caller's perspective.

```ts
nsHooks.addAction('ns-order-submit-successful', 'example-module/after-sale', (response) => {
    console.debug('Order stored', response.data.order);
});

nsHooks.addFilter('ns-pay-queue', 'example-module/payment-gate', (queues) => {
    queues.splice(queues.length - 1, 0, ExamplePaymentGate);
    return queues;
});
```

## Lifecycle sequence

The relevant flow is:

1. `pos-init.ts` creates `window.POS` and its BehaviorSubjects and default queues.
2. The POS Blade page defines types, options, settings, and payment types on `DOMContentLoaded`.
3. `<ns-pos>` mounts, waits 500 ms, then calls `POS.reset()`.
4. `reset()` emits `ns-before-cart-reset`, runs `processInitialQueue()` sequentially, then emits `ns-after-cart-changed` and `ns-after-cart-reset`.
5. Product/cart mutations emit `ns-after-cart-changed`; the core listener calls `refreshCart()`, which eventually emits `ns-cart-after-refreshed`.
6. The Pay button calls `POS.runPaymentQueue()`.
7. The payment UI eventually calls `POS.submitOrder()` and `proceedSubmitting()`.
8. Submission emits `ns-order-before-submit`, performs the HTTP request, then emits `ns-order-submit-successful` or `ns-order-submit-failed`.

`reset()` runs after a completed sale and can run manually. Treat initialization and button registration as repeatable.

## Cart buttons

There are three distinct button regions:

| Region | Extension point | Component props |
| --- | --- | --- |
| Main POS header | Mutate `header.buttons` in `ns-pos-header` | None |
| Cart toolbox/header | Update `POS.cartHeaderButtons` | `order`, `settings`, `options` |
| Cart bottom actions | Update `POS.cartButtons` | `order`, `settings` |

The cart component restores both cart button collections during `ns-before-cart-reset`. Apply module changes from `ns-after-cart-reset` with a priority after core, such as `20`, so they survive every reset.

```ts
import { defineAsyncComponent, markRaw } from 'vue';
import CartButton from './components/CartButton.vue';
import CartHeaderButton from './components/CartHeaderButton.vue';

declare const POS: any;
declare const nsHooks: any;

nsHooks.addAction('ns-pos-header', 'example-module/header-button', (header) => {
    // Prefer Options API + string `template` for POS-hosted header buttons (dual-Vue).
    // Use __m('Label', 'ExampleModule') for every user-facing string (scanner + globals).
    header.buttons.ExampleHeaderButton = defineAsyncComponent(
        () => import('./components/HeaderButton')
    );
});

// Example: open a full-viewport module popup (floor ops) without leaving POS:
// Popup.show(OpsFullscreenPopup, { … }, { closeOnOverlayClick: false });
// with shell classes like `w-[100vw] h-[100dvh] max-w-[100vw]`.

nsHooks.addAction('ns-after-cart-reset', 'example-module/cart-buttons', () => {
    POS.cartButtons.next({
        ...POS.cartButtons.getValue(),
        ExampleCartButton: markRaw(CartButton),
    });

    POS.cartHeaderButtons.next({
        ...POS.cartHeaderButtons.getValue(),
        ExampleCartHeaderButton: markRaw(CartHeaderButton),
    });
}, 20);
```

To position a button relative to a core key, use the existing object helpers such as `ns.insertAfterKey` or `ns.insertBeforeKey`. Do not mutate an object without calling `.next(...)`; subscribers need the BehaviorSubject emission.

## Product row components

**Hybrid extension point (required pattern for custom cart-line UI).**

When a module needs display or editable meta under a cart product (staff, room, seat, notes, modifiers, etc.), register a **Vue SFC** via the filter below. Do **not**:

- Inject raw HTML strings into the cart product row
- Nest `createApp()` / `nsCreateApp()` inside the cart DOM (POS already owns the Vue tree)
- Mutate the product object without `POS.updateProduct` (the cart will not refresh correctly)

Core (`resources/ts/pages/dashboard/pos/ns-pos-cart.vue`) renders a meta slot under each product and applies:

```ts
nsHooks.applyFilters('ns-pos-product-row-components', {}, product, index)
```

For every key in the returned object map, core mounts `<component :is="…" :product :index :order :options :settings />` inside a flex meta strip.

### When to use

| Need | Approach |
| --- | --- |
| Show/edit per-line module fields under a product | **`ns-pos-product-row-components`** (this section) |
| Cart bottom / header actions | `POS.cartButtons` / `POS.cartHeaderButtons` on `ns-after-cart-reset` |
| Collect data when the product is first added | `POS.addToCartQueue` |
| Order-level type fields | `POS.orderTypeQueue` + server `ns-orders-types` |

### Register the filter once at module boot

Load the POS entry with `@moduleViteAssets` on the POS route (see [Loading a module script on the POS](#loading-a-module-script-on-the-pos)). Register from that entry (e.g. `DOMContentLoaded` or a class method called once):

```ts
import { markRaw } from 'vue';
import ExampleCartMeta from './components/ExampleCartMeta.vue';

declare const nsHooks: any;

nsHooks.addFilter(
    'ns-pos-product-row-components',
    'example-module/cart-meta', // stable module-specific namespace
    (components, product, index) => {
        // Gate on flags set when the line is added (addToCartQueue / product queues).
        if (product?.example_is_service === true) {
            // markRaw is required — these are component constructors, not reactive data.
            components.ExampleCartMeta = markRaw(ExampleCartMeta);
        }

        return components; // always return the map
    }
);
```

- Use a **filter**, not an action: the return value is the component map.
- Always `return components`.
- Use `markRaw()` on every component constructor.
- Gate with a product flag (boolean, type, or module prefix field) so non-matching lines stay clean.
- Multiple modules may each add keys; keep keys unique (`ModuleNameCartMeta`).

Reference implementation: `modules/NsAppointments/Resources/ts/pos.ts` (`registerProductRowMeta`) + `AppointmentsCartMeta.ts` (string template).

### Component contract

| Prop | Required | Description |
| --- | --- | --- |
| `product` | yes | Cart line (includes custom fields from `addToCartQueue`) |
| `index` | yes | Index in `POS.products` — pass to `POS.updateProduct` |
| `order` | no | Current order object |
| `options` | no | POS options |
| `settings` | no | POS settings |

Minimal component shape:

```ts
// Resources/ts/components/ExampleCartMeta.vue
export default {
    name: 'ExampleCartMeta',
    props: {
        product: { type: Object, required: true },
        index: { type: Number, required: true },
        order: { type: Object, required: false },
    },
    methods: {
        applyPatch(patch: Record<string, unknown>) {
            POS.updateProduct(this.product, patch, this.index);
        },
    },
};
```

### Data flow

1. **Write custom fields when the product enters the cart** (`POS.addToCartQueue` or product-selection queue): e.g. `ns_appointment_service`, `ns_appointment_worker_id`, `ns_appointment_worker_name`, room fields.
2. **Display** those fields in the row component (computed labels from `product.*`).
3. **Edit** with popups / selects / API calls, then **`POS.updateProduct(product, patch, index)`** so core merges the patch and re-renders.
4. **Persist** the same keys server-side on order submit (validation, product meta, appointment records). Frontend-only fields will be lost.

```ts
POS.updateProduct(this.product, {
    example_worker_id: selected.id,
    example_worker_name: selected.name,
}, this.index);
```

### Shared Vue + theming

- Import from `'vue'` only through the shared runtime (`defineNexoPOSModuleConfig` / `nexoposVueRuntime()`). Never bundle a second Vue into the POS entry.
- Style module controls with the module Tailwind prefix and semantic tokens; reuse `<ns-button>` / `.ns-button` wrappers where appropriate. See [module-frontend.md](module-frontend.md).

### Dual-Vue caveat for POS product-row components

Core POS (`pos.ts` / `posApp`) still creates its app from a **bundled** Vue copy. Module SFCs are compiled against `window.NexoPOSVue`. A precompiled SFC passed into the cart via `:is` may **never mount** (no `mounted` log).

For product-row components injected into `ns-pos-cart`, prefer **Options API + string `template`** (compiled by the host POS Vue), same pattern as NsGastro cart buttons. See `modules/NsAppointments/Resources/ts/components/AppointmentsCartMeta.ts`.

Popup hosts can have the same issue; Gastro-style templates or aligning POS with the shared runtime avoids it.

### Core build required

`ns-pos-cart.vue` must be present in the **built** core assets (`public/build/assets/ns-pos-cart-*.js`). If that chunk is stale, the filter registers but nothing renders. After changing the cart host, run a core `npm run build` (or Vite dev with `public/hot`).

### Unit price vs once-per-line extra

**Change unit price** (multiplied by quantity):

| Filter | Args | Notes |
| --- | --- | --- |
| `ns-pos-product-sale-price` | `(price, quantities)` | normal mode |
| `ns-pos-product-wholesale-price` | `(price, quantities)` | wholesale |
| `ns-pos-product-custom-price` | `(price, quantities)` | custom mode — quantities only, not full product |
| `ns-pos-product-unit-price` | `(unitPrice, product)` | final unit before × qty |

**Add an amount once per line** (not × quantity) — e.g. room fee on a service:

```ts
nsHooks.addFilter(
  'ns-pos-product-line-extra',
  'example-module/line-fee',
  (extra, product) => {
    if (!product?.example_has_fee) {
      return extra;
    }

    return Number(extra) + Number(product.example_fee || 0);
  }
);
```

Core path (`pos-init.ts` → `computeProduct`):

```ts
unitPrice = applyFilters('ns-pos-product-unit-price', product.unit_price, product)
product.line_extra = applyFilters('ns-pos-product-line-extra', 0, product)
// tax base includes line_extra
product.total_price = unitPrice * quantity - discount + product.line_extra
```

Do **not** bake once-fees into unit price (quantity would multiply them).

**Example (shared room on a service line):**

```text
Service unit 115 × qty 2 + room line_extra 45 = total 275
```

Store room fee on the product (`ns_appointment_room_price`); keep unit price as service-only.

Reference: `modules/NsAppointments` — filter `ns-appointments/room-fee` on `ns-pos-product-line-extra`.

## addToCartQueue and productData

`POS.addToCart()` runs queue classes sequentially. Each class is constructed with `cartProduct` but results accumulate in **`productData`**, then merge:

```ts
productData = { ...productData, ...await queue.run(productData) }
cartProduct = { ...cartProduct, ...productData }
```

Unit selection returns `$quantities` **into productData**, not by mutating `this.product`.

When your queue needs sale price or `$quantities()`, merge first:

```ts
async run(productData: Record<string, any> = {}) {
  const source = {
    ...this.product,
    ...productData,
    $quantities: productData.$quantities ?? this.product.$quantities,
  };
  // read source.$quantities(), return fields to merge
}
```

If you only read `this.product`, service/unit price often looks like **0** and later fees appear alone as the unit price.

## Initial queue

`POS.initialQueue` contains functions returning promises. `processInitialQueue()` awaits them sequentially and races each entry against a 60-second timeout. The queue runs during every `POS.reset()`, not only the first page load.

Register before the first reset, normally from the module entry's `DOMContentLoaded` handler:

```ts
document.addEventListener('DOMContentLoaded', () => {
    POS.initialQueue.push(async () => {
        const response = await fetch('/api/example-module/pos-context', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Unable to initialize Example Module on the POS.');
        }

        POS.set('exampleModule', await response.json());

        return {
            status: 'success',
            message: 'Example Module initialized',
        };
    });
});
```

Prefer `nsHttpClient` when matching existing module code. Ensure every observable path resolves or rejects the wrapping promise. Keep the task idempotent, avoid registering the same queue twice, and do not use the initial queue for one-time irreversible work.

## Custom order types

A complete custom order type has a server definition and, when it needs extra data, a frontend selection processor.

In the live POS order, `order.type` is the selected order-type object, not its identifier string:

```ts
type PosOrderType = {
    icon: string;
    identifier: string;
    label: string;
    selected: boolean;
};
```

Compare `order.type.identifier` with an identifier string in product, payment, and submission queues. Guard a missing selection when necessary:

```ts
if (order.type?.identifier !== 'curbside') {
    return true;
}
```

Do not compare `order.type === 'curbside'`, and do not return `{ type: selectedType.identifier }` from an order-type selection processor. Core updates `order.type` with the selected object; processors should return only their additional order fields.

Register the server type through the PHP `ns-orders-types` filter so the POS controller can include it:

```php
use App\Classes\Hook;

Hook::addFilter('ns-orders-types', static function (array $types): array {
    $types['curbside'] = [
        'identifier' => 'curbside',
        'label' => __m('Curbside', 'ExampleModule'),
        'icon' => asset('modules/examplemodule/images/curbside.svg'),
        'selected' => false,
    ];

    return $types;
});
```

The type must also be enabled in `ns_pos_order_types`; otherwise the POS controller filters it out. Follow the module's installation/settings pattern rather than silently overwriting an administrator's existing selection.

Add a frontend processor only if choosing the type must collect or compute fields:

```ts
POS.orderTypeQueue.push({
    identifier: 'example-module/curbside',
    promise: async (selectedType) => {
        if (selectedType.identifier !== 'curbside') {
            return {};
        }

        const pickup = await openPickupDetailsPopup();

        return {
            curbside_vehicle: pickup.vehicle,
            curbside_slot: pickup.slot,
        };
    },
});
```

`triggerOrderTypeSelection()` awaits each processor, merges every returned key into the order, and then emits the updated order. Resolve with an object; reject to cancel selection. Ensure custom attributes are accepted, validated, stored, and returned by the backend as well.

## Payment and submission

### Payment-button queue

Clicking Pay invokes `runPaymentQueue()`. It filters this default class list through `ns-pay-queue`:

```text
ProductsQueue -> CustomerQueue -> TypeQueue -> PaymentQueue
```

Each class receives the current order in its constructor and exposes `run(): Promise<any>`. Queues run sequentially. A rejection stops the flow; resolving continues it.

```ts
class ExamplePaymentGate {
    constructor(private order: any) {}

    async run(): Promise<boolean> {
        if (this.order.total > 1000 && !this.order.approval_code) {
            throw new Error('Manager approval is required.');
        }

        return true;
    }
}

nsHooks.addFilter('ns-pay-queue', 'example-module/payment-gate', (queues) => {
    const paymentIndex = queues.indexOf(PaymentQueue);
    queues.splice(paymentIndex, 0, ExamplePaymentGate);
    return queues;
});
```

Insert before `PaymentQueue` to validate before opening payment. Append after it only when the payment popup's queue promise is intentionally resolved by that workflow.

### Actual order submission

There is currently no separate asynchronous submission promise queue in `pos-init.ts`.

- Use `ns-pay-queue` for awaited validation or preparation before the payment UI.
- Use `ns-order-before-submit` only for synchronous in-place mutation of the order immediately before the POST/PUT request.
- Use `ns-order-submit-successful` for post-success effects.
- Use `ns-order-submit-failed` for failure reporting or recovery.

```ts
nsHooks.addAction('ns-order-before-submit', 'example-module/order-fields', (order) => {
    order.example_reference = POS.get('exampleReference') ?? null;
});

nsHooks.addAction('ns-order-submit-successful', 'example-module/submitted', (response) => {
    console.debug('Submitted order', response.data.order);
});
```

Do not start an asynchronous request inside `ns-order-before-submit` and expect submission to wait. If async work must happen immediately before the HTTP request, place it in an `ns-pay-queue` class or change the core API deliberately with tests.

## Complete cart script example

This minimal module entry adds cart buttons, product-row meta under matching lines, initializes state on every reset, gates payment, and adds a synchronous field before submission:

```ts
import { markRaw } from 'vue';
import ExampleCartButton from './components/ExampleCartButton.vue';
import ExampleCartHeaderButton from './components/ExampleCartHeaderButton.vue';
import ExampleCartMeta from './components/ExampleCartMeta.vue';

declare const POS: any;
declare const PaymentQueue: any;
declare const nsHooks: any;

class RequireReferenceQueue {
    constructor(private order: any) {}

    async run(): Promise<boolean> {
        if (!POS.get('exampleReference')) {
            throw new Error('Choose a reference before payment.');
        }

        return true;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    POS.initialQueue.push(async () => {
        POS.set('exampleReference', null);
        return { status: 'success', message: 'Example cart state reset' };
    });

    nsHooks.addAction('ns-after-cart-reset', 'example-module/buttons', () => {
        POS.cartButtons.next({
            ...POS.cartButtons.getValue(),
            ExampleCartButton: markRaw(ExampleCartButton),
        });

        POS.cartHeaderButtons.next({
            ...POS.cartHeaderButtons.getValue(),
            ExampleCartHeaderButton: markRaw(ExampleCartHeaderButton),
        });
    }, 20);

    // Hybrid product-row UI (display + edit custom line meta).
    nsHooks.addFilter(
        'ns-pos-product-row-components',
        'example-module/cart-meta',
        (components, product) => {
            if (product?.example_is_service === true) {
                components.ExampleCartMeta = markRaw(ExampleCartMeta);
            }

            return components;
        }
    );

    // Once-per-line fee (e.g. room) — not multiplied by quantity.
    nsHooks.addFilter(
        'ns-pos-product-line-extra',
        'example-module/line-fee',
        (extra, product) => {
            if (product?.example_is_service !== true) {
                return extra;
            }

            return Number(extra) + Number(product.example_room_price || 0);
        }
    );

    nsHooks.addFilter('ns-pay-queue', 'example-module/require-reference', (queues) => {
        const paymentIndex = queues.indexOf(PaymentQueue);
        queues.splice(paymentIndex, 0, RequireReferenceQueue);
        return queues;
    });

    nsHooks.addAction('ns-order-before-submit', 'example-module/reference', (order) => {
        order.example_reference = POS.get('exampleReference');
    });
});
```

Back this script with server-side authorization, validation, persistence, and focused tests. Frontend checks are user experience, not a security boundary.

## Reference modules

| Module | What to copy |
| --- | --- |
| `modules/NsGastro` | Cart/header buttons, `markRaw`, kitchen flows, string-template cart buttons |
| `modules/NsAppointments` | POS footer inject, `addToCartQueue`, product-row meta (runtime template), `ns-pos-product-line-extra` room fee, Walk-in/Booking order types, pay queue |

Key Appointments files:

- `Resources/ts/pos.ts` — boot, filters, queues  
- `Resources/ts/queues/ServiceProductQueue.ts` — add-to-cart meta  
- `Resources/ts/components/AppointmentsCartMeta.ts` — product-row UI (string `template`)  
- `Resources/ts/product-meta.ts` — room/staff snapshots + line-extra helper  
- `Listeners/RenderPosBookingAssets.php` + `Resources/Views/pos/footer.blade.php`

## Persist cart fields on order products (round-trip)

Cart product keys that must survive save → hold/edit → load **should match `nexopos_orders_products` columns**.

Why:

1. POS submits the full product object on the order.
2. Core only copies known product columns + flash `setData()` (request-scoped, not DB).
3. Module migrations add columns; before create/update, copy payload fields onto the model (same names).
4. `loadOrder` returns those columns on each product; product-row UI rebuilds without re-prompting.

Pattern (see NsAppointments):

| Layer | Responsibility |
| --- | --- |
| Frontend | Set `ns_appointment_*` on the cart line |
| Migration | Add matching columns on `nexopos_orders_products` |
| Before create/update listener | `fillOrderProductFromCartPayload` from `getData()` |
| `serviceProducts` / domain | Read model attributes (and flash for same-request) |
| Casts | `OrderProduct::mergeCasts([...])` for bool/int/json |

Do **not** rely only on `getData()` for persistence — it is flash memory for the save request.

Optional list fields (e.g. full staff options array) stay client-only; re-fetch when editing.

## Debugging checklist

1. **Module script loaded?** POS route only; `@moduleViteAssets('Resources/ts/pos.ts', …)`; no stale `Public/hot` pointing at a dead Vite server.  
2. **Boot ran?** Order types enabled; `POS.get('…Booted')` not blocking forever after a failed first load.  
3. **Product flag present?** `product.ns_appointment_service === true` (or your flag) on cart lines.  
4. **Product-row not mounting?** Core `ns-pos-cart` build includes the meta slot; dual-Vue → use Options API + string `template`.  
5. **Unit price wrong / only fee shows?** Queue must merge `productData` (see [addToCartQueue and productData](#addtocartqueue-and-productdata)).  
6. **Fee multiplies with qty?** Move fee to `ns-pos-product-line-extra`, not unit price.  
7. **Stale core hooks?** Rebuild `pos-init` / cart chunks or enable Vite HMR.
