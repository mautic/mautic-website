<?php

namespace MB;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Inject a profile header with avatar, name, and badges into author archives.
 * Suppresses the default Elementor/theme archive title to avoid duplication.
 */
class ProfileHook
{
	private static $done = false;

	public static function init(): void
	{
		add_action('elementor/theme/before_do_archive', [__CLASS__, 'elementor_before_archive']);
		add_action('loop_start', [__CLASS__, 'maybe_output_author_badges']);
		add_filter('get_the_archive_title', [__CLASS__, 'suppress_author_title'], 20);
		add_filter('body_class', [__CLASS__, 'body_classes']);
	}

	public static function body_classes($classes)
	{
		if (is_author()) {
			$user_id = (int) (get_query_var('author') ?: 0);
			if ($user_id > 0) {
				$post_count = (int) count_user_posts($user_id, 'post', true);
				if ($post_count <= 0) {
					$classes[] = 'mb-no-author-posts';
				}
			}
		}
		return $classes;
	}

	/**
	 * Return empty title for author archives once our header has rendered,
	 * so Elementor's Archive Title widget doesn't duplicate the name.
	 */
	public static function suppress_author_title($title)
	{
		if (is_author() && self::$done) {
			return '';
		}
		return $title;
	}

	public static function elementor_before_archive(): void
	{
		if (!is_author()) {
			return;
		}
		self::render_profile_section();
	}

	public static function maybe_output_author_badges($query): void
	{
		if (is_admin() || !$query->is_main_query() || !is_author()) {
			return;
		}
		self::render_profile_section();
	}

	private static function render_profile_section(): void
	{
		if (self::$done) {
			return;
		}
		self::$done = true;

		$user_id = get_query_var('author') ?: get_current_user_id();
		$user    = $user_id ? get_user_by('ID', $user_id) : null;
		if (!$user) {
			return;
		}

		$display_name = $user->display_name;
		$bio          = get_user_meta($user_id, 'description', true);
		$avatar_url   = get_avatar_url($user_id, ['size' => 160]);

		?>
		<section class="mautic-profile-header">
			<div class="mb-profile-top">
				<div class="mb-profile-info">
					<div class="mb-profile-breadcrumb">
						<?php echo Shortcode::render_directory_breadcrumb([
							'user_id' => $user_id,
						]); ?>
					</div>
					<?php if ($avatar_url): ?>
						<div class="mb-profile-avatar-wrapper">
							<img class="mb-profile-avatar" src="<?php echo esc_url($avatar_url); ?>" alt="" width="120" height="120" />
						</div>
					<?php endif; ?>
					<h1 class="mb-profile-name elementor-heading-title elementor-size-default"><?php echo esc_html($display_name); ?></h1>
					<?php if ($bio): ?>
						<p class="mb-profile-bio"><?php echo esc_html($bio); ?></p>
					<?php endif; ?>
				</div>
			</div>
			<div class="mb-profile-badges">
				<h2><?php esc_html_e('Community badges', 'mautic-badges'); ?></h2>
				<?php echo do_shortcode('[mautic_badges show_dates="1" collapse="3" user_id="' . (int) $user->ID . '"]'); ?>
			</div>
		</section>
		<?php
		$post_count = (int) count_user_posts((int) $user->ID, 'post', true);
		if ($post_count <= 0) {
			?>
			<section class="mb-author-no-posts">
				<h2><?php esc_html_e('No articles yet', 'mautic-badges'); ?></h2>
				<p><?php esc_html_e('Want to share your knowledge with the community?', 'mautic-badges'); ?></p>
				<p>
					<a class="mb-author-cta elementor-button elementor-button-link elementor-size-sm" href="<?php echo esc_url('https://contribute.mautic.org/en/latest/contributing/writing_for_mautic.html'); ?>" target="_blank" rel="noopener noreferrer">
						<span class="elementor-button-content-wrapper">
							<span class="elementor-button-icon">
								<svg aria-hidden="true" class="e-font-icon-svg e-fas-arrow-right" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="M190.5 66.9l22.2-22.2c9.4-9.4 24.6-9.4 33.9 0L441 239c9.4 9.4 9.4 24.6 0 33.9L246.6 467.3c-9.4 9.4-24.6 9.4-33.9 0l-22.2-22.2c-9.5-9.5-9.3-25 .4-34.3L311.4 296H24c-13.3 0-24-10.7-24-24v-32c0-13.3 10.7-24 24-24h287.4L190.9 101.2c-9.8-9.3-10-24.8-.4-34.3z"></path></svg>
							</span>
							<span class="elementor-button-text">Write for Mautic</span>
						</span>
					</a></p>
			</section>
			<?php
		}
	}
}
