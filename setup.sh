#!/usr/bin/env bash
set -e

# Run this from your WordPress root directory (not the theme folder).
# Requires WP-CLI: https://wp-cli.org/

if ! command -v wp &> /dev/null; then
    echo "WP-CLI not found. Install it from https://wp-cli.org/ and try again."
    exit 1
fi

echo "Installing ACF..."
wp plugin install advanced-custom-fields --activate

echo ""
echo "Done. Next steps:"
echo "  1. Activate the Abra theme in WP Admin → Appearance → Themes"
echo "  2. Create field groups in WP Admin → Custom Fields"
echo "  3. Commit acf-json/*.json to version control"
