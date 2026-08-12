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

echo ""
echo "Done. Next steps:"
echo "  1. Activate the Abra theme in WP Admin → Appearance → Themes"
echo "  2. Create field groups in WP Admin → Custom Fields"
echo "  3. Commit acf-json/*.json to version control"
