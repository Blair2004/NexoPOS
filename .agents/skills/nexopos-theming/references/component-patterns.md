# Theme-aware component patterns

## Visual role mapping

| Intent | Preferred utilities |
|---|---|
| Normal/supporting text | `text-fontcolor`, `text-fontcolor-soft` |
| Card/panel | `bg-box-background border-box-edge text-fontcolor` |
| Nested/raised/hoverable panel | `bg-box-elevation-background border-box-elevation-edge hover:bg-box-elevation-hover` |
| Popup | `bg-popup-surface text-fontcolor` |
| Field | `bg-input-background border-input-edge text-fontcolor` |
| Field button | `bg-input-button hover:bg-input-button-hover active:bg-input-button-active` |
| Floating results | `bg-floating-menu border-floating-menu-edge hover:bg-floating-menu-hover` |
| Brand action | `bg-primary hover:bg-secondary text-white` |
| Status (solid active fill) | `bg-*-primary text-white` or `bg-*-secondary text-white` |
| Status foreground emphasis | `text-*-tertiary` or `border-*-tertiary` on a neutral surface; **never** `bg-*-tertiary` |
| Status text / icons / borders | `text-*-primary`, `border-*-primary` / `border-*-secondary` |

Use component-specific tokens inside tabs, tables, POS, CRUD, or numpad UI.

## Card and panel

```vue
<article class="rounded-lg border border-box-edge bg-box-background p-4 text-fontcolor shadow">
    <header class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <h3 class="truncate font-semibold">{{ title }}</h3>
            <p class="mt-1 text-sm text-fontcolor-soft">{{ description }}</p>
        </div>
        <slot name="actions" />
    </header>
    <div class="mt-4"><slot /></div>
</article>
```

Do not add a wrapper component if `ns-box` fits.

## Form control

Reuse `ns-input`, `ns-select`, `ns-textarea`, `ns-checkbox`, or another existing field. For a necessary custom control:

```html
<label class="block text-sm font-medium text-fontcolor" for="reference">Reference</label>
<input id="reference" class="mt-1 h-10 w-full rounded-md border border-input-edge bg-input-background px-3 text-fontcolor outline-none placeholder:text-fontcolor-soft focus:border-primary focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:bg-input-disabled">
```

Use native `disabled` and an associated label. Match `has-error`/`is-pristine` when integrating NexoPOS validation.

## Buttons and feedback

Prefer `ns-button` and its established types: `primary`, `success`, `error`, `warning`, `default`, and `info`. Prefer `ns-icon-button` or `ns-inset-button` for compact actions. Reuse `ns-notice` for alerts.

Theme CSS targets **`.ns-button button` and `.ns-button a`**. Do not put `ns-button` only on the interactive element.

```html
<!-- Vue component (preferred in SFCs when registered) -->
<ns-button type="info" href="/dashboard/example">Open</ns-button>

<!-- Hook wrapper (Blade / manual markup) — theme owns color; you own shape -->
<div class="ns-button info">
    <a href="/dashboard/example" class="rounded-lg px-3 py-2">Open</a>
</div>

<div class="ns-button default">
    <button type="button" class="rounded-lg px-3 py-2">Refresh</button>
</div>
```

Wrong: `<a class="ns-button info">…</a>` or `<button class="ns-button default">…</button>`.

For standalone color maps when the hook is inappropriate, use a literal class map:

```ts
const variantClasses = {
    primary: 'bg-primary text-white hover:bg-secondary',
    success: 'bg-success-primary text-white hover:bg-success-secondary',
    warning: 'bg-warning-primary text-white hover:bg-warning-secondary',
    error: 'bg-error-primary text-white hover:bg-error-secondary',
} as const;
```

Do not interpolate class fragments. Pair color with text/icons for status. Inspect actual contrast: status suffixes do not guarantee universal luminance across themes.

### Typography

- Default headings and body: `text-fontcolor`
- Sublines, field descriptions, meta, empty hints: `text-fontcolor-soft`

### Loading

- Always pass `ns-spinner` `size` (and usually `border`) so the spinner is not oversized.
- Optional loading label goes **below** the spinner (`flex-col`), not beside it, for panel/page loads.
- On failure, replace the spinner with an error message (and retry if useful); never leave a permanent spinning state.

Module-specific prefix rules: `create-nexopos-module` → `references/module-frontend.md`.

## Tabs and tables

Reuse `ns-tabs`, `ns-tabs-item`, and `ns-table`. Use `tab-*` for tab chrome and `table-*` for standalone tables. Use existing status row classes instead of restyling every cell. Compare all theme files before changing them.

## Vue state classes

Keep classes statically discoverable:

```vue
<div :class="isSelected
    ? 'border-primary bg-primary/10 text-fontcolor'
    : 'border-box-edge bg-box-background text-fontcolor hover:bg-box-elevation-hover'">
</div>
```

Avoid ``:class="`border-${color}-500 bg-${color}-100`"``. Prefer controlled variant props over arbitrary consumer classes that conflict with the component contract.

## Global hook pattern

Use global theme CSS for descendant/state contracts:

```css
.ns-example {
    @apply border-box-edge bg-box-background text-fontcolor;

    &.selected {
        @apply border-primary bg-box-elevation-background;
    }

    .ns-example-description {
        @apply text-fontcolor-soft;
    }
}
```

Add the same hook to every theme file. Extract genuinely identical rules to a shared file when appropriate, but do not reorganize theme architecture during an unrelated task.

## Verification

1. Run focused component or feature tests and `npm run build`.
2. Search changed UI files: `rg -n '(gray|slate|zinc|red|blue|green|orange|teal)-|#[0-9a-fA-F]{3,8}|rgb\(' <changed-files>`.
3. Inspect all themes for default, hover/focus, disabled, invalid, empty, loading, and selected states.
4. Check narrow/wide layouts, long translated text, keyboard navigation, and RTL when placement is directional.
