<?php

declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────────
// FIRST-RUN SETUP
// On activation, flags that setup is needed. A dedicated admin page collects
// explicit consent before creating pages or changing any settings.
// ─────────────────────────────────────────────────────────────────────────────

function abra_get_or_create_page(string $title, string $slug, string $status): int
{
	$existing = get_page_by_path($slug, OBJECT, 'page');
	if ($existing) {
		return $existing->ID;
	}

	$trashed = get_posts(['name' => $slug, 'post_type' => 'page', 'post_status' => 'trash', 'numberposts' => 1]);
	if ($trashed) {
		wp_update_post(['ID' => $trashed[0]->ID, 'post_status' => $status]);
		return (int) $trashed[0]->ID;
	}

	return (int) wp_insert_post([
		'post_title'  => $title,
		'post_name'   => $slug,
		'post_type'   => 'page',
		'post_status' => $status,
	]);
}

add_action('after_switch_theme', function (): void {
	if (get_option('abra_setup_complete')) {
		return;
	}
	set_transient('abra_setup_needed', true, DAY_IN_SECONDS);
});

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN NOTICE
// Shown on all admin pages until setup is complete.
// ─────────────────────────────────────────────────────────────────────────────

add_action('admin_notices', function (): void {
	if (get_option('abra_setup_complete') || !get_transient('abra_setup_needed')) {
		return;
	}
	if (!current_user_can('manage_options')) {
		return;
	}
	$setup_url = esc_url(admin_url('themes.php?page=abra-setup'));
	?>
	<div class="notice notice-info">
		<p>
			<?php esc_html_e('Abra is activated.', 'abra'); ?>
			<a href="<?php echo esc_url($setup_url); ?>" class="button button-primary" style="margin-left:8px;">
				<?php esc_html_e('Set up your project →', 'abra'); ?>
			</a>
		</p>
	</div>
	<?php
});

// ─────────────────────────────────────────────────────────────────────────────
// SUCCESS NOTICE
// ─────────────────────────────────────────────────────────────────────────────

add_action('admin_notices', function (): void {
	if (($_GET['abra_setup'] ?? '') !== 'done') {
		return;
	}
	?>
	<div class="notice notice-success is-dismissible">
		<p><?php esc_html_e('Abra is set up. Happy building.', 'abra'); ?></p>
	</div>
	<?php
});

// ─────────────────────────────────────────────────────────────────────────────
// SETUP PAGE
// ─────────────────────────────────────────────────────────────────────────────

add_action('admin_menu', function (): void {
	add_theme_page(
		__('Set Up Abra', 'abra'),
		__('Set Up Abra', 'abra'),
		'manage_options',
		'abra-setup',
		'abra_render_setup_page'
	);
});

