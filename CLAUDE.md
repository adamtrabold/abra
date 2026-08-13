# Abra

Minimal WordPress block theme starter — a zero-design-opinion blank canvas for designers who want to build a custom site without needing a developer.

## File map

| File | Read when |
|---|---|
| DESIGN.md | Understanding how the architecture works, what to build where |
| theme.json | Doing any visual work — read before touching tokens or block settings |
| theme-json-reference.json | Adding tokens; this is a copy-pasteable menu of every WP-supported option (lives in child theme) |

## What Abra is

Abra is a blank canvas. Every WordPress design default — color palette, font choices, size scale, spacing, shadows, border radius — has been explicitly zeroed out. Andy Bell's Modern CSS Reset (global.css) clears margin, line-height, box-sizing, and media defaults. Nothing visual exists in an Abra project until the developer puts it there intentionally.

## Where to write changes

- If a child theme is active: all changes go in the child theme — never modify the parent while a child is active.
- If no child theme is active: work directly in the parent.
- A child theme is optional; it exists to keep project work separate from future Abra updates.

## How the token system works

WP-native design tokens live in the named arrays inside `settings`: colors → `settings.color.palette`; fonts → `settings.typography.fontFamilies`; sizes → `settings.typography.fontSizes`; spacing → `settings.spacing.spacingSizes`; shadows → `settings.shadow.presets`. These surface in block editor controls. Custom arbitrary tokens — anything the editor doesn't need to expose as a control — go in `settings.custom.{group}.{key}` and become `--wp--custom--{group}--{key}` CSS custom properties automatically. Read `theme.json` before any visual work. Abra resets everything to zero; nothing exists until the project defines it.

## Key failure modes

1. **Blocks hardcoded in a template instead of wp:post-content** → page editor stops working. Fix: slim the template to a shell (header + `wp:post-content` + footer), put content in post content.

2. **Classic PHP template files (single.php, page.php, get_header() calls)** → silently override FSE templates; Site Editor has no effect. Fix: delete the classic files.

3. **CSS values hardcoded instead of referencing CSS custom properties** → drift when tokens change. Fix: use `var(--wp--custom--{group}--{key})`.

4. **Raw hex colors or px font sizes in block HTML** → Site Editor can't update them. Fix: use `var:preset|color|{slug}` and `var:preset|font-size|{slug}`.

5. **Core WP block used in design but not configured in theme.json** → renders with WP fallback styles. Fix: configure in child theme (see DESIGN.md).
