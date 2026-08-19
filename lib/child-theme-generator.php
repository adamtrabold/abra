<?php

declare(strict_types=1);

if (!is_admin()) {
	return;
}

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN NOTICE
// Shows when Abra is active as the parent theme (no child active).
// Offers a one-field form to generate and activate a child theme.
// ─────────────────────────────────────────────────────────────────────────────
add_action('admin_notices', function (): void {
	if (get_template() !== get_stylesheet()) {
		return;
	}
	if (get_option('abra_child_dismissed')) {
		return;
	}
	$screen = get_current_screen();
	if (!in_array($screen?->id, ['dashboard', 'themes'], true)) {
		return;
	}
	$action_url  = esc_url(admin_url('admin-post.php'));
	$dismiss_url = esc_url(wp_nonce_url(admin_url('admin-post.php?action=abra_dismiss_child'), 'abra_dismiss_child'));
	?>
	<div class="notice notice-info" id="abra-child-notice">
		<form method="post" action="<?php echo esc_url($action_url); ?>" style="display:flex;align-items:center;gap:1.25rem;padding:0.5rem 0;">
			<?php wp_nonce_field('abra_create_child', 'abra_nonce'); ?>
			<input type="hidden" name="action" value="abra_create_child">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1014.98" height="48" aria-label="Abra" style="display:block;flex-shrink:0;margin-left:0.5rem;">
				<path fill="#1c0e02" d="M518.67,638.64c0-1.09-.05-2.18-.13-3.26,64.66-3.82,124.49-12.15,176.35-23.94l-183.68,326.6h488.8l-201.96-359.1c56.09-24.42,89.31-54.74,89.31-87.58,0-47.14-68.47-89.08-174.79-115.85,106.32-26.77,174.79-68.7,174.79-115.85,0-80.84-201.3-146.38-449.62-146.38v230.46L244.4,0,0,434.55h437.73v119.31c-81.28-38.29-216.09-63.33-368.68-63.33v524.46h449.62l-229.39-247.19c136.9-25.39,229.39-73.71,229.39-129.15Z"/>
			</svg>
			<div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
				<p style="margin:0;"><?php esc_html_e('Build directly in this theme, or generate a child theme to keep your project separate from Abra updates.', 'abra'); ?></p>
				<input type="text" name="abra_child_name" placeholder="<?php esc_attr_e('Project name', 'abra'); ?>" style="width:180px;" required>
				<button type="submit" class="button button-primary"><?php esc_html_e('Create Child Theme', 'abra'); ?></button>
				<a href="<?php echo esc_url($dismiss_url); ?>" style="color:inherit;opacity:0.5;font-size:0.85em;text-decoration:none;"><?php esc_html_e('Dismiss', 'abra'); ?></a>
			</div>
		</form>
	</div>
	<?php
});

// ─────────────────────────────────────────────────────────────────────────────
// DISMISS HANDLER
// ─────────────────────────────────────────────────────────────────────────────
add_action('admin_post_abra_dismiss_child', function (): void {
	check_admin_referer('abra_dismiss_child');
	update_option('abra_child_dismissed', true);
	wp_safe_redirect(wp_get_referer() ?: admin_url());
	exit;
});

// ─────────────────────────────────────────────────────────────────────────────
// CREATE CHILD HANDLER
// ─────────────────────────────────────────────────────────────────────────────
add_action('admin_post_abra_create_child', function (): void {
	check_admin_referer('abra_create_child', 'abra_nonce');

	if (!current_user_can('switch_themes')) {
		wp_die(esc_html__('You do not have permission to do this.', 'abra'));
	}

	$name = sanitize_text_field(wp_unslash($_POST['abra_child_name'] ?? ''));

	if (empty($name)) {
		wp_safe_redirect(wp_get_referer() ?: admin_url());
		exit;
	}

	$slug   = sanitize_title($name);
	$result = abra_generate_child($slug, $name);

	if (is_wp_error($result)) {
		wp_die(esc_html($result->get_error_message()));
	}

	switch_theme($slug);
	wp_safe_redirect(admin_url('themes.php'));
	exit;
});

