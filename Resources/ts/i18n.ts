/**
 * Exposes the NexoPOS module translator as a real Vue component binding.
 */
export function __m(text: string, namespace: string): string {
    const runtime = typeof globalThis !== 'undefined'
        ? (globalThis as typeof globalThis & { __m?: (value: string, module: string) => string })
        : undefined;

    return typeof runtime?.__m === 'function'
        ? runtime.__m(text, namespace)
        : text;
}
