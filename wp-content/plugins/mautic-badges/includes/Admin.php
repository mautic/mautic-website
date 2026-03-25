<?php

namespace MB;

if (!defined('ABSPATH')) {
	exit;
}

class Admin
{
	public static function init(): void
	{
		add_action('admin_menu', [__CLASS__, 'register_menu']);
		add_action('admin_post_mb_refresh_all_mapped_users', [__CLASS__, 'handle_refresh_all_mapped_users']);

		// Directory opt-out checkbox in WP user profile settings.
		add_action('show_user_profile', [__CLASS__, 'render_directory_opt_out_field']);
		add_action('edit_user_profile', [__CLASS__, 'render_directory_opt_out_field']);
		add_action('personal_options_update', [__CLASS__, 'save_directory_opt_out_field']);
		add_action('edit_user_profile_update', [__CLASS__, 'save_directory_opt_out_field']);
	}

	public static function register_menu(): void
	{
		add_management_page(
			__('Mautic Badges', 'mautic-badges'),
			__('Mautic Badges', 'mautic-badges'),
			'manage_options',
			'mautic-badges',
			[__CLASS__, 'render_page']
		);
	}

	public static function render_page(): void
	{
		if (!current_user_can('manage_options')) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$refreshed = isset($_GET['refreshed']) ? (int) $_GET['refreshed'] : 0;

		$base = defined('DISCOURSE_BASE_URL') ? (string) DISCOURSE_BASE_URL : '';
		$has_api = defined('DISCOURSE_API_KEY') && defined('DISCOURSE_API_USERNAME');
		$has_ingest = defined('MB_INGEST_TOKEN');
		$nonce = wp_create_nonce('mb_admin_actions');

		?>
		<div class="wrap">
			<h1><?php echo esc_html(__('Mautic Badges', 'mautic-badges')); ?></h1>

			<?php if ($refreshed === 1): ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e('All mapped users have been refreshed.', 'mautic-badges'); ?></p>
				</div>
			<?php elseif ($refreshed === -1): ?>
				<div class="notice notice-error is-dismissible">
					<p><?php esc_html_e('Refresh failed. Check the debug log for details.', 'mautic-badges'); ?></p>
				</div>
			<?php endif; ?>
			<p><strong>DISCOURSE_BASE_URL</strong>: <code><?php echo esc_html($base !== '' ? $base : 'not set'); ?></code></p>
			<p><strong>DISCOURSE_API_KEY / DISCOURSE_API_USERNAME</strong>: <code><?php echo esc_html($has_api ? 'set' : 'not set'); ?></code></p>
			<p><strong>MB_INGEST_TOKEN</strong>: <code><?php echo esc_html($has_ingest ? 'set' : 'not set'); ?></code></p>

			<hr />

			<h2><?php echo esc_html(__('Quick setup examples', 'mautic-badges')); ?></h2>
			<p><?php echo esc_html(__('Put these constants in your wp-config.php file. I intentionally read these from constants so secrets are not stored in the plugin database.', 'mautic-badges')); ?></p>
			<p>
				<code>DISCOURSE_BASE_URL</code>, <code>DISCOURSE_API_KEY</code>, <code>DISCOURSE_API_USERNAME</code>
			</p>
			<p>
				<code>MB_INGEST_TOKEN</code> (used to authenticate the webhook/n8n POST)
			</p>
			<p>
				Example snippet (do not copy the placeholder values):
			</p>
			<pre style="background:#f6f7fc;padding:12px;border-radius:10px;overflow:auto;">
<code>define('DISCOURSE_BASE_URL', 'https://forum.mautic.org');
define('DISCOURSE_API_KEY', '...');
define('DISCOURSE_API_USERNAME', 'mautibot');
define('MB_INGEST_TOKEN', 'your-shared-token');</code>
			</pre>

			<hr />

			<h2><?php echo esc_html(__('Webhook / n8n ingest', 'mautic-badges')); ?></h2>
			<p><?php echo esc_html(__('After constants are in wp-config.php, configure your webhook sender (n8n/relay) with these values.', 'mautic-badges')); ?></p>
			<ul>
				<li><code>POST /wp-json/mautic-badges/v1/ingest</code></li>
				<li><strong>Header</strong>: <code>X-Bridge-Token: &lt;MB_INGEST_TOKEN&gt;</code></li>
				<li><strong>JSON body</strong>: <code>{"username":"discourse_username"}</code> (or include <code>wp_user_id</code>)</li>
			</ul>

			<h2><?php echo esc_html(__('Shortcode', 'mautic-badges')); ?></h2>
			<p><code>[mautic_badges]</code> (also supports legacy <code>[discourse_badges]</code>)</p>

			<hr />

			<h2><?php echo esc_html(__('Manual refresh (admin)', 'mautic-badges')); ?></h2>
			<ul>
				<li><code>POST /wp-json/mautic-badges/v1/refresh</code> with JSON <code>{"mode":"catalog"}</code></li>
				<li><code>POST /wp-json/mautic-badges/v1/refresh</code> with JSON <code>{"mode":"user","username":"achilles"}</code></li>
			</ul>

			<hr />

			<h2><?php echo esc_html(__('Directory cache', 'mautic-badges')); ?></h2>
			<p><?php echo esc_html(__('Use this to refresh all WordPress users that have a Discourse username mapped. Runs in batches and may take some time on large sites.', 'mautic-badges')); ?></p>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="mb_refresh_all_mapped_users" />
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>" />
				<p>
					<label>
						<?php echo esc_html(__('Batch size (per request):', 'mautic-badges')); ?>
						<input type="number" name="batch_size" value="50" min="1" max="500" />
					</label>
				</p>
				<p>
					<button type="submit" class="button button-primary">
						<?php echo esc_html(__('Refresh all mapped users now', 'mautic-badges')); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}

