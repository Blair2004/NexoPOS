# NexoPOS frontend APIs

Use this reference for module TypeScript, Vue components, Blade-injected scripts, API requests, notifications, and localization. Verify behavior against `resources/ts/bootstrap.ts` and the relevant implementation under `resources/ts/libraries/`.

## Contents

- [`nsHttpClient`](#nshttpclient)
- [Observable and promise usage](#observable-and-promise-usage)
- [Errors and request state](#errors-and-request-state)
- [Frontend globals](#frontend-globals)
- [Localization](#localization)
- [Module declarations](#module-declarations)

## `nsHttpClient`

`nsHttpClient` is a global `HttpClient` instance backed by Axios. It returns cold RxJS Observables. A request starts when code subscribes.

```ts
declare const nsHttpClient: any;
declare const nsSnackBar: any;

const subscription = nsHttpClient.get('/api/example-module/items').subscribe({
    next: (body) => {
        // body is result.data from Axios, not the Axios response object.
        console.debug(body);
    },
    error: (error) => {
        nsSnackBar.error(error.message ?? 'Unable to load items.');
    },
});
```

Available methods:

```ts
nsHttpClient.get(url);
nsHttpClient.post(url, data, axiosConfig?);
nsHttpClient.put(url, data, axiosConfig?);
nsHttpClient.delete(url);
```

Use API URLs beginning with `/api/`. Module `Routes/api.php` is already mounted under that prefix, so define the route itself without another `api` prefix.

### Current configuration limitation

The live `_request()` implementation calls Axios uniformly as `client[type](url, data, config)`. This matches Axios `post` and `put`, but Axios `get` and `delete` accept configuration as their second argument. Consequently, the wrapper's documented `get(url, config)` and `delete(url, config)` configuration is not reliably forwarded in the current implementation.

- Do not rely on query `params`, custom headers, abort signals, or other per-request configuration passed to `nsHttpClient.get()` or `.delete()` until the wrapper is fixed and tested.
- Put simple query strings in the URL with `URLSearchParams`.
- Prefer a normal module API endpoint over bypassing `nsHttpClient` merely to work around the limitation.
- If a feature requires GET/DELETE configuration, fix the core wrapper deliberately with focused tests or obtain approval for a scoped alternative.

```ts
const query = new URLSearchParams({ page: '1', search: term });

nsHttpClient.get(`/api/example-module/items?${query.toString()}`).subscribe({
    next: (result) => useItems(result),
    error: (error) => nsSnackBar.error(error.message ?? 'Request failed.'),
});
```

`post` and `put` configuration is forwarded:

```ts
nsHttpClient.post('/api/example-module/items', payload, {
    headers: { Accept: 'application/json' },
}).subscribe({
    next: (response) => nsSnackBar.success(response.message),
    error: (error) => nsSnackBar.error(error.message ?? 'Unable to save.'),
});
```

Axios defaults enable same-origin credentials and the `X-Requested-With` header. Continue to protect routes and validate requests on the server.

## Observable and promise usage

Always subscribe to execute a request. Keep and unsubscribe long-lived subscriptions during component teardown. One-shot HTTP Observables complete after one response.

Use RxJS composition for dependent or parallel calls. When a NexoPOS lifecycle queue requires a promise, bridge the Observable explicitly:

```ts
import { firstValueFrom } from 'rxjs';

POS.initialQueue.push(async () => {
    const context = await firstValueFrom(
        nsHttpClient.get('/api/example-module/pos-context')
    );

    POS.set('exampleModuleContext', context);

    return { status: 'success', message: 'Context loaded' };
});
```

If surrounding code wraps `subscribe()` manually, make every path resolve or reject:

```ts
const result = await new Promise((resolve, reject) => {
    nsHttpClient.post('/api/example-module/action', payload).subscribe({
        next: resolve,
        error: reject,
    });
});
```

## Errors and request state

The error callback receives `error.response.data` when Axios provides it; otherwise it receives the Axios response or thrown error. Do not assume every error has the same shape.

```ts
function messageFrom(error: any): string {
    if (typeof error?.message === 'string') {
        return error.message;
    }

    if (error?.errors && typeof error.errors === 'object') {
        return Object.values(error.errors).flat().join('\n');
    }

    return 'An unexpected request error occurred.';
}
```

`nsHttpClient.response` exposes the last successful full Axios response globally. Do not use it to correlate concurrent requests.

`nsHttpClient.subject()` emits:

- `{ identifier: 'async.start', url, data }` before a request
- `{ identifier: 'async.stop' }` after success or failure

Use a counter rather than a boolean when tracking multiple concurrent requests. Do not monkey-patch the private `_request()` method.

The `http-client-url` frontend filter can synchronously transform every request URL:

```ts
nsHooks.addFilter('http-client-url', 'example-module/api-url', (url) => {
    return url;
});
```

Return the URL and keep global changes narrowly scoped.

## Frontend globals

Core globals include:

| Global | Purpose |
| --- | --- |
| `nsHttpClient` | Observable HTTP requests |
| `nsSnackBar` | Success, error, and informational toasts |
| `nsNotice` | Floating notices |
| `nsHooks` | WordPress-style frontend actions and filters |
| `Popup` | Popup component manager |
| `nsEvent` | NexoPOS event emitter |
| `RxJS` | RxJS exports for legacy/global code |
| `nsExtraComponents` | Extra Vue component registry where available |
| `ns.insertAfterKey`, `ns.insertBeforeKey` | Ordered object insertion |
| `nsCurrency`, `nsRawCurrency` | Currency formatting and raw numeric conversion |

Prefer explicit imports available through the module build when existing module code uses them. Use global declarations when integrating with globals injected by the core bundle.

## Localization

- Use `__('Text')` for core-owned strings.
- Use `__m('Text', 'ExampleModule')` for module-owned strings.
- Keep the module namespace identical to `config.xml` and existing `Lang/` usage.
- Treat API fields as localized objects only when their contract or comparable code shows that shape; do not assume every name or description is localized.
- When consuming a localized object, use `window.ns?.language`, then an English or first-value fallback.

```ts
function localized(value: string | Record<string, string>): string {
    if (typeof value === 'string') {
        return value;
    }

    const language = (window as any).ns?.language ?? 'en';

    return value?.[language] ?? value?.en ?? Object.values(value ?? {})[0] ?? '';
}
```

## Module declarations

Keep declarations small and aligned with APIs actually used:

```ts
declare const nsHttpClient: any;
declare const nsSnackBar: any;
declare const nsHooks: any;
declare const Popup: any;
declare const __: (text: string) => string;
declare const __m: (text: string, namespace: string) => string;

interface Window {
    ns?: { language?: string };
}
```

Do not copy a large ambient declaration template blindly. Prefer real imported types or a narrow module-owned `types.d.ts` as the module matures.
