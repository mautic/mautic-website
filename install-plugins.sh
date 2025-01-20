#!/bin/bash

# Define plugin list
PLUGINS=(
  "akismet"
  "classic-editor"
  "contact-form-7"
  "wordpress-seo"
)

# Check if WP-CLI is installed
if ! command -v wp &>/dev/null; then
  echo "WP-CLI is not installed. Installing..."
  curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
  chmod +x wp-cli.phar
  sudo mv wp-cli.phar /usr/local/bin/wp
fi

# Navigate to WordPress directory
WP_DIR="/var/www/html/wordpress"  # Adjust to your WordPress root directory
cd "$WP_DIR" || { echo "WordPress directory not found"; exit 1; }

# Install plugins
echo "Installing plugins..."
for PLUGIN in "${PLUGINS[@]}"; do
  wp plugin install "$PLUGIN" --activate
done

echo "All plugins installed and activated."

