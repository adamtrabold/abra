# Abra Review Skill

FSE compliance and design system audit. Being thorough is expected — reading WordPress documentation for edge cases is acceptable.

---

## Read first

1. Active `theme.json` — the source of truth for registered color slugs, font-size slugs, spacing scale, and `--wp--custom--*` variables. Every audit check below is validated against this file.
2. `DESIGN.md` — architecture decisions and rationale that inform what "correct" looks like for this theme.

---

## May consult (for edge cases)

- WordPress Block Theme Developer Handbook: https://developer.wordpress.org/themes/block-themes/
- Global Settings & Styles reference: https://developer.wordpress.org/block-editor/how-to-guides/themes/global-settings-and-styles/

---

## Audit checks

Run all seven checks. Report findings using the output format below.

### 1. Templates — content structure

Inspect every file in `templates/`. Each template that renders post content must use `wp:post-content` to pull in the post's block content dynamically. Hardcoded content blocks (wp:heading, wp:paragraph, etc.) at the template level lock content into the template and break Site Editor per-post editing.

**Flag:** Any template that contains content blocks instead of `wp:post-content` where post content is expected.

### 2. Block HTML — token usage

Inspect all block markup in `patterns/`, `templates/`, and `parts/`. Check:

- **Colors:** Must use `var:preset|color|{slug}` syntax, not hex values (`#rrggbb`), `rgb()`, or named colors. Confirm the slug exists in the active theme.json.
- **Font sizes:** Must use the `"fontSize":"{slug}"` block attribute with a slug from theme.json, not hardcoded `px`, `em`, or `rem` values in block attributes.

**Flag:** Any hardcoded color or font-size value in block markup. Flag unknown slugs (present in markup but absent from theme.json) separately — those are also broken.

### 3. CSS — duplication of theme.json-generated vars

Inspect `assets/css/global.css` and `assets/css/blocks.css`. WordPress generates CSS custom properties from theme.json at runtime:

- `--wp--preset--color--{slug}` from `settings.color.palette`
- `--wp--preset--font-size--{slug}` from `settings.typography.fontSizes`
- `--wp--preset--spacing--{slug}` from `settings.spacing.spacingSizes`
- `--wp--custom--{path}` from `settings.custom`

Any CSS file that hardcodes the same values those vars would carry is duplicating the design token layer — a maintenance hazard when theme.json changes.

**Flag:** CSS declarations that duplicate values already expressed as theme.json tokens.

### 4. Patterns — registration method

All patterns should be registered via PHP comment headers in `patterns/*.php` files (auto-discovered by WordPress). Patterns stored in the database (via the Patterns UI in the Site Editor without a corresponding file) are not version-controlled and can be overwritten.

**Flag:** Any evidence of DB-only patterns (check if pattern slugs in the inserter have no corresponding file in `patterns/`).

### 5. Custom blocks — registration hygiene

Inspect `blocks/*/block.json`. Each block directory should have a `block.json` and a `render.php` (for ACF blocks). Registration is handled by the glob in `functions.php` §9 — `register_block_type(dirname($block_json))` runs for every match.

**Flag:**
- Any `register_block_type()` call outside of `functions.php` §9 (manual per-block registration causes duplicate notices)
- Any block directory missing `block.json`
- Any block directory whose `block.json` lacks `apiVersion: 3`

### 6. Child theme context

If a child theme is active, all customizations (theme.json overrides, additional blocks, patterns, CSS) belong in the child theme — not the parent.

**Flag:** Any file in the parent theme that appears to be a customization rather than a base definition (heuristic: recently modified files in `blocks/`, `patterns/`, `assets/css/` that contain site-specific content rather than starter/example content).

### 7. Custom CSS variable coverage

Inspect all CSS files for `--wp--custom--` variable references. Cross-reference each against the `settings.custom` object in the active theme.json. A reference to a var that isn't defined in theme.json will silently resolve to empty and produce invisible styling bugs.

**Flag:** Any `--wp--custom--{path}` reference in CSS that has no corresponding entry in theme.json's `settings.custom`.

---

## Output format

Group findings into three severity levels. Use this exact structure:

```
### Blocking
FSE breaks or the Site Editor loses control over something.
[file path] — [description of what is broken and why]

### Warning
Convention violated. Won't break immediately but is wrong and should be fixed.
[file path] — [description of the violation]

### Info
Suggestion or improvement. Not wrong, but worth considering.
[file path] — [description of the suggestion]
```

If a severity level has no findings, write `None.` under that heading. Do not omit the heading.
