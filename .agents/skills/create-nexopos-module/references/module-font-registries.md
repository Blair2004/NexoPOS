# Settings-managed font registries

Use this pattern when administrators register external fonts once and module builders assign them to individual design roles.

## Choose storage deliberately

- Use a module-prefixed NexoPOS option for a small global registry that needs no querying, relationships, audit history, or pagination.
- Use a table, model, migration, and CRUD only when fonts need ownership, relational integrity, filtering, large collections, or lifecycle events.
- Store registry entries as `{ id, family, stylesheet_url }`. Generate a stable UUID for `id`; do not use the family name as identity.
- Store role-to-font-ID assignments in consumer settings. Resolve the current registry at render time so an administrator can correct a font URL globally.
- Fall back to the consumer's default font when an assigned registry entry was removed.

## Validate external stylesheets

- Accept either the raw stylesheet URL or a pasted `<link>` tag, then normalize it before validation.
- Allowlist the exact HTTPS host and expected path. For Google Fonts, require `https`, `fonts.googleapis.com`, and a path beginning with `/css`.
- Extract the first `family` query value only as a convenience; still validate the submitted family and normalized URL server-side.
- Re-check the URL in the browser before injecting a `<link rel="stylesheet">`. Never inject arbitrary HTML supplied by a user.

## Add the settings manager

1. Add a custom `SettingForm::tab(..., fields: [], component: 'module-font-manager')` to the module settings page.
2. Register the Vue component on `nsExtraComponents`; do not mount a nested Vue application.
3. Add a settings-specific Vite entry and load it through a `RenderFooterEvent` listener only for the module's settings identifier. The entry must load before dashboard app initialization.
4. Build a compact add/edit/remove interface that saves through `nsHttpClient`, uses module localization, semantic theme utilities, and clear loading/error/confirmation states.
5. Protect create, update, and delete routes with `manage.options` or a narrower module permission. Expose the read route publicly only when public rendering genuinely requires the catalog and every returned field is safe.

## Connect builders and renderers

- Fetch the registry once when the builder loads and present registered families in role dropdowns. Include a “default font” choice represented by `null`.
- Validate every submitted role ID with `Rule::in($registry->ids())`; restrict the role array to known keys.
- Do not persist the registry catalog inside a published design snapshot. Attach the current catalog when returning draft/public rendering payloads.
- Load each selected stylesheet once, deduplicated by stable ID, and expose role-specific CSS variables with the default font as their fallback.

## Reuse fonts in print builders

- Store the registry ID on each printable text element rather than copying the family or stylesheet URL into the design JSON.
- Attach the current registry catalog when loading the builder and print payload, then resolve missing IDs to a built-in print-safe fallback.
- Keep the browser preview, print HTML, and PDF renderer on the same normalized design payload so font assignments do not drift between outputs.
- For browser print views, add one stylesheet link per used registry entry. Do not inject a pasted link tag or load the same stylesheet more than once.
- For server-side PDF renderers, enable remote assets only when required and restrict allowed hosts to the validated stylesheet and font-file hosts. Google Fonts requires both `fonts.googleapis.com` and `fonts.gstatic.com`.
- Expect browser-only effects such as backdrop blur to degrade in PDF output; provide a deterministic translucent-background fallback without changing the saved design.
- Keep printable QR destinations server-owned and read-only. Font or layout customization must never allow a user to replace the encoded public URL.

## Verify

- Test URL normalization, host/path rejection, duplicate family rejection, registry create/update/delete behavior, role-ID validation, deleted-font fallback, print payload resolution, and PDF fallback behavior.
- Run the focused PHPUnit tests, Pint after PHP changes, the module frontend build, and the skill validator after editing this skill.
