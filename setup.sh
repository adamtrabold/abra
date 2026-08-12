#!/usr/bin/env bash
set -e

# Requires WP-CLI: https://wp-cli.org/
# Run from anywhere — the script finds the WordPress root itself.

if ! command -v wp &> /dev/null; then
    echo "WP-CLI not found. Install it from https://wp-cli.org/ and try again."
    exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
WP_ROOT="$(cd "$SCRIPT_DIR/../../.." && pwd)"

if [ ! -f "$WP_ROOT/wp-config.php" ]; then
    echo "Could not find wp-config.php at $WP_ROOT"
    echo "Make sure the theme is installed at wp-content/themes/abra/"
    exit 1
fi

cd "$WP_ROOT"

echo "Installing ACF..."
wp plugin install advanced-custom-fields --activate

echo "Setting permalinks..."
wp rewrite structure '/%postname%/' --hard

echo "Creating pages..."
HOME_ID=$(wp post create --post_type=page --post_title='Home' --post_status=publish --porcelain)
BLOG_ID=$(wp post create --post_type=page --post_title='Blog' --post_status=publish --porcelain)
DS_ID=$(wp post create --post_type=page --post_title='Design System' --post_status=publish --porcelain)
wp option update show_on_front page
wp option update page_on_front "$HOME_ID"
wp option update page_for_posts "$BLOG_ID"
wp post meta update "$DS_ID" _wp_page_template design-system

echo ""
echo "Done. Next steps:"
echo "  1. Activate the Abra theme in WP Admin → Appearance → Themes"
echo "  2. Create field groups in WP Admin → Custom Fields"
echo "  3. Commit acf-json/*.json to version control"
