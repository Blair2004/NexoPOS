---
name: nexopos-theming
description: Create, style, review, or refactor theme-aware NexoPOS user interfaces. Use for Vue or Blade components, dashboard and POS UI, component CSS under resources/css, semantic color utilities, theme tokens, light/dark/phosphor compatibility, visual states, or new NexoPOS themes. Trigger whenever frontend work must follow the existing NexoPOS theming system.
---

# NexoPOS Theming

Build UI against semantic visual roles so the same component works with the `light`, `dark`, and `phosphor` CSS entries.

## Required reading

- Read [theme-system.md](references/theme-system.md) before changing tokens, CSS entries, global component hooks, or theme selection.
- Read [component-patterns.md](references/component-patterns.md) before creating or restyling a Vue or Blade component.

## Workflow

1. Inspect the target and at least two sibling components. Search for an existing NexoPOS component or `ns-*` hook that already solves the problem.
2. If application code will change, use Laravel Boost `search-docs` for the relevant installed packages before editing.
3. Keep layout, sizing, spacing, responsiveness, and typography weight in markup. Express theme-dependent colors with semantic utilities. Use a stable `ns-*` hook for descendant, state, or cross-component selectors.
4. Map every visual role to an existing token before considering a new token. Prefer `fontcolor`, `box-*`, `input-*`, `tab-*`, and status families over palette colors.
5. Implement default, hover, focus, active or selected, disabled, loading where applicable, and invalid or status states.
6. Preserve semantic HTML, visible focus, labels, native disabled behavior, keyboard behavior, and sufficient contrast. Do not rely on color alone.
7. Verify all three themes. Search changed files for literal colors and justify every remaining occurrence.
8. Run the smallest relevant automated tests and `npm run build`. For PHP changes, also run the focused PHPUnit test and `vendor/bin/pint --dirty --format agent`.

## Non-negotiable rules

- Do not use `dark:` as the compatibility mechanism. NexoPOS loads one complete theme stylesheet, and `phosphor` is a third theme.
- Do not branch markup on `window.ns.theme` for ordinary styling.
- Do not use `data-theme` as though it scopes token values; the selected Vite entry supplies them.
- Do not construct Tailwind classes dynamically, such as `` `bg-${color}-500` ``. Use complete literal class maps or stable semantic classes.
- Do not use Tailwind's `sr-only` utility in NexoPOS Vue or Blade markup. In the current application it can create an unexpected layout box, producing excess whitespace or nested scrollbars. Give icon-only controls an accessible name with `aria-label` or `aria-labelledby` on the interactive element instead.
- Do not add `gray-*`, `white`, `black`, hex, RGB, or RGBA when a semantic token fits. Literal colors are acceptable for fixed assets, deliberate overlays, or third-party APIs that cannot consume CSS variables.
- Do not edit only one theme's component stylesheet when the selector exists in all themes. Compare all three and preserve intentional differences.
- Do not create a base component when an existing `ns-*` component can be extended safely.
- Do not introduce a token in only one theme. Define it in every supported theme.
- Never use a status token ending in `-tertiary` as a background. Status backgrounds use `*-secondary` with `text-white`; `*-primary` and `*-tertiary` remain non-background roles.
- Apply `box-elevation-hover` only when the element is interactive, or when its complete container is clickable. A passive row, card, statistic, or information panel must not change to the elevation-hover surface.
- Use the standard NexoPOS confirmation popup before destructive, revocation, reset, or irreversible actions. Inline confirmation content is not a substitute for `Popup.show(nsConfirmPopup, ...)`.

## Modules (Tailwind prefix + UI)

Modules that import Tailwind in their own CSS **must** use a short module-specific prefix so they do not re-emit core’s unprefixed utility universe:

```css
@import "tailwindcss" prefix(foo);
```

Bridge semantic roles in `@theme` (`--color-fontcolor: var(--color-fontcolor);`, etc.) so `foo:text-fontcolor` compiles against host theme variables.

In markup, the prefix is always first, then variants, then the utility:

| Class | Meaning |
| --- | --- |
| `foo:flex` | Base utility |
| `foo:md:grid-cols-2` | Breakpoint |
| `foo:text-fontcolor` | Default text (headings, body) |
| `foo:text-fontcolor-soft` | Sublines, descriptions |
| `foo:hover:underline` | State |

**Buttons:** theme colors come from `.ns-button` / type class on a **wrapper** (or the `<ns-button>` component). Module utilities supply padding/radius only — never `<a class="ns-button info">`.

**Semantic status scale:** `primary` is lighter than `secondary`, and `secondary` is lighter than `tertiary`. Filled backgrounds may use the primary or secondary step with `text-white`. Never use a tertiary step as a background, including hover states. This rule applies to info, success, warning, and error.

**Loading and failures:** use a sized `ns-spinner` with optional text below it. Settle loading on failure and report most request/action errors with `nsSnackBar.error`; do not inject a full-width error block that shifts the page. Keep inline errors for field/row context or persistent fatal states with retry controls, and reserve stable content height for an initial failure.

Core hooks (`ns-button`, `ns-box`, …) stay **unprefixed** as hook names. Full rules: `create-nexopos-module` → [module-frontend.md](../create-nexopos-module/references/module-frontend.md).

## Choose the styling layer

Use semantic utilities directly for isolated elements:

```html
<section class="rounded-lg border border-box-edge bg-box-background p-4 text-fontcolor shadow">
    <h2 class="font-semibold">Title</h2>
    <p class="mt-1 text-sm text-fontcolor-soft">Supporting copy</p>
</section>
```

Use a stable hook when multiple descendants or states share a contract:

```html
<div class="ns-example-card rounded-lg border"><!-- component markup --></div>
```

Style that hook in the appropriate theme component files, or extract truly identical rules into a shared stylesheet imported by every theme. Keep the selector contract identical.

Avoid theme-aware `@apply` inside an SFC `<style>` block when semantic utilities can live in markup. A single `@reference` to `light.css` does not prove dark or phosphor compatibility.

## Add or change tokens

1. Name the visual role rather than its current color.
2. Check whether an existing role can serve it.
3. Add the same `--color-<role>` to every theme's `_colors.css`.
4. Use the generated utility: `bg-<role>`, `text-<role>`, `border-<role>`, or `ring-<role>`.
5. Update [theme-system.md](references/theme-system.md).
6. Build assets and inspect every supported theme.

## Review checklist

- Reuses an existing component or justifies a new one.
- Uses semantic tokens for foregrounds, surfaces, edges, controls, and feedback.
- Has no accidental theme-specific palette literals.
- Handles relevant states plus long, empty, loading, and error content.
- Contains no `sr-only`; icon-only controls use `aria-label` or `aria-labelledby`.
- Works at supported breakpoints and in RTL when layout is directional.
- Uses literal, statically discoverable Tailwind classes.
- Keeps light, dark, and phosphor selector contracts aligned.
- Passes focused tests and the asset build.
