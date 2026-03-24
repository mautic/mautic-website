<?php

namespace MB;

if (!defined('ABSPATH')) {
	exit;
}

class Client
{
	private static function base_url(): string
	{
		$base = defined('DISCOURSE_BASE_URL') ? (string) DISCOURSE_BASE_URL : '';
		return rtrim($base, '/');
	}

	/**
	 * @return array|\WP_Error
	 */
	public static function get(string $path, bool $use_api_headers = true)
	{
		$base = self::base_url();
		if ($base === '') {
			return new \WP_Error('mb_config', 'DISCOURSE_BASE_URL not set');
		}

		$url = $base . $path;

		$args = [
			'timeout' => 15,
			'headers' => [
				'Accept' => 'application/json',
			],
		];

		if ($use_api_headers && defined('DISCOURSE_API_KEY') && defined('DISCOURSE_API_USERNAME')) {
			$args['headers']['Api-Key'] = (string) DISCOURSE_API_KEY;
			$args['headers']['Api-Username'] = (string) DISCOURSE_API_USERNAME;
		}

		$res = wp_remote_get($url, $args);
		if (is_wp_error($res)) {
			return $res;
		}

		$code = (int) wp_remote_retrieve_response_code($res);
		$body = (string) wp_remote_retrieve_body($res);

		if ($code < 200 || $code >= 300) {
			return new \WP_Error('mb_http', 'HTTP ' . $code, ['body' => $body]);
		}

		$json = json_decode($body, true);
		if (!is_array($json)) {
			return new \WP_Error('mb_json', 'Invalid JSON from Discourse', ['body' => $body]);
		}

		return $json;
	}

	public static function get_user_badges(string $username)
	{
		return self::get('/user-badges/' . rawurlencode($username) . '.json', true);
	}

	public static function get_badge_catalog()
	{
		return self::get('/badges.json', true);
	}
}

