<?php

namespace MB;

use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
	exit;
}

class Rest
{
	public const NAMESPACE = 'mautic-badges/v1';

	public static function init(): void
	{
		add_action('rest_api_init', [__CLASS__, 'register_routes']);
	}

	public static function register_routes(): void
	{
		register_rest_route(self::NAMESPACE, '/ingest', [
			'methods' => 'POST',
			'callback' => [__CLASS__, 'handle_ingest'],
			'permission_callback' => '__return_true',
			'args' => [
				'username' => [
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'wp_user_id' => [
					'type' => 'integer',
					'sanitize_callback' => 'absint',
				],
			],
		]);

		register_rest_route(self::NAMESPACE, '/refresh', [
			'methods' => 'POST',
			'callback' => [__CLASS__, 'handle_refresh'],
			'permission_callback' => static function () {
				return current_user_can('manage_options');
			},
			'args' => [
				'mode' => [
					'type' => 'string',
					'default' => 'catalog',
					'enum' => ['catalog', 'user'],
					'sanitize_callback' => 'sanitize_key',
				],
				'username' => [
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'wp_user_id' => [
					'type' => 'integer',
					'sanitize_callback' => 'absint',
				],
			],
		]);

		register_rest_route(self::NAMESPACE, '/directory-opt-out', [
			'methods' => 'POST',
			'callback' => [__CLASS__, 'handle_directory_opt_out'],
			'permission_callback' => static function () {
				return is_user_logged_in();
			},
			'args' => [
				'user_id' => [
					'type' => 'integer',
					'sanitize_callback' => 'absint',
				],
				'opt_out' => [
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'nonce' => [
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		]);
	}

	private static function get_header(WP_REST_Request $req, string $name): string
	{
		$val = (string) $req->get_header($name);
		if ($val !== '') {
			return $val;
		}

		if (function_exists('apache_request_headers')) {
			$headers = apache_request_headers();
			if (is_array($headers)) {
				foreach ($headers as $k => $v) {
					if (strtolower((string) $k) === strtolower($name)) {
						return (string) $v;
					}
				}
			}
		}

		return '';
	}

	public static function handle_ingest(WP_REST_Request $req)
	{
		if (!defined('MB_INGEST_TOKEN') || (string) MB_INGEST_TOKEN === '') {
			return new WP_REST_Response(['error' => 'MB_INGEST_TOKEN not configured'], 403);
		}

		$token = self::get_header($req, 'x-bridge-token');
		if ($token === '' || !hash_equals((string) MB_INGEST_TOKEN, $token)) {
			return new WP_REST_Response(['error' => 'Forbidden'], 403);
		}

		$params = $req->get_json_params();
		if (!is_array($params)) {
			$params = [];
		}

		$username = isset($params['username']) ? (string) $params['username'] : '';
		$wp_user_id = isset($params['wp_user_id']) ? (int) $params['wp_user_id'] : null;

		if ($username === '' && $wp_user_id) {
			$username = (string) get_user_meta($wp_user_id, 'discourse_username', true);
		}

		if ($username === '') {
			return new WP_REST_Response(['error' => 'username required'], 400);
		}

		$res = Sync::refresh_user_by_username($username, $wp_user_id);
		if (is_wp_error($res)) {
			return new WP_REST_Response(['error' => $res->get_error_message()], 500);
		}

		return new WP_REST_Response(['ok' => true], 200);
	}

	public static function handle_refresh(WP_REST_Request $req)
	{
		$params = $req->get_json_params();
		if (!is_array($params)) {
			$params = [];
		}

		$mode = isset($params['mode']) ? (string) $params['mode'] : 'catalog';
		if ($mode === 'catalog') {
			$res = Sync::refresh_catalog();
			if (is_wp_error($res)) {
				return new WP_REST_Response(['error' => $res->get_error_message()], 500);
			}
			return new WP_REST_Response(['ok' => true], 200);
		}

		$username = isset($params['username']) ? (string) $params['username'] : '';
		$wp_user_id = isset($params['wp_user_id']) ? (int) $params['wp_user_id'] : null;
		if ($username === '' && $wp_user_id) {
			$username = (string) get_user_meta($wp_user_id, 'discourse_username', true);
		}
		if ($username === '') {
			return new WP_REST_Response(['error' => 'username or wp_user_id required'], 400);
		}

		$res = Sync::refresh_user_by_username($username, $wp_user_id);
		if (is_wp_error($res)) {
			return new WP_REST_Response(['error' => $res->get_error_message()], 500);
		}

		return new WP_REST_Response(['ok' => true], 200);
	}

	public static function handle_directory_opt_out(WP_REST_Request $req): WP_REST_Response
	{
		$user_id = (int) $req->get_param('user_id');
		$opt_out = (string) $req->get_param('opt_out');
		$nonce   = (string) $req->get_param('nonce');

		$user = wp_get_current_user();
		if (!$user || !$user->ID) {
			return new WP_REST_Response(['error' => 'Unauthorized'], 401);
		}

		if ($user_id <= 0) {
			return new WP_REST_Response(['error' => 'user_id required'], 400);
		}

		// Self-service by default. Admins can manage other users.
		if ((int) $user->ID !== (int) $user_id && !current_user_can('manage_options')) {
			return new WP_REST_Response(['error' => 'Forbidden'], 403);
		}

		$nonce_action = 'mb_directory_opt_out_' . $user_id;
		if ($nonce === '' || !wp_verify_nonce($nonce, $nonce_action)) {
			return new WP_REST_Response(['error' => 'Invalid nonce'], 403);
		}

		$yes_value = (string) apply_filters('mb_directory_opt_out_yes_value', '1');
		$enabled = ($opt_out === '1' || $opt_out === $yes_value);

		if ($enabled) {
			update_user_meta($user_id, (string) apply_filters('mb_directory_opt_out_meta_key', 'directory_opt_out'), $yes_value);
		} else {
			delete_user_meta($user_id, (string) apply_filters('mb_directory_opt_out_meta_key', 'directory_opt_out'));
		}

		return new WP_REST_Response(['ok' => true], 200);
	}
}

