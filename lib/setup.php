<?php

declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────────
// FIRST-RUN SETUP
// Runs on theme activation. Creates scaffold pages, detects conflicts with
// existing WP settings, and surfaces a one-time resolution UI for anything
// that would change existing site configuration. No UI on fresh installs.
// ─────────────────────────────────────────────────────────────────────────────

function abra_get_or_create_page(string $title, string $slug, string $status): int {
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

    // Create Home and Blog pages — silently skipped if slug already exists.
    $home_id = abra_get_or_create_page('Home', 'home', 'publish');
    $blog_id = abra_get_or_create_page('Blog', 'blog', 'publish');

    // Design System always lives at abra-design-system for clarity.
    $ds_existing = get_page_by_path('abra-design-system', OBJECT, 'page');
    if (!$ds_existing) {
        $trashed_ds = get_posts(['name' => 'abra-design-system', 'post_type' => 'page', 'post_status' => 'trash', 'numberposts' => 1]);
        $ds_existing = $trashed_ds ? $trashed_ds[0] : null;
    }

    if (!$ds_existing) {
        $ds_id = (int) wp_insert_post([
            'post_title'  => 'Design System',
            'post_name'   => 'abra-design-system',
            'post_type'   => 'page',
            'post_status' => 'private',
        ]);
        update_post_meta($ds_id, '_wp_page_template', 'design-system');
    } else {
        $ds_id = (int) $ds_existing->ID;
    }

    // Detect conflicts with existing WP settings.
    $conflicts = [];

    if ($ds_existing) {
        $conflicts['design_system'] = ['ds_id' => $ds_id];
    }

    if (get_option('show_on_front') === 'page' && (int) get_option('page_on_front') > 0) {
        $current_front            = get_post((int) get_option('page_on_front'));
        $conflicts['front_page']  = ['current_title' => $current_front ? $current_front->post_title : ''];
    }

    if ((int) get_option('page_for_posts') > 0) {
        $current_blog            = get_post((int) get_option('page_for_posts'));
        $conflicts['blog_page']  = ['current_title' => $current_blog ? $current_blog->post_title : ''];
    }

    if (get_option('permalink_structure') !== '/%postname%/') {
        $conflicts['permalink'] = true;
    }

    if (empty($conflicts)) {
        // Fresh install — apply everything silently.
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure('/%postname%/');
        flush_rewrite_rules();
        update_option('show_on_front', 'page');
        update_option('page_on_front', $home_id);
        update_option('page_for_posts', $blog_id);
        update_option('abra_setup_complete', true);
        return;
    }

    // Store conflict state for the resolution UI.
    set_transient('abra_setup_pending', [
        'conflicts' => $conflicts,
        'home_id'   => $home_id,
        'blog_id'   => $blog_id,
        'ds_id'     => $ds_id,
    ], HOUR_IN_SECONDS);
});


// ─────────────────────────────────────────────────────────────────────────────
// CONFLICT RESOLUTION UI
// Admin notice rendered as a self-contained form. Only appears when conflicts
// were detected on activation. Each question only renders if it applies.
// ─────────────────────────────────────────────────────────────────────────────

