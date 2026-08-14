=== Abra ===

Contributors: adamtrabold
Donate link:
Tags: blog, one-column, custom-colors, custom-logo, custom-menu, editor-style, featured-images, full-site-editing, block-patterns, wide-blocks, translation-ready
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 8.0
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A minimal WordPress block theme starter. Zero styles, zero opinions. Your foundation.

== Description ==

Abra is a minimal WordPress block theme starter for the Gutenberg era. It ships with zero design opinions — no colors, no typography presets, no spacing defaults — so you start from a genuinely clean slate rather than overriding someone else's choices.

Every WordPress default design decision has been explicitly reset. Andy Bell's Modern CSS Reset removes browser-injected styles. What remains is a structural scaffold: the minimum set of templates, template parts, and patterns needed to build a custom site without fighting the theme.

Features:
* Full site editing (FSE) ready
* Zero default styles — complete design freedom
* Minimal template set: index, single, page, archive, 404
* Design System page at /abra-design-system showing all HTML elements at browser defaults
* Child theme generator — create a child theme in one click from the dashboard
* Clean token architecture via theme.json

== Installation ==

1. Download the theme zip.
2. In your WordPress admin, go to Appearance → Themes → Add New → Upload Theme.
3. Upload the zip and click Install Now.
4. Activate the theme.
5. On activation, a setup screen will guide you through creating starter pages, configuring permalinks, and optionally installing recommended plugins.

== Frequently Asked Questions ==

= Why are there no styles? =

Abra is a starter theme, not a finished design. The goal is to give you a clean foundation with no defaults to override. Add your own styles via theme.json tokens and CSS in assets/css/.

= Does Abra require ACF? =

No. ACF (Advanced Custom Fields) is an optional recommended plugin. Abra works without it.

= How do I create a child theme? =

After activating Abra, visit your WordPress dashboard. The setup notice includes an option to generate a child theme. Enter your project name and click Create Child Theme — Abra generates a ready-to-edit child theme and activates it automatically.

= Where do I put my styles? =

Global styles go in assets/css/global.css. Block-specific styles go in assets/css/blocks.css. Both are enqueued automatically if the files exist.

== Screenshots ==

1. The theme in its reset state — a blank canvas with no applied styles.

== Changelog ==

= 1.0.0 =
* Initial release.

== Resources ==

* Andy Bell's Modern CSS Reset, MIT License, https://andy-bell.co.uk/a-more-modern-css-reset/