	public static function handle_refresh_all_mapped_users(): void
	{
		if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'] ?? '', 'mb_admin_actions')) {
			wp_die('Unauthorized', 403);
		}
		$batch = isset($_POST['batch_size']) ? (int) $_POST['batch_size'] : 50;
		$batch = max(1, min(500, $batch));

		Sync::refresh_all_mapped_users($batch);

		wp_safe_redirect(add_query_arg(['page' => 'mautic-badges', 'refreshed' => 1], admin_url('tools.php')));
		exit;
	}

	public static function render_directory_opt_out_field($user): void
	{
		if (!$user instanceof \WP_User) {
			return;
		}

		$meta_key = (string) apply_filters('mb_directory_opt_out_meta_key', 'directory_opt_out');
		$yes_value = (string) apply_filters('mb_directory_opt_out_meta_yes_value', '1');
		$checked = ((string) get_user_meta((int) $user->ID, $meta_key, true) === $yes_value);
		$nonce = wp_create_nonce('mb_directory_opt_out_' . (int) $user->ID);

		?>
		<h2><?php esc_html_e('Mautician directory', 'mautic-badges'); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="mb_directory_opt_out"><?php esc_html_e('Opt out of listing', 'mautic-badges'); ?></label>
					</th>
					<td>
						<label style="display:inline-flex;align-items:center;gap:10px;">
							<input
								id="mb_directory_opt_out"
								name="mb_directory_opt_out"
								type="checkbox"
								value="1"
								<?php checked($checked); ?>
							/>
							<?php esc_html_e('Hide this account from the Mautician directory', 'mautic-badges'); ?>
						</label>
						<input type="hidden" name="mb_directory_opt_out_nonce" value="<?php echo esc_attr((string) $nonce); ?>" />
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	public static function save_directory_opt_out_field($user_id): void
	{
		$user_id = (int) $user_id;
		if ($user_id <= 0) {
			return;
		}

		if (!current_user_can('edit_user', $user_id)) {
			return;
		}

		$nonce = isset($_POST['mb_directory_opt_out_nonce']) ? (string) wp_unslash($_POST['mb_directory_opt_out_nonce']) : '';
		$nonce_action = 'mb_directory_opt_out_' . $user_id;
		if ($nonce === '' || !wp_verify_nonce($nonce, $nonce_action)) {
			return;
		}

		$meta_key = (string) apply_filters('mb_directory_opt_out_meta_key', 'directory_opt_out');
		$yes_value = (string) apply_filters('mb_directory_opt_out_meta_yes_value', '1');

		$enabled = isset($_POST['mb_directory_opt_out']) ? '1' : '';
		if ($enabled === '1') {
			update_user_meta($user_id, $meta_key, $yes_value);
		} else {
			delete_user_meta($user_id, $meta_key);
		}
	}
}

