# NexoPOS POS lifecycle

Use this reference when a module changes the live POS cart. Verify every extension point against `resources/ts/pos-init.ts` and a maintained module such as `modules/NsGastro` because these APIs can evolve.

## Contents

- [Loading a module script on the POS](#loading-a-module-script-on-the-pos)
- [Actions and filters](#actions-and-filters)
- [Lifecycle sequence](#lifecycle-sequence)
- [Cart buttons](#cart-buttons)
- [Initial queue](#initial-queue)
- [Custom order types](#custom-order-types)
- [Payment and submission](#payment-and-submission)
- [Complete cart script example](#complete-cart-script-example)

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
    header.buttons.ExampleHeaderButton = defineAsyncComponent(
        () => import('./components/HeaderButton.vue')
    );
});

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

This minimal module entry adds a cart button, adds a cart-header button, initializes module state on every reset, gates payment, and adds a synchronous field before submission:

```ts
import { markRaw } from 'vue';
import ExampleCartButton from './components/ExampleCartButton.vue';
import ExampleCartHeaderButton from './components/ExampleCartHeaderButton.vue';

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
