<?php
/**
 * Plugin Name: Mautic Badges
 * Description: Sync Discourse badges into WordPress and render them via shortcode. Supports n8n-forwarded webhook ingestion.
 * Version: 0.6.2
 * Author: Mautic
 * License: GPLv2 or later
 * Text Domain: mautic-badges
 */

if (!defined('ABSPATH')) {
	exit;
}

define('MB_VERSION', '0.1.0');
define('MB_PLUGIN_FILE', __FILE__);
define('MB_PLUGIN_DIR', __DIR__);

// Config is expected via wp-config.php or environment.
// (Avoid committing secrets into plugin source.)
// define('DISCOURSE_BASE_URL', 'https://forum.mautic.org');
// define('DISCOURSE_API_KEY', '...');
// define('DISCOURSE_API_USERNAME', 'mautibot');
// define('MB_INGEST_TOKEN', 'local-shared-token');

require_once MB_PLUGIN_DIR . '/includes/Installer.php';
require_once MB_PLUGIN_DIR . '/includes/Client.php';
require_once MB_PLUGIN_DIR . '/includes/Sync.php';
require_once MB_PLUGIN_DIR . '/includes/Rest.php';
require_once MB_PLUGIN_DIR . '/includes/Shortcode.php';
require_once MB_PLUGIN_DIR . '/includes/Admin.php';
require_once MB_PLUGIN_DIR . '/includes/Directory.php';
require_once MB_PLUGIN_DIR . '/includes/ProfileHook.php';

register_activation_hook(__FILE__, static function () {
	\MB\Installer::activate();

	// Schedule nightly badge refresh if not already scheduled.
	if (!wp_next_scheduled('mb_nightly_refresh_mapped_users')) {
		wp_schedule_event(time() + 300, 'daily', 'mb_nightly_refresh_mapped_users');
	}
});
register_deactivation_hook(__FILE__, ['MB\\Installer', 'deactivate']);

add_action('plugins_loaded', static function () {
	\MB\Installer::maybe_upgrade();
	\MB\Rest::init();
	\MB\Shortcode::init();
	\MB\Admin::init();
	\MB\Directory::init();
	\MB\ProfileHook::init();
});

// Cron: nightly auto-refresh for all mapped users.
add_action('mb_nightly_refresh_mapped_users', static function () {
	\MB\Sync::refresh_all_mapped_users(100);
});

