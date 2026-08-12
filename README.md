<picture>
  <source media="(prefers-color-scheme: dark)" srcset="/.github/abra_logo_light.svg">
  <source media="(prefers-color-scheme: light)" srcset="/.github/abra_logo_dark.svg">
  <img alt="Abra" src="/.github/abra_logo_dark.svg" width="120">
</picture>

# What's Abra?
Abra is a starter WordPress block theme reset button for the Gutenberg era. Zero design opinions, built for creators of custom-rolled, bespoke experiences. Every WordPress default design decision has been explicitly *poofed* away, [Andy Bell's Modern CSS Reset](https://andy-bell.co.uk/a-more-modern-css-reset/) disappears the browser-injected junk — you get a clean slate.

Only necessary templates, blocks, and patterns to get started are provided, everything else is up to you. ACF is bundled for ease of content configuration as you start building.

---

## Getting started

The easiest way to get started is to have your favorite LLM clone this repo into your project and activate it for you. If you want to do it manually:

```bash
cd wp-content/themes
git clone https://github.com/adamtrabold/abra
```

Activate in **Appearance → Themes**. Abra sets up your permalinks and creates Home, Blog, and Design System pages automatically. An admin notice will prompt you to install ACF.

------

## Starting a project

Build directly in Abra, or create a child theme for your project to keep your work separate from future Abra updates.

On your dashboard and Appearance page, Abra shows a notice to create a child theme in one click. Type your project name, hit **Create Child Theme** — Abra generates a ready-to-edit theme, copies all the templates and parts into it, and activates it automatically.

Your child theme includes a `theme.example.json` showing every token Abra defines. Copy any group into `theme.json` to override the defaults set by the Abra parent theme.

---

## Blocks and patterns

A starter block lives in `/blocks/example-card/` — duplicate the folder to create a new one, it registers itself automatically.

A starter pattern lives in `/patterns/example-hero.php` — duplicate and edit.

---

## Style tokens

Everything that controls how your site looks — spacing, type, border radius, shadows, transitions — lives in `settings.custom` inside `theme.json`. Change a value there and it updates everywhere, no build step needed.

```json
"borderRadius": {
    "sm": "4px",
    "md": "8px",
    "lg": "16px"
}
```

WordPress turns these into CSS custom properties you can use anywhere:

```css
.card { border-radius: var(--wp--custom--border-radius--md); }
```

Visit `/design-system` on your site (logged in) to see every token rendered live.


---

## Content design via ACF

[Advanced Custom Fields](https://www.advancedcustomfields.com/) is a WordPress plugin that makes it easy to customize the content of your site — things like a hero image, a testimonial, or a team member card. Abra comes with a quick install button out of the box.

Create field groups in **Custom Fields** in your WordPress admin. They save automatically as files inside your theme so they're always part of your project and travel with it.

---

## Requirements

- WordPress 6.4+
- PHP 8.0+
- ACF Free or ACF Pro
