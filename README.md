<picture>
  <source media="(prefers-color-scheme: dark)" srcset="/.github/abra_logo_light.svg">
  <source media="(prefers-color-scheme: light)" srcset="/.github/abra_logo_dark.svg">
  <img alt="Abra" src="/.github/abra_logo_dark.svg" width="120">
</picture>

# What's Abra?
Abra is a starter WordPress block theme reset button for the Gutenberg era. Zero design opinions, built for creators of custom-rolled, bespoke experiences. Every WordPress default design decision has been explicitly *poofed* away, [Andy Bell's Modern CSS Reset](https://andy-bell.co.uk/a-more-modern-css-reset/) disappears the browser-injected junk — you get a clean slate. Only necessary templates, blocks, and patterns to get started are provided, everything else is up to you. ACF bundled for ease of content configuration as you start building.

## What you get

- Minimal block theme (FSE) with a fully configured `theme.json`
- All WordPress default presets suppressed (colors, gradients, fonts, spacing, shadows, aspect ratios)
- Single control panel in `settings.custom` — change tokens, they cascade everywhere
- Native breakpoint control via `settings.viewport` (WordPress 7.1+)
- Templates: index, single, page, archive, 404
- Header + footer template parts
- ACF wired with JSON sync to `acf-json/` — field groups are version controlled
- Auto-registering ACF block system — drop a folder in `/blocks/`, it registers itself
- Example block in `/blocks/example-card/` — duplicate to create new blocks
- Example pattern in `/patterns/example-hero.php` — duplicate to create new patterns
- [Andy Bell's Modern CSS Reset](https://andy-bell.co.uk/a-more-modern-css-reset/) in `assets/css/global.css` — sensible baseline, supplemented with `video` and `height: auto`
- Block override CSS in `assets/css/blocks.css` — documented pattern for overriding core block styles
- Admin bar height exposed as `--admin-bar-height` CSS custom property
- Nav submenu hardcoded colors reset to inherit
- Browser body margin reset

## Requirements

- WordPress 6.4+
- PHP 8.0+
- WP-CLI (for setup script)
- ACF Free or ACF Pro

## Setup

```bash
# 1. Clone into your themes directory
cd wp-content/themes
git clone https://github.com/adamtrabold/abra

# 2. Activate the theme in WordPress admin

# 3. Run setup (installs ACF, sets pretty permalinks, creates Home + Blog pages)
bash wp-content/themes/abra/setup.sh
```

## theme.json — the control panel

All developer-configurable tokens live in `settings.custom`. Change a value there and it cascades as a CSS custom property (`--wp--custom--key--nested`) and can be referenced anywhere in `theme.json` via `var:custom|key|nested`.

## ACF field groups

Create field groups in WP Admin → Custom Fields. They auto-save as JSON to `acf-json/`. Commit those files. On a new environment: WP Admin → Custom Fields → Sync.

## Git workflow

Always commit `acf-json/*.json`. That's how field group structure travels between environments.
