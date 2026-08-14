# Abra Build Skill

Guides building or configuring any WordPress construct following FSE standards — core block configuration, custom ACF blocks, and block patterns. This skill is NOT ACF-specific; the FSE-first approach applies to all constructs.

---

## Read first (preamble — do these before anything else)

1. `DESIGN.md` — architecture overview and decision framework
2. Active `theme.json` — current color slugs, font-size slugs, spacing scale, and custom tokens. Read this before referencing ANY color, font, or spacing value anywhere.
3. `blocks/example-card/` — canonical ACF block reference (block.json + render.php)
4. `patterns/example-hero.php` — canonical pattern reference

---

## Decide first

Use the DESIGN.md decision framework before writing any code.

**Always prefer core blocks.** Only build a custom block when a core block (or combination of core blocks) genuinely cannot do it. When in doubt, try composing with core blocks first.

---

## If working from a Figma design

If the design uses WordPress core blocks or components, the [Abra Figma Kit](https://www.figma.com/community/file/1669854326293587407) is the canonical reference for core block shapes, states, and component anatomy.

Use the Figma Console MCP to read the source of truth:

- `figma_get_file_data` — overall file structure and frame layout
- `figma_get_component_details` — individual component anatomy and properties

For any element whose intent is ambiguous — a styled group, an unnamed layer, a component with multiple possible mappings — **ask the user to clarify before proceeding.** Do not assume.

---

## Core block configuration

Use this when you need editor control over a core block's settings or want to apply default tokens.

1. Add `settings.blocks["core/{block}"]` to the active theme.json to control which editor controls are visible (color palette, font size picker, spacing controls, etc.)
2. Add `styles.blocks["core/{block}"]` to the active theme.json to apply default token values (backgroundColor, color, fontSize, spacing)
3. Copy the base CSS from `wp-includes/blocks/{block}/style.css` into `assets/css/blocks.css` and override there — never modify core files
4. Optionally call `register_block_style()` in functions.php to register named style variations (e.g., "outlined", "ghost")

**If a child theme is active, all changes go in the child theme — never the parent.**

---

## Custom ACF block

Use this only when core blocks cannot achieve the design.

1. Create `blocks/{name}/` directory inside the theme's `blocks/` folder
2. Write `block.json`:
   - `"apiVersion": 3`
   - `"name": "abra/{name}"`
   - `"category": "abra"`
   - `"acf": { "mode": "preview", "renderTemplate": "render.php" }`
   - Include `"supports"` (at minimum: `"anchor": true`)
3. Write `render.php`:
   - Use `get_field()` to retrieve ACF field values
   - Output semantic HTML with BEM CSS classes
   - Apply token-based classes — do not write inline styles
4. **Do NOT call `register_block_type()` manually.** The glob in `functions.php` section 9 scans `blocks/*/block.json` and registers everything automatically. Adding a manual call will cause a duplicate registration warning.

**If a child theme is active, the block goes in the child theme's `blocks/` directory.**

---

## Block pattern

1. Create `patterns/{name}.php` inside the theme's `patterns/` folder
2. Write the PHP comment header — all fields are required:
   ```php
   <?php
   /**
    * Title: Human-readable title
    * Slug: abra/{name}
    * Description: One-sentence description
    * Categories: abra
    * Inserter: true
    */
   ?>
   ```
3. Compose the pattern body from core blocks and registered custom blocks only — no raw HTML outside block markup
4. Reference colors using the preset syntax: `"style":{"color":{"background":"var:preset|color|{slug}"}}` — read the active theme.json first to confirm the slug exists
5. Reference font sizes using the preset syntax: `"fontSize":"{slug}"` — confirm the slug exists in theme.json

**If a child theme is active, the pattern goes in the child theme's `patterns/` directory.**

---

## Key failure modes

| Mistake | Why it's wrong |
|---|---|
| Calling `register_block_type()` manually in a block file | The glob in `functions.php` §9 already registers all `blocks/*/block.json` — a manual call produces a duplicate registration notice |
| Inline styles in `render.php` | Bypasses the CSS cascade; use BEM classes and `assets/css/blocks.css` |
| Hardcoded hex colors in block HTML or patterns | Breaks Site Editor color control; use `var:preset|color|{slug}` |
| Hardcoded `px` font sizes in block HTML or patterns | Breaks Site Editor typography control; use font-size slugs from theme.json |
| Raw pixel values in theme.json or CSS instead of preset references | Duplicates what WordPress generates as CSS custom properties |
| Classic PHP template logic in FSE context | FSE uses block templates only — no `get_template_part()` classic patterns |
| Editing the parent theme when a child is active | Changes will be lost on parent update |

---

## Output checklist

Before calling the work done, verify:

- [ ] The construct appears in the block inserter or Site Editor as expected
- [ ] It renders correctly in the editor (edit view) and on the frontend
- [ ] No PHP notices or warnings in the debug log
- [ ] Every color reference uses a slug that exists in the active theme.json
- [ ] Every font-size reference uses a slug that exists in the active theme.json
- [ ] No hardcoded hex, px, or magic numbers in block HTML, pattern markup, or CSS
- [ ] No manual `register_block_type()` call added for an ACF block
