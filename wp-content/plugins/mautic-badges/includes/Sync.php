<?php

namespace MB;

if (!defined('ABSPATH')) {
	exit;
}

class Sync
{
	public static function badges_table(): string
	{
		global $wpdb;
		return $wpdb->prefix . 'discourse_badges';
	}

	public static function user_badges_table(): string
	{
		global $wpdb;
		return $wpdb->prefix . 'discourse_user_badges';
	}

	public static function upsert_catalog(array $badges): void
	{
		global $wpdb;
		$table = self::badges_table();
		$now = current_time('mysql');

		foreach ($badges as $b) {
			$discourse_badge_id = (int) ($b['id'] ?? 0);
			if ($discourse_badge_id <= 0) {
				continue;
			}

			$name = sanitize_text_field((string) ($b['name'] ?? ''));
			$description = isset($b['description']) ? wp_kses_post((string) $b['description']) : '';
			$icon_url = !empty($b['image_url']) ? (string) $b['image_url'] : (string) ($b['icon'] ?? '');
			$badge_type = isset($b['badge_type_id']) ? (int) $b['badge_type_id'] : 0;
			$badge_grouping_id = isset($b['badge_grouping_id']) ? (int) $b['badge_grouping_id'] : null;

			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$table}
						(discourse_badge_id, name, description, icon_url, badge_type, badge_grouping_id, enabled, updated_at)
					VALUES (%d, %s, %s, %s, %d, %d, 1, %s)
					ON DUPLICATE KEY UPDATE
						name = VALUES(name),
						description = VALUES(description),
						icon_url = VALUES(icon_url),
						badge_type = VALUES(badge_type),
						badge_grouping_id = VALUES(badge_grouping_id),
						updated_at = VALUES(updated_at)",
					$discourse_badge_id,
					$name,
					$description,
					$icon_url,
					$badge_type,
					$badge_grouping_id !== null ? $badge_grouping_id : 0,
					$now
				)
			);
		}
	}

	public static function replace_user_badges(int $wp_user_id, string $username, array $user_badges): void
	{
		global $wpdb;
		$table = self::user_badges_table();

		$wpdb->query('START TRANSACTION');
		$wpdb->delete($table, ['wp_user_id' => $wp_user_id]);

		// Discourse can return multiple grants for the same badge id over time.
		// Our schema only allows one row per (wp_user_id, discourse_badge_id),
		// so keep the most recent grant per badge.
		$latest_by_badge = [];
		foreach ($user_badges as $ub) {
			$badge_id = (int) ($ub['badge_id'] ?? 0);
			if ($badge_id <= 0) {
				continue;
			}
			$granted_raw = (string) ($ub['granted_at'] ?? '');
			$ts = $granted_raw !== '' ? strtotime($granted_raw) : false;
			if (!isset($latest_by_badge[$badge_id]) || ($ts !== false && $ts > $latest_by_badge[$badge_id]['ts'])) {
				$latest_by_badge[$badge_id] = [
					'ub' => $ub,
					'ts' => $ts !== false ? $ts : 0,
				];
			}
		}

		foreach ($latest_by_badge as $badge_id => $entry) {
			$ub = $entry['ub'];

			$granted_at = null;
			if (!empty($ub['granted_at'])) {
				$ts = strtotime((string) $ub['granted_at']);
				if ($ts) {
					$granted_at = gmdate('Y-m-d H:i:s', $ts);
				}
			}

			$wpdb->insert($table, [
				'wp_user_id' => $wp_user_id,
				'discourse_username' => $username,
				'discourse_user_id' => isset($ub['user_id']) ? (int) $ub['user_id'] : null,
				'discourse_badge_id' => (int) $badge_id,
				'granted_at' => $granted_at,
			]);
		}
		$wpdb->query('COMMIT');
	}

	/**
	 * @return true|\WP_Error
	 */
	public static function refresh_user_by_username(string $username, ?int $wp_user_id = null)
	{
		$username = sanitize_user($username, true);
		if ($username === '') {
			return new \WP_Error('mb_input', 'Invalid username');
		}

		// Ensure badge grouping IDs are available for UI grouping (certifications, etc.).
		// The user-badges endpoint does not reliably include badge_grouping_id, so we
		// opportunistically refresh the catalog if groupings are missing. Rate-limited.
		if (!get_transient('mb_catalog_groupings_checked')) {
			global $wpdb;
			$table = self::badges_table();
			$has_groupings = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE badge_grouping_id IS NOT NULL AND badge_grouping_id > 0");
			if ($has_groupings <= 0) {
				self::refresh_catalog();
			}
			set_transient('mb_catalog_groupings_checked', '1', 6 * HOUR_IN_SECONDS);
		}

		$data = Client::get_user_badges($username);
		if (is_wp_error($data)) {
			return $data;
		}

		$badges = is_array($data['badges'] ?? null) ? $data['badges'] : [];
		$user_badges = is_array($data['user_badges'] ?? null) ? $data['user_badges'] : [];

		if ($badges) {
			self::upsert_catalog($badges);
		}

		if (!$wp_user_id) {
			$wp_user = get_user_by('login', $username) ?: get_user_by('slug', $username);
			if (!$wp_user) {
				return new \WP_Error('mb_map', 'Unable to map Discourse username to WP user');
			}
			$wp_user_id = (int) $wp_user->ID;
		}

		self::replace_user_badges($wp_user_id, $username, $user_badges);
		update_user_meta($wp_user_id, 'discourse_username', $username);
		update_user_meta($wp_user_id, 'discourse_badges_updated_at', time());

		return true;
	}

	/**
	 * @return true|\WP_Error
	 */
	public static function refresh_catalog()
	{
		$json = Client::get_badge_catalog();
		if (is_wp_error($json)) {
			return $json;
		}
		if (!empty($json['badges']) && is_array($json['badges'])) {
			self::upsert_catalog($json['badges']);
		}
		return true;
	}

	/**
	 * Refresh all WP users in batches.
	 *
	 * If a user has a discourse_username meta, we use that.
	 * Otherwise, we assume their WP login matches the Discourse username.
	 *
	 * @param int $batch_size
	 */
	public static function refresh_all_mapped_users(int $batch_size = 50): void
	{
		$batch_size = max(1, min(500, $batch_size));

		$paged = 1;
		do {
			$users = get_users([
				'number' => $batch_size,
				'paged'  => $paged,
				'fields' => ['ID', 'user_login'],
			]);

			if (!$users) {
				break;
			}

			foreach ($users as $u) {
				$uid = (int) $u->ID;
				$mapped = (string) get_user_meta($uid, 'discourse_username', true);
				$username = $mapped !== '' ? $mapped : (string) $u->user_login;
				self::refresh_user_by_username($username, $uid);
			}

			$paged++;
		} while (count($users) === $batch_size);
	}
}