function abra_render_setup_page(): void
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$acf_active   = class_exists('ACF');
	$acf_install_url = wp_nonce_url(
		admin_url('update.php?action=install-plugin&plugin=advanced-custom-fields'),
		'install-plugin_advanced-custom-fields'
	);
	?>
	<div class="wrap" style="max-width:640px;">
		<h1><?php esc_html_e('Set Up Abra', 'abra'); ?></h1>
		<p><?php esc_html_e('Choose what to set up. Everything here is optional — you can skip any step.', 'abra'); ?></p>

		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<?php wp_nonce_field('abra_setup', 'abra_setup_nonce'); ?>
			<input type="hidden" name="action" value="abra_setup">

			<?php // ── Section 1: ACF ───────────────────────────────────────── ?>
			<div style="border:1px solid #ddd;border-radius:4px;padding:20px 24px;margin-bottom:20px;">
				<h2 style="margin-top:0;"><?php esc_html_e('Advanced Custom Fields', 'abra'); ?></h2>
				<?php if ($acf_active): ?>
					<p style="color:#46b450;">&#10003; <?php esc_html_e('ACF is already installed.', 'abra'); ?></p>
				<?php else: ?>
					<p><?php esc_html_e('ACF lets you add structured content fields to your pages and posts — hero images, testimonials, team members, and more.', 'abra'); ?></p>
					<a href="<?php echo esc_url($acf_install_url); ?>" class="button button-secondary">
						<?php esc_html_e('Install ACF', 'abra'); ?>
					</a>
				<?php endif; ?>
			</div>

			<?php // ── Section 2: Child theme ───────────────────────────────── ?>
			<div style="border:1px solid #ddd;border-radius:4px;padding:20px 24px;margin-bottom:20px;">
				<h2 style="margin-top:0;"><?php esc_html_e('Child theme', 'abra'); ?></h2>
				<p><?php esc_html_e('A child theme keeps your project files separate from Abra so future theme updates don\'t overwrite your work.', 'abra'); ?></p>
				<label style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
					<input type="checkbox" name="create_child" value="1" checked>
					<?php esc_html_e('Create a child theme for this project', 'abra'); ?>
				</label>
				<label>
					<?php esc_html_e('Project name', 'abra'); ?><br>
					<input
						type="text"
						name="child_theme_name"
						placeholder="<?php esc_attr_e('My Project', 'abra'); ?>"
						style="width:100%;max-width:320px;margin-top:4px;"
					>
				</label>
			</div>

			<?php // ── Section 3: Starter pages ─────────────────────────────── ?>
			<div style="border:1px solid #ddd;border-radius:4px;padding:20px 24px;margin-bottom:24px;">
				<h2 style="margin-top:0;"><?php esc_html_e('Starter pages', 'abra'); ?></h2>

				<label style="display:flex;align-items:flex-start;gap:8px;margin-bottom:12px;">
					<input type="checkbox" name="create_pages" value="1" checked style="margin-top:3px;">
					<span>
						<?php esc_html_e('Create Home and Blog pages and set as the front page', 'abra'); ?><br>
						<small style="color:#666;"><?php esc_html_e('Also sets permalink structure to /%postname%/', 'abra'); ?></small>
					</span>
				</label>

				<label style="display:flex;align-items:flex-start;gap:8px;">
					<input type="checkbox" name="create_design_system" value="1" checked style="margin-top:3px;">
					<span>
						<?php esc_html_e('Create Design System page', 'abra'); ?><br>
						<small style="color:#666;"><?php esc_html_e('Private page at /abra-design-system/ — shows HTML elements at browser defaults.', 'abra'); ?></small>
					</span>
				</label>
			</div>

			<p>
				<button type="submit" class="button button-primary button-large">
					<?php esc_html_e('Set Up Abra', 'abra'); ?>
				</button>
				<a href="<?php echo esc_url(admin_url()); ?>" style="margin-left:12px;color:#666;">
					<?php esc_html_e('Skip for now', 'abra'); ?>
				</a>
			</p>
		</form>

		<p style="margin-top:24px;padding-top:24px;border-top:1px solid #eee;">
			<a href="https://www.figma.com/community/file/1669854326293587407" target="_blank" rel="noopener">
				<?php esc_html_e('Open the Abra Figma Kit →', 'abra'); ?>
			</a>
		</p>
	</div>
	<?php
}

// ─────────────────────────────────────────────────────────────────────────────
// FORM HANDLER
// ─────────────────────────────────────────────────────────────────────────────

add_action('admin_post_abra_setup', function (): void {
	if (!current_user_can('manage_options') || !check_admin_referer('abra_setup', 'abra_setup_nonce')) {
		wp_die(esc_html__('Unauthorized.', 'abra'));
	}

	// Child theme
	if (!empty($_POST['create_child'])) {
		$name = sanitize_text_field(wp_unslash($_POST['child_theme_name'] ?? ''));
		if ($name && function_exists('abra_generate_child')) {
			$slug   = sanitize_title($name);
			$result = abra_generate_child($slug, $name);
			if (!is_wp_error($result)) {
				switch_theme($slug);
			}
		}
	}

	// Starter pages
	if (!empty($_POST['create_pages'])) {
		$home_id = abra_get_or_create_page(__('Home', 'abra'), 'home', 'publish');
		$blog_id = abra_get_or_create_page(__('Blog', 'abra'), 'blog', 'publish');
		update_option('show_on_front', 'page');
		update_option('page_on_front', $home_id);
		update_option('page_for_posts', $blog_id);
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure('/%postname%/');
		flush_rewrite_rules();
	}

	// Design system page
	if (!empty($_POST['create_design_system'])) {
		$ds_id = abra_get_or_create_page(__('Design System', 'abra'), 'abra-design-system', 'private');
		update_post_meta($ds_id, '_wp_page_template', 'design-system');
	}

	delete_transient('abra_setup_needed');
	update_option('abra_setup_complete', true);

	wp_redirect(admin_url('index.php?abra_setup=done'));
	exit;
});
