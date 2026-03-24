<?php

namespace MB;

if (!defined('ABSPATH')) {
	exit;
}

class Installer
{
	public const OPTION_DB_VERSION = 'mb_db_version';
	public const DB_VERSION = '2';

	/**
	 * Create/upgrade tables like a third-party plugin (dbDelta).
	 */
	public static function activate(): void
	{
		self::create_tables();
		update_option(self::OPTION_DB_VERSION, self::DB_VERSION, true);
	}

	/**
	 * Called on plugins_loaded to run schema upgrades without re-activation.
	 */
	public static function maybe_upgrade(): void
	{
		$current = (string) get_option(self::OPTION_DB_VERSION, '0');
		if (version_compare($current, self::DB_VERSION, '<')) {
			self::create_tables();
			update_option(self::OPTION_DB_VERSION, self::DB_VERSION, true);
		}
	}

	public static function deactivate(): void
	{
		wp_clear_scheduled_hook('mb_nightly_refresh_mapped_users');
	}

	public static function create_tables(): void
	{
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset_collate = $wpdb->get_charset_collate();

		$badges_table = $wpdb->prefix . 'discourse_badges';
		$user_badges_table = $wpdb->prefix . 'discourse_user_badges';

		$sql_badges = "CREATE TABLE {$badges_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			discourse_badge_id INT(11) NOT NULL,
			name VARCHAR(191) NOT NULL,
			description TEXT NULL,
			icon_url TEXT NULL,
			badge_type SMALLINT NULL,
			badge_grouping_id SMALLINT NULL,
			enabled TINYINT(1) NOT NULL DEFAULT 1,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uniq_discourse_badge (discourse_badge_id),
			KEY idx_enabled (enabled),
			KEY idx_grouping (badge_grouping_id)
		) {$charset_collate};";

		$sql_user_badges = "CREATE TABLE {$user_badges_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			wp_user_id BIGINT(20) UNSIGNED NOT NULL,
			discourse_username VARCHAR(191) NOT NULL,
			discourse_user_id INT(11) NULL,
			discourse_badge_id INT(11) NOT NULL,
			granted_at DATETIME NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uniq_user_badge (wp_user_id, discourse_badge_id),
			KEY idx_discourse_username (discourse_username),
			KEY idx_badge_id (discourse_badge_id),
			KEY idx_user_id (wp_user_id)
		) {$charset_collate};";

		dbDelta($sql_badges);
		dbDelta($sql_user_badges);
	}
}

