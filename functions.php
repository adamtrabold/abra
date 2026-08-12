<?php

declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────────
// 1. THEME SETUP
// Block themes require explicit opt-in for wide/full alignment and responsive
// embeds — these are not on by default. Editor styles won't load at all without
// add_editor_style(); block themes skip the classic style queue.
// ─────────────────────────────────────────────────────────────────────────────
add_action('after_setup_theme', function (): void {
    add_theme_support('wp-block-styles');
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_editor_style('assets/css/editor.css');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. ENQUEUE STYLES
// style.css carries the theme header and serves as the canonical theme
// stylesheet. global.css and blocks.css are optional — they only load if they
// exist, so this works in new installs before those files are built.
// ─────────────────────────────────────────────────────────────────────────────
add_action('wp_enqueue_scripts', function (): void {
    $theme   = wp_get_theme();
    $version = $theme->get('Version');

    wp_enqueue_style('abra-style', get_stylesheet_uri(), [], $version);

    $css_files = [
        'abra-global' => '/assets/css/global.css',
        'abra-blocks' => '/assets/css/blocks.css',
    ];

    foreach ($css_files as $handle => $path) {
        $full_path = get_template_directory() . $path;
        if (file_exists($full_path)) {
            wp_enqueue_style($handle, get_template_directory_uri() . $path, ['abra-style'], $version);
        }
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. PER-BLOCK CSS LOADING
// WordPress loads all block CSS in one bundle by default. This loads each
// block's CSS only when that block appears on the page — leaner payloads on
// pages that use only a handful of blocks.
// ─────────────────────────────────────────────────────────────────────────────
add_filter('should_load_separate_core_block_assets', '__return_true');

// ─────────────────────────────────────────────────────────────────────────────
// 4. DEQUEUE wp-block-library-theme
// wp-block-library-theme adds decorative styles: quote borders, table borders,
// pullquote borders. These are design decisions that belong to Abra, not core.
// Core layout styles (wp-block-library) are deliberately kept — they handle
// structural concerns like alignment, spacing, and group/columns layout.
// ─────────────────────────────────────────────────────────────────────────────
add_action('wp_enqueue_scripts', function (): void {
    wp_dequeue_style('wp-block-library-theme');
}, 100);

// ─────────────────────────────────────────────────────────────────────────────
// 5 + 6 + 7. INLINE BASELINE STYLES (single wp_head hook, priority 1)
//
// 5. ADMIN BAR HEIGHT AS CSS CUSTOM PROPERTY
//    WordPress hardcodes admin bar height with !important on html { margin-top }
//    but provides no CSS custom property. Abra exposes --admin-bar-height so
//    any fixed or sticky element can offset itself without magic numbers.
//
// 6. NAV SUBMENU COLOR RESET
//    WordPress hardcodes background: #fff and color: #000 on navigation
//    submenus when no color is set. No theme.json key controls this — it lives
//    in core block CSS. Reset to inherit so Abra's palette flows through.
//
// 7. BODY MARGIN RESET
//    WordPress does not reset the browser default 8px body margin in block
//    themes. Without this, full-width sections bleed into a visible gap.
//
// All three are combined into one hook call to minimise hook dispatch overhead.
// ─────────────────────────────────────────────────────────────────────────────
add_action('wp_head', function (): void {
    echo '<style>
        body { margin: 0; }
        :root { --admin-bar-height: 0px; }
        .admin-bar { --admin-bar-height: 32px; }
        @media screen and (max-width: 782px) {
            .admin-bar { --admin-bar-height: 46px; }
        }
        .wp-block-navigation .wp-block-navigation__submenu-container {
            background-color: inherit;
            color: inherit;
            border: none;
        }
    </style>';
}, 1);

// ─────────────────────────────────────────────────────────────────────────────
// 8. ACF JSON SYNC
// Directs ACF field group JSON to the theme's acf-json/ folder so field group
// definitions travel with the theme in version control. The load path is also
// registered so ACF reads from the same folder on import/sync.
// ─────────────────────────────────────────────────────────────────────────────
if (class_exists('ACF')) {
    add_filter('acf/settings/save_json', fn () => get_template_directory() . '/acf-json');

    add_filter('acf/settings/load_json', function (array $paths): array {
        $paths[] = get_template_directory() . '/acf-json';
        return $paths;
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// 9. AUTO-REGISTER ACF BLOCKS
// Scans /blocks/ for any block.json file and registers it automatically.
// Zero config — drop a folder into /blocks/ and it appears in the editor.
// Each block.json is the single source of truth; no PHP registration needed
// per block.
// ─────────────────────────────────────────────────────────────────────────────
add_action('init', function (): void {
    if (!function_exists('acf_register_block_type')) {
        return;
    }

    $blocks_dir = get_template_directory() . '/blocks/';

    if (!is_dir($blocks_dir)) {
        return;
    }

    foreach (glob($blocks_dir . '*/block.json') ?: [] as $block_json) {
        register_block_type(dirname($block_json));
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// 10. REGISTER PATTERN CATEGORY
// Patterns shipped with Abra are grouped under a dedicated "Abra" category so
// they don't get lost among core and plugin patterns in the inserter.
// ─────────────────────────────────────────────────────────────────────────────
add_action('init', function (): void {
    register_block_pattern_category('abra', ['label' => 'Abra']);
});
