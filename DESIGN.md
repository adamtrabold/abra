# Abra Architecture

## Abra's zero-reset philosophy

Abra suppresses or empties every WordPress design default. No palette, no font choices, no size scale, no spacing presets, no shadow presets, no border radius presets, no appearance tools. Every one of these is explicitly disabled or set to an empty array in `theme.json` — not left to default. Andy Bell's Modern CSS Reset in `global.css` handles margin clearing, line-height normalization, box-sizing, and media defaults. Additional baseline fixes in `functions.php` cover gaps WordPress doesn't address: `body { margin: 0 }`, a `--admin-bar-height` CSS custom property for sticky element offset, and a nav submenu color reset that core hardcodes outside `theme.json`'s reach. The decorative block stylesheet (`wp-block-library-theme`) is dequeued entirely; structural block layout styles (`wp-block-library`) are kept. Every design token in an Abra project was put there on purpose by the developer.

## Where tokens live and how to add them

There are two types of tokens:

**WP-native design tokens** appear in block editor controls and are declared in named arrays:
- Colors → `settings.color.palette` as named slugs; reference in block HTML as `var:preset|color|{slug}`
- Font families → `settings.typography.fontFamilies`
- Font sizes → `settings.typography.fontSizes`; reference in block HTML as `var:preset|font-size|{slug}`
- Spacing → `settings.spacing.spacingSizes`
- Shadows → `settings.shadow.presets`

**Custom tokens** are CSS custom properties only — they don't appear in editor controls. Declare them in `settings.custom.{group}.{key}`; WordPress generates `--wp--custom--{group}--{key}`. Reference in CSS with `var(--wp--custom--{group}--{key})` and in `theme.json` style values with `var:custom|{group}|{key}`. Abra's base custom tokens cover viewport breakpoints, layout widths, spacing rhythm, line-height scale, border radius, transitions, shadows, and z-index layers.

The parent's `theme.json` is the base. The child's `theme.json` overrides or extends it. Use `theme-json-reference.json` (in the child theme) as a copy-pasteable menu of every option WordPress supports — it's faster than reading the schema.

## Adding or configuring a core WP block

When a design uses a core block not yet styled for the project, do all of this in the child theme if one is active:

1. `settings.blocks["core/{block}"]` in child `theme.json` — control which editor controls appear for that block
2. `styles.blocks["core/{block}"]` in child `theme.json` — apply default token values to the block
3. Copy the block's base CSS from `wp-includes/blocks/{block}/style.css` into `blocks.css` and override from there
4. Optionally call `register_block_style()` in `functions.php` to add named style variations

`blocks.css` is only loaded when it exists — safe to create only when needed.

## Figma-to-WP translation

When working from a Figma design, use Figma Console MCP to read it: `figma_get_file_data` for the full file, `figma_get_component_details` for individual components, `figma_get_selection` for whatever is currently selected. Do not assume component or group names map directly to WP constructs — ask the user what each piece is meant to be.

Once intent is clear, use this decision table:

| Design element | Build as |
|---|---|
| Appears on every page in the same structural position | Template part |
| Defines full page layout for a content type | Template |
| Reusable editorial unit, placed by content editors | Block pattern |
| Custom behavior or ACF field data | Custom ACF block |
| Core WP block needing styling | Configure in child theme.json + blocks.css |
| Visual override only | styles.blocks in child theme.json or blocks.css |

The [Abra Figma Kit](https://www.figma.com/community/file/1669854326293587407) provides official component representations for all core blocks — use it alongside this table.

Content editors should be able to change per-page content → that content goes inside `wp:post-content` in the template. Structural and site-wide elements → template parts.