// ─────────────────────────────────────────────────────────────────────────────
// GENERATOR
// ─────────────────────────────────────────────────────────────────────────────
function abra_generate_child(string $slug, string $name): true|WP_Error
{
	$parent_dir = get_template_directory();
	$child_dir  = dirname($parent_dir) . '/' . $slug;

	if (is_dir($child_dir)) {
		return new WP_Error('exists', sprintf(
			/* translators: %s: theme folder slug */
			__("A theme folder named '%s' already exists in wp-content/themes/.", 'abra'),
			$slug
		));
	}

	// Root files
	$files = [
		'style.css'                => abra_child_style_css($name),
		'theme.json'               => abra_child_theme_json(),
		'theme-json-reference.json' => abra_child_theme_reference_json(),
		'functions.php'            => abra_child_functions_php(),
		'CLAUDE.md'                => abra_child_claude_md($name),
		'DESIGN.md'                => abra_child_design_md($name),
		'acf-json/.gitkeep'        => '',
	];

	foreach ($files as $path => $content) {
		$full = $child_dir . '/' . $path;
		wp_mkdir_p(dirname($full));
		file_put_contents($full, $content);
	}

	// Templates — skip design-system.html (parent dev tool)
	wp_mkdir_p($child_dir . '/templates');
	foreach (glob($parent_dir . '/templates/*.html') ?: [] as $src) {
		if (basename($src) === 'design-system.html') {
			continue;
		}
		copy($src, $child_dir . '/templates/' . basename($src));
	}

	// Parts
	wp_mkdir_p($child_dir . '/parts');
	foreach (glob($parent_dir . '/parts/*.html') ?: [] as $src) {
		copy($src, $child_dir . '/parts/' . basename($src));
	}

	// Blocks — update abra/ namespace to child slug in block.json
	if (is_dir($parent_dir . '/blocks')) {
		abra_copy_dir_transformed(
			$parent_dir . '/blocks',
			$child_dir  . '/blocks',
			fn (string $content, string $file): string =>
				basename($file) === 'block.json'
					? str_replace(['"abra/', '"category": "abra"'], ['"' . $slug . '/', '"category": "' . $slug . '"'], $content)
					: $content
		);
	}

	// Patterns — update abra/ namespace to child slug
	wp_mkdir_p($child_dir . '/patterns');
	foreach (glob($parent_dir . '/patterns/*.php') ?: [] as $src) {
		$content = str_replace('abra/', $slug . '/', file_get_contents($src));
		file_put_contents($child_dir . '/patterns/' . basename($src), $content);
	}

	// Screenshot
	$child_screenshot_src = $parent_dir . '/screenshot-child.png';
	if (file_exists($child_screenshot_src)) {
		copy($child_screenshot_src, $child_dir . '/screenshot.png');
	}

	return true;
}

