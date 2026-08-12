<picture>
  <source media="(prefers-color-scheme: dark)" srcset="/.github/abra_logo_light.svg">
  <source media="(prefers-color-scheme: light)" srcset="/.github/abra_logo_dark.svg">
  <img alt="Abra" src="/.github/abra_logo_dark.svg" width="120">
</picture>

# What's Abra?
Abra is a starter WordPress block theme reset button for the Gutenberg era. Zero design opinions, built for creators of custom-rolled, bespoke experiences. Every WordPress default design decision has been explicitly *poofed* away, [Andy Bell's Modern CSS Reset](https://andy-bell.co.uk/a-more-modern-css-reset/) disappears the browser-injected junk — you get a clean slate.

Only necessary templates, blocks, and patterns to get started are provided, everything else is up to you. ACF is bundled for ease of content configuration as you start building.

## What you get

- Minimal block theme (FSE) with a fully configured `theme.json`
- All WordPress default presets suppressed (colors, gradients, fonts, spacing, shadows, aspect ratios)
- Single control panel in `settings.custom` — change tokens, they cascade everywhere
- Native breakpoint control via `settings.viewport` (WordPress 7.1+)
- Templates: index, single, page, archive, 404, design system preview page (private, dev-only)
- Header + footer template parts
- ACF wired with JSON sync to `acf-json/` — field groups are version controlled
- Auto-registering ACF block system — drop a folder in `/blocks/`, it registers itself
- Example block in `/blocks/example-card/` — duplicate to create new blocks
- Example pattern in `/patterns/example-hero.php` — duplicate to create new patterns
- [Andy Bell's Modern CSS Reset](https://andy-bell.co.uk/a-more-modern-css-reset/) in `assets/css/global.css`
- Block override CSS in `assets/css/blocks.css` — documented pattern for overriding core block styles
- Admin bar height exposed as `--admin-bar-height` CSS custom property
- Nav submenu hardcoded colors reset to inherit
- Browser body margin reset
- One-click child theme generator (dashboard + Appearance notices)

## Requirements

- WordPress 6.4+
- PHP 8.0+
- ACF Free or ACF Pro

## Setup

```bash
# 1. Clone into your themes directory
cd wp-content/themes
git clone https://github.com/adamtrabold/abra

# 2. Activate the theme in WordPress admin
```

On activation, Abra automatically sets pretty permalinks and creates your Home, Blog, and Design System pages. An admin notice will prompt you to install ACF.

## Child Theme

You can build directly in Abra, or generate a child theme to keep your project separate from future Abra updates.

On the dashboard and Appearance pages, a notice appears with a single field. Enter your project name and click **Create Child Theme** — Abra generates the child theme, activates it, and drops you on the Themes page.

The generated child theme includes:

- `style.css` — correct headers with `Template: abra`
- `theme.json` — minimal, inherits all Abra tokens; nothing overrides the parent until you add it
- `theme.example.json` — full token reference showing every available group to override
- `functions.php` — ACF block registration and JSON sync pre-wired, pointed at the child theme
- `templates/` — copies of all Abra templates, ready to edit
- `parts/` — copies of all Abra template parts
- `blocks/` — copy of the example block with namespace updated to your project slug
- `patterns/` — copy of the example pattern with namespace updated to your project slug
- `acf-json/` — empty, ready for your field groups

### Overriding tokens in the child

Copy any token group from `theme.example.json` into `theme.json` and change the values. WordPress deep-merges child into parent — only the values you add override Abra's defaults, everything else inherits.

```json
{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 3,
  "settings": {
    "custom": {
      "borderRadius": {
        "sm": "2px",
        "md": "4px"
      }
    }
  }
}
```

### ACF blocks in the child

Drop a folder into the child's `/blocks/` directory. It registers itself — same zero-config system as the parent.

## theme.json — the control panel

Your design tokens — spacing, type, shadows, radii, transitions — live in the `settings.custom` section of `theme.json`. WordPress automatically converts them to CSS custom properties:

```
settings.custom.borderRadius.sm  →  --wp--custom--border-radius--sm
```

Change a value in `theme.json`, it cascades everywhere. No build step. Use them in CSS like any custom property:

```css
.card { border-radius: var(--wp--custom--border-radius--md); }
```

Visit `/design-system` on your site (logged in as admin) to see every token rendered live. The reference table reads actual computed values — it reflects both `theme.json` file values and any Site Editor overrides.

## ACF field groups

Create field groups in WP Admin → Custom Fields. They auto-save as JSON to `acf-json/`. Commit those files. On a new environment: WP Admin → Custom Fields → Sync.

## Git workflow

Always commit `acf-json/*.json`. That's how field group structure travels between environments.
