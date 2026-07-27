# NexoPOS theme system

## Architecture

NexoPOS uses Tailwind CSS v4 with CSS-first configuration. Each supported theme is a separate Vite entry:

- `resources/css/light.css`
- `resources/css/dark.css`
- `resources/css/phosphor.css`

Each entry imports Tailwind, scans `resources/`, imports shared CSS and spacing, declares colors in `<theme>/_colors.css`, then imports parallel component styles such as `_forms.css`, `_box.css`, `_table.css`, and `_pos.css`.

An `@theme` declaration named `--color-<role>` generates Tailwind color utilities. For example, `--color-box-background` enables `bg-box-background`, `text-box-background`, `border-box-background`, and opacity forms such as `bg-box-background/70`.

The root `tailwind.config.js` reflects an older CSS-variable model and is not authoritative for current v4 theme colors. Prefer CSS-first `@theme` declarations and the current entry files.

## Runtime selection

`config/nexopos.php` registers `dark`, `light`, and `phosphor`. General settings store `ns_default_theme`; authenticated users can have an attribute override. Layouts load `resources/css/<theme>.css` through `@vite`.

Most layouts output `data-theme="<theme>"`; `dashboard-blank.blade.php` currently outputs a theme class. Do not depend on either selector for normal colors: the selected stylesheet is the compatibility mechanism.

`window.ns.theme` exists for integrations that require concrete values. Prefer computed semantic variables where possible:

```ts
const styles = getComputedStyle(document.documentElement);
const primary = styles.getPropertyValue('--color-primary').trim();
```

## Semantic token catalog

Use each token as a generated utility without the `--color-` prefix.

| Role | Tokens | Typical use |
|---|---|---|
| Text | `typography`, `fontcolor`, `fontcolor-soft`, `fontcolor-hard` | Primary, supporting, and strong text |
| Main surfaces | `surface`, `surface-soft`, `surface-hard`, `popup-surface` | Canvas, subdued/raised areas, popups |
| Navigation | `aside-background`, `aside-color`, `aside-menu-background`, `aside-menu-background-hover`, `aside-submenu-background`, `aside-submenu-background-hover` | Dashboard aside and menus |
| Boxes | `box-background`, `box-edge`, `box-elevation-background`, `box-elevation-edge`, `box-elevation-hover` | Cards, panels, nested and hoverable surfaces |
| Inputs | `input-background`, `input-disabled`, `input-button`, `input-button-hover`, `input-button-active`, `input-option-hover`, `input-edge` | Fields and field controls |
| Generic controls | `button-hover`, `button-active`, `option-hover` | Generic interactions |
| Accent | `primary`, `secondary`, `tertiary`, `soft-primary`, `soft-secondary`, `soft-tertiary` | Brand and muted accent hierarchy |
| Feedback | `info-*`, `success-*`, `warning-*`, `error-*`, `default-*` with `primary`, `secondary`, `tertiary` suffixes | Status surfaces, borders, actions, notices |
| Tabs | `tab-active`, `tab-active-border`, `tab-inactive`, `tab-table-th`, `tab-table-th-edge` | Tab headers, bodies, nested tables |
| Tables | `table-th`, `table-th-edge` | Table headers and edges |
| Floating UI | `floating-menu`, `floating-menu-hover`, `floating-menu-selected`, `floating-menu-edge` | Dropdowns and contextual menus |
| Specialized | `crud-button-edge`, `crud-input-background`, `pos-button-edge` | CRUD and POS controls |
| Numpad | `numpad-background`, `numpad-typography`, `numpad-edge`, `numpad-hover`, `numpad-hover-edge` | Numeric keypad |
| Scroll/code | `scroll-thumb`, `scroll-track`, `scroll-popup-thumb`, `pre` | Scrollbars and code blocks |

Prefer role tokens even when a palette utility matches one theme today.

## Component CSS contract

Vue and Blade markup typically provides layout utilities, a stable hook such as `ns-button`, `ns-input`, `ns-box`, `ns-notice`, `ns-tab`, or `ns-table`, and states such as `active`, `checked`, `has-error`, `success`, `warning`, `error`, or `info`.

Theme component files apply semantic tokens to those hooks. The hook/state vocabulary is an API shared by all themes. Compare matching files in all three directories before editing: differences extend beyond `_colors.css` into forms, tables, tabs, notices, dashboards, popups, and POS rules.

## Adding a theme

1. Register the name in `config/nexopos.php`.
2. Add `resources/css/<theme>.css` as a Vite input.
3. Define every semantic token in the new `_colors.css`.
4. Supply every imported component stylesheet with the same selector contract.
5. Confirm layouts can resolve the entry through `@vite`.
6. Run focused settings/theme tests and `npm run build`.
7. Inspect representative forms, boxes, tables, notices, popups, navigation, and POS screens.

## Known hazards

- `bg-background` appears in current templates, but no `--color-background` exists in the reviewed color files. Prefer `bg-box-background` or deliberately introduce the token in all themes.
- Component styles are substantially duplicated and have drifted. Never assume only `_colors.css` differs.
- Some current code uses palette colors, hard-coded colors, or theme-name selectors. Assess these as legacy, not automatic precedent.
- `dark.css` and `phosphor.css` import the light permissions stylesheet. Verify whether that sharing is intentional before changing permission colors.
- Theme selector output differs between layouts. Avoid new selector-based behavior until the contract is standardized.
- Some chart integrations hard-code theme colors. Prefer computed semantic variables when supported.