add_action('admin_notices', function (): void {
    if (get_option('abra_setup_complete') || !current_user_can('manage_options')) {
        return;
    }

    $pending = get_transient('abra_setup_pending');
    if (!$pending) {
        return;
    }

    $conflicts = $pending['conflicts'];

    ?>
    <div class="notice notice-info" style="padding: 20px 24px; max-width: 620px;">
        <h3 style="margin: 0 0 6px;">Finish setting up Abra</h3>
        <p style="margin: 0 0 20px; color: #666;">Abra found some existing settings. Choose what to do, then click <strong>Apply</strong>.</p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('abra_setup_resolve', 'abra_nonce'); ?>
            <input type="hidden" name="action" value="abra_setup_resolve">

            <?php if (isset($conflicts['design_system'])): ?>
            <fieldset style="margin-bottom: 20px; border: 1px solid #ddd; border-radius: 4px; padding: 14px 16px;">
                <legend style="font-weight: 600; padding: 0 4px;">A page already exists at <code>/abra-design-system/</code>. What should we do?</legend>
                <label style="display: block; margin-bottom: 6px;">
                    <input type="radio" name="design_system" value="keep" checked> Keep the existing page
                </label>
                <label style="display: block;">
                    <input type="radio" name="design_system" value="replace"> Replace it with Abra's blank design system page
                </label>
            </fieldset>
            <?php endif; ?>

            <?php if (isset($conflicts['front_page'])): ?>
            <fieldset style="margin-bottom: 20px; border: 1px solid #ddd; border-radius: 4px; padding: 14px 16px;">
                <legend style="font-weight: 600; padding: 0 4px;">Front page</legend>
                <p style="margin: 0 0 10px;">Your front page is currently set to show <strong><?php echo esc_html($conflicts['front_page']['current_title']); ?></strong>. Switch it to show the new <strong>Home</strong> page instead?</p>
                <label style="display: block; margin-bottom: 6px;"><input type="radio" name="front_page" value="no" checked> No, keep my current front page</label>
                <label style="display: block;"><input type="radio" name="front_page" value="yes"> Yes, switch to the new Home page</label>
            </fieldset>
            <?php endif; ?>

            <?php if (isset($conflicts['blog_page'])): ?>
            <fieldset style="margin-bottom: 20px; border: 1px solid #ddd; border-radius: 4px; padding: 14px 16px;">
                <legend style="font-weight: 600; padding: 0 4px;">Blog page</legend>
                <p style="margin: 0 0 10px;">Your blog posts are currently displayed on <strong><?php echo esc_html($conflicts['blog_page']['current_title']); ?></strong>. Display them on the new <strong>Blog</strong> page instead?</p>
                <label style="display: block; margin-bottom: 6px;"><input type="radio" name="blog_page" value="no" checked> No, keep my current blog page</label>
                <label style="display: block;"><input type="radio" name="blog_page" value="yes"> Yes, switch to the new Blog page</label>
            </fieldset>
            <?php endif; ?>

            <?php if (isset($conflicts['permalink'])): ?>
            <fieldset style="margin-bottom: 20px; border: 1px solid #ddd; border-radius: 4px; padding: 14px 16px;">
                <legend style="font-weight: 600; padding: 0 4px;">Permalink structure</legend>
                <p style="margin: 0 0 10px;">Switch permalink structure to <code>/%postname%/</code>, creating clean URLs like <code>/page-name/</code>?</p>
                <label style="display: block; margin-bottom: 6px;"><input type="radio" name="permalink" value="no" checked> No, keep my current permalink structure</label>
                <label style="display: block;"><input type="radio" name="permalink" value="yes"> Yes, switch to clean URLs</label>
            </fieldset>
            <?php endif; ?>

            <p style="margin: 0;"><button type="submit" class="button button-primary">Apply</button></p>
        </form>
    </div>
    <?php
});


// ─────────────────────────────────────────────────────────────────────────────
// RESOLUTION HANDLER
// Processes the conflict form and applies the user's choices.
// ─────────────────────────────────────────────────────────────────────────────

add_action('admin_post_abra_setup_resolve', function (): void {
    if (!current_user_can('manage_options') || !check_admin_referer('abra_setup_resolve', 'abra_nonce')) {
        wp_die('Unauthorized');
    }

    $pending = get_transient('abra_setup_pending');
    if (!$pending) {
        wp_redirect(admin_url());
        exit;
    }

    $conflicts = $pending['conflicts'];
    $home_id   = $pending['home_id'];
    $blog_id   = $pending['blog_id'];
    $ds_id     = $pending['ds_id'];

    // Design system — replace clears content and re-assigns template.
    if (isset($conflicts['design_system']) && ($_POST['design_system'] ?? 'keep') === 'replace') {
        wp_update_post(['ID' => $ds_id, 'post_status' => 'private', 'post_content' => '']);
        update_post_meta($ds_id, '_wp_page_template', 'design-system');
    }

    // Front page — only change if user said yes, or if no conflict (fresh).
    if (!isset($conflicts['front_page']) || ($_POST['front_page'] ?? 'no') === 'yes') {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $home_id);
    }

    // Blog page.
    if (!isset($conflicts['blog_page']) || ($_POST['blog_page'] ?? 'no') === 'yes') {
        update_option('page_for_posts', $blog_id);
    }

    // Permalink structure.
    if (!isset($conflicts['permalink']) || ($_POST['permalink'] ?? 'no') === 'yes') {
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure('/%postname%/');
        flush_rewrite_rules();
    }

    delete_transient('abra_setup_pending');
    update_option('abra_setup_complete', true);

    wp_redirect(admin_url('index.php?abra_setup=done'));
    exit;
});
