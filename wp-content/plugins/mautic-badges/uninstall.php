<?php
/**
 * Uninstall handler.
 *
 * Only deletes data if you explicitly opt in by defining:
 * define('MB_DELETE_DATA_ON_UNINSTALL', true);
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

if (!defined('MB_DELETE_DATA_ON_UNINSTALL') || MB_DELETE_DATA_ON_UNINSTALL !== true) {
	return;
}

global $wpdb;

$badges_table = $wpdb->prefix . 'discourse_badges';
$user_badges_table = $wpdb->prefix . 'discourse_user_badges';

$wpdb->query("DROP TABLE IF EXISTS {$user_badges_table}");
$wpdb->query("DROP TABLE IF EXISTS {$badges_table}");

delete_option('mb_db_version');