function abra_copy_dir_transformed(string $src, string $dst, callable $transform): void
{
	if (!is_dir($src)) {
		return;
	}

	wp_mkdir_p($dst);

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);

	foreach ($iterator as $item) {
		$relative  = substr($item->getPathname(), strlen($src) + 1);
		$dest_path = $dst . '/' . $relative;

		if ($item->isDir()) {
			wp_mkdir_p($dest_path);
		} else {
			$content = file_get_contents($item->getPathname());
			file_put_contents($dest_path, $transform($content, $item->getPathname()));
		}
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// FILE TEMPLATES
// ─────────────────────────────────────────────────────────────────────────────
function abra_child_style_css(string $name): string
{
	$template = get_template();
	return "/*\nTheme Name: {$name}\nTemplate:   {$template}\nVersion:    1.0.0\n*/\n";
}

function abra_child_theme_json(): string
{
	$data = [
		'$schema' => 'https://schemas.wp.org/trunk/theme.json',
		'version' => 3,
		'settings' => [
			'custom' => [
				'_comment' => 'Copy token groups from theme-json-reference.json here to override Abra defaults.',
			],
		],
	];

	return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

function abra_child_theme_reference_json(): string
{
	return <<<'JSON'
{
	"$schema": "https://schemas.wp.org/trunk/theme.json",
	"version": 3,

	"_instructions": "This is a reference of all WordPress theme.json settings — not an active config file. Everything here is in its suppressed/empty/off state. Copy any section you need into your theme.json and fill in your values. Delete what you don't use.",

	"settings": {

		"_note": "Each top-level key under settings maps to a WordPress feature area. Copy only the keys you need.",

		"color": {
			"_note": "palette items: { slug, color, name }. Set booleans to true to enable the feature in the editor.",
			"palette": [],
			"gradients": [],
			"duotone": [],
			"custom": false,
			"customGradient": false,
			"customDuotone": false,
			"defaultPalette": false,
			"defaultGradients": false,
			"defaultDuotone": false,
			"link": false,
			"heading": false,
			"button": false,
			"caption": false,
			"background": false,
			"text": false
		},

		"typography": {
			"_note": "fontFamilies items: { slug, name, fontFamily }. fontSizes items: { slug, name, size }.",
			"fontFamilies": [],
			"fontSizes": [],
			"customFontSize": false,
			"dropCap": false,
			"fluid": false,
			"fontStyle": false,
			"fontWeight": false,
			"letterSpacing": false,
			"lineHeight": false,
			"textDecoration": false,
			"textTransform": false,
			"writingMode": false
		},

		"spacing": {
			"_note": "spacingSizes items: { slug, name, size }. units controls which units appear in the editor spacing pickers.",
			"spacingSizes": [],
			"customSpacingSize": false,
			"margin": false,
			"padding": false,
			"blockGap": false,
			"units": ["px", "em", "rem", "%", "vw", "vh"]
		},

		"shadow": {
			"_note": "presets items: { slug, name, shadow }. shadow is a CSS box-shadow string, e.g. '0 2px 8px rgba(0,0,0,0.1)'.",
			"presets": [],
			"defaultPresets": false
		},

		"border": {
			"color": false,
			"radius": false,
			"style": false,
			"width": false
		},

		"dimensions": {
			"aspectRatio": false,
			"minHeight": false
		},

		"layout": {
			"_note": "contentSize is the default content column width; wideSize is the wide-aligned width. Set as CSS length values, e.g. '720px' or 'clamp(16rem, 90vw, 56rem)'.",
			"contentSize": "",
			"wideSize": ""
		},

		"custom": {
			"_note": "Arbitrary key-value tokens. Naming formula: settings.custom.{group}.{key} → CSS var --wp--custom--{group}--{key}. camelCase keys become kebab-case. Example: custom.branding.primaryColor → --wp--custom--branding--primary-color.",
			"branding": {
				"primaryColor": "",
				"secondaryColor": "",
				"accentColor": ""
			}
		}

	}
}
JSON;
}

function abra_child_claude_md(string $name): string
{
	return "# {$name}\n\nA child theme built on [Abra](https://github.com/adamtrabold/abra).\n\n## Source of truth\n\nRead \`theme.json\` for all active token values — this is always current. Use \`theme-json-reference.json\` to see what WordPress settings are available to add.\n\n## FSE rules\n\nSee the parent theme's \`CLAUDE.md\` for full FSE rules and key failure modes.\n\n## Where to write changes\n\nAll changes for this project go in this child theme — not the parent Abra theme.\n- Tokens → theme.json\n- CSS → assets/css/global.css or assets/css/blocks.css\n- Blocks → blocks/\n- Patterns → patterns/\n";
}

function abra_child_design_md(string $name): string
{
	return "# {$name} — Design\n\nDesign decisions for this project live in \`theme.json\`. Read it before any visual work — this file is always the source of truth for what's actually defined.\n\nUse \`theme-json-reference.json\` as a copy-pasteable menu of available WordPress settings.\n\nIf working from a Figma file, the [Abra Figma Kit](https://www.figma.com/community/file/1669854326293587407) is the canonical reference for core block shapes, states, and component anatomy.\n\n## Color palette\n\n[Add your palette here — define colors in settings.color.palette in theme.json as named slugs, then document usage decisions below]\n\n## Typography\n\n[Add your type system here — define fonts in settings.typography.fontFamilies and sizes in settings.typography.fontSizes in theme.json]\n\n## Custom tokens\n\n[Document any settings.custom tokens you add — the formula is: settings.custom.{group}.{key} → --wp--custom--{group}--{key}]\n\n## Constraints and notes\n\n[Document design decisions, naming rationale, and rules you discover as you build]\n";
}

function abra_child_functions_php(): string
{
	return <<<'PHP'
<?php

declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────────
// 1. ACF JSON SYNC
// Saves field group JSON to this child theme's acf-json/ folder.
// Priority 20 overrides the parent theme's save path.
// Both parent and child acf-json/ are searched on load.
// ─────────────────────────────────────────────────────────────────────────────
if (class_exists('ACF')) {
	add_filter('acf/settings/save_json', fn () => get_stylesheet_directory() . '/acf-json', 20);

	add_filter('acf/settings/load_json', function (array $paths): array {
		$paths[] = get_stylesheet_directory() . '/acf-json';
		return $paths;
	});
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. AUTO-REGISTER ACF BLOCKS
// Scans /blocks/ for block.json files and registers them automatically.
// Drop a new folder into /blocks/ and it appears in the editor — no config needed.
// ─────────────────────────────────────────────────────────────────────────────
add_action('init', function (): void {
	if (!function_exists('acf_register_block_type')) {
		return;
	}

	$blocks_dir = get_stylesheet_directory() . '/blocks/';

	if (!is_dir($blocks_dir)) {
		return;
	}

	foreach (glob($blocks_dir . '*/block.json') ?: [] as $block_json) {
		register_block_type(dirname($block_json));
	}
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. ENQUEUE CHILD THEME STYLES
// Loads assets/css/global.css and assets/css/blocks.css if they exist.
// Create either file to have it automatically picked up.
// ─────────────────────────────────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', function() {
	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();
	$version   = wp_get_theme()->get( 'Version' );

	if ( file_exists( $theme_dir . '/assets/css/global.css' ) ) {
		wp_enqueue_style( 'child-global', $theme_uri . '/assets/css/global.css', [], $version );
	}
	if ( file_exists( $theme_dir . '/assets/css/blocks.css' ) ) {
		wp_enqueue_style( 'child-blocks', $theme_uri . '/assets/css/blocks.css', [], $version );
	}
} );
PHP;
}
