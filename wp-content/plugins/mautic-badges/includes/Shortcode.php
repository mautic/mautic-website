<?php

namespace MB;

if (!defined('ABSPATH')) {
	exit;
}

class Shortcode
{
	public static function init(): void
	{
		add_action('wp_enqueue_scripts', [__CLASS__, 'register_assets']);
		add_shortcode('mautic_badges', [__CLASS__, 'render']);
		// Back-compat with earlier naming.
		add_shortcode('discourse_badges', [__CLASS__, 'render']);
		add_shortcode('mautic_directory_breadcrumb', [__CLASS__, 'render_directory_breadcrumb']);
	}

	public static function register_assets(): void
	{
		if (!wp_style_is('mb-fontawesome', 'registered')) {
			wp_register_style(
				'mb-fontawesome',
				'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
				[],
				'6.5.1'
			);
		}

		wp_register_style(
			'mb-badges',
			plugins_url('assets/badges.css', MB_PLUGIN_FILE),
			['mb-fontawesome'],
			MB_VERSION
		);
	}

	/**
	 * Render an icon from either an image URL or a Font Awesome class name.
	 */
	public static function render_icon(string $icon_raw, string $extra_class = 'mb-badge-icon', int $size = 0): string
	{
		if ($icon_raw === '') {
			return '';
		}

		if (strpos($icon_raw, 'http') === 0 || strpos($icon_raw, '/') === 0) {
			$sz = $size > 0
				? ' width="' . $size . '" height="' . $size . '" style="width:' . $size . 'px;height:' . $size . 'px;max-width:' . $size . 'px;object-fit:contain;"'
				: '';
			return '<img class="' . esc_attr($extra_class) . '"' . $sz . ' src="' . esc_url($icon_raw) . '" alt="" loading="lazy" />';
		}

		$style = 'fas';
		$icon = $icon_raw;

		if (preg_match('/^(fa[rsbl])-(.+)$/', $icon_raw, $m)) {
			$style = $m[1];
			$icon  = $m[2];
		}

		if (strpos($icon, 'fa-') !== 0) {
			$icon = 'fa-' . $icon;
		}

		return '<i class="' . esc_attr($extra_class) . ' ' . esc_attr($style) . ' ' . esc_attr($icon) . '" aria-hidden="true"></i>';
	}

	/**
	 * Discourse badge grouping IDs considered "featured".
	 * 8 = Mautic Certification, 7 = Mautic Conferences, 6 = Backers.
	 */
	public static function featured_group_ids(): array
	{
		return apply_filters('mb_featured_group_ids', [8, 7, 6]);
	}

	/**
	 * Discourse badge grouping IDs considered "certifications".
	 */
	public static function certification_group_ids(): array
	{
		return apply_filters('mb_certification_group_ids', [8]);
	}

	/**
	 * Sort badge rows by tier (gold first) then date descending.
	 * Works with both stdClass rows (Shortcode) and arrays (Directory).
	 */
	public static function sort_badges(array $rows): array
	{
		usort($rows, function ($a, $b) {
			$a_type = is_object($a) ? (int) $a->badge_type : (int) ($a['badge_type'] ?? 3);
			$b_type = is_object($b) ? (int) $b->badge_type : (int) ($b['badge_type'] ?? 3);
			$a_date = is_object($a) ? ($a->granted_at ?? '') : ($a['granted_at'] ?? '');
			$b_date = is_object($b) ? ($b->granted_at ?? '') : ($b['granted_at'] ?? '');

			if ($a_type !== $b_type) {
				return $a_type - $b_type;
			}

			return strcmp((string) $b_date, (string) $a_date);
		});

		return $rows;
	}

	public static function render($atts = []): string
	{
		wp_enqueue_style('mb-badges');

		$atts = shortcode_atts([
			'user_id'    => 0,
			'limit'      => 0,
			'collapse'   => 0,
			'show_dates' => '0',
			'show_other' => '1',
			'empty'      => __('No badges yet.', 'mautic-badges'),
		], $atts);

		$user_id = (int) $atts['user_id'];
		if ($user_id <= 0) {
			$user_id = (int) (get_query_var('author') ?: get_current_user_id());
		}

		if ($user_id <= 0) {
			return '';
		}

		global $wpdb;
		$ub_table = Sync::user_badges_table();
		$b_table  = Sync::badges_table();

		$sql = $wpdb->prepare(
			"SELECT ub.discourse_badge_id, ub.granted_at,
			        b.name, b.icon_url, b.badge_type, b.badge_grouping_id
			 FROM {$ub_table} ub
			 JOIN {$b_table} b ON b.discourse_badge_id = ub.discourse_badge_id
			 WHERE ub.wp_user_id = %d",
			$user_id
		);

		$rows = $wpdb->get_results($sql);

		if (!$rows) {
			$username = (string) get_user_meta($user_id, 'discourse_username', true);
			if ($username !== '') {
				$res = Sync::refresh_user_by_username($username, $user_id);
				if (!is_wp_error($res)) {
					$rows = $wpdb->get_results($sql);
				}
			}
		}

		if (!$rows) {
			return '<div class="mb-badges mb-empty">' . esc_html((string) $atts['empty']) . '</div>';
		}

		$cert_ids       = self::certification_group_ids();
		$certifications = [];
		$community      = [];
		foreach ($rows as $r) {
			$gid = (int) ($r->badge_grouping_id ?? 0);
			if ($gid > 0 && in_array($gid, $cert_ids, true)) {
				$certifications[] = $r;
			} else {
				$community[] = $r;
			}
		}
		$certifications = self::sort_badges($certifications);
		$community      = self::sort_badges($community);

		$limit = (int) $atts['limit'];
		if ($limit > 0) {
			$certifications = array_slice($certifications, 0, $limit);
		}

		$show_dates = ((string) $atts['show_dates']) === '1';
		$show_other = ((string) $atts['show_other']) === '1';
		$collapse   = (int) $atts['collapse'];

		$has_certs_overflow = $collapse > 0 && count($certifications) > $collapse;
		$toggle_certs = 'mb-tc-' . wp_unique_id();
		$toggle_other = 'mb-to-' . wp_unique_id();

		ob_start();
		?>
		<div class="mb-badges-wrap">
			<div class="mb-cert-section">
				<div class="mb-section-trigger mb-section-static mb-certs-trigger">
					<span class="mb-trigger-left">
						<span class="mb-trigger-title"><?php esc_html_e('Certifications', 'mautic-badges'); ?></span>
						<span class="mb-count"><?php echo (int) count($certifications); ?></span>
					</span>
				</div>

				<?php if (!$certifications): ?>
					<div class="mb-certs-empty">
						<h2 class="mb-certs-empty-title"><?php esc_html_e('No certifications yet', 'mautic-badges'); ?></h2>
						<p class="mb-certs-empty-sub"><?php esc_html_e('Ready to level up your Mautic skills?', 'mautic-badges'); ?></p>
						<p>
							<a class="mb-certs-cta elementor-button elementor-button-link elementor-size-sm" href="<?php echo esc_url('https://mautic.org/mautic-certification'); ?>" target="_blank" rel="noopener noreferrer">
								<span class="elementor-button-content-wrapper">
									<span class="elementor-button-icon">
										<svg aria-hidden="true" class="e-font-icon-svg e-fas-arrow-right" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="M190.5 66.9l22.2-22.2c9.4-9.4 24.6-9.4 33.9 0L441 239c9.4 9.4 9.4 24.6 0 33.9L246.6 467.3c-9.4 9.4-24.6 9.4-33.9 0l-22.2-22.2c-9.5-9.5-9.3-25 .4-34.3L311.4 296H24c-13.3 0-24-10.7-24-24v-32c0-13.3 10.7-24 24-24h287.4L190.9 101.2c-9.8-9.3-10-24.8-.4-34.3z"></path></svg>
									</span>
									<span class="elementor-button-text"><?php esc_html_e('Get certified', 'mautic-badges'); ?></span>
								</span>
							</a>
						</p>
					</div>
				<?php else: ?>
				<?php if ($has_certs_overflow): ?>
					<input type="checkbox" id="<?php echo esc_attr($toggle_certs); ?>" class="mb-toggle-input" aria-hidden="true" />
					<label for="<?php echo esc_attr($toggle_certs); ?>" class="mb-section-trigger mb-certs-toggle">
						<span class="mb-trigger-left">
							<span class="mb-trigger-title"><?php esc_html_e('View', 'mautic-badges'); ?></span>
						</span>
						<span class="mb-trigger-right">
							<span class="mb-toggle-show"><?php esc_html_e('Show all', 'mautic-badges'); ?></span>
							<span class="mb-toggle-hide" style="display:none"><?php esc_html_e('Show fewer', 'mautic-badges'); ?></span>
							<span class="mb-trigger-chevron" aria-hidden="true"></span>
						</span>
					</label>
				<?php endif; ?>

				<div class="mb-badges">
					<?php foreach ($certifications as $idx => $r): ?>
						<?php
						$icon_raw   = (string) ($r->icon_url ?? '');
						$name       = esc_html((string) ($r->name ?? ''));
						$badge_type = (int) ($r->badge_type ?? 3);
						$tier_class = $badge_type === 1 ? 'mb-tier-gold' : ($badge_type === 2 ? 'mb-tier-silver' : 'mb-tier-bronze');
						$date = '';
						if (!empty($r->granted_at)) {
							$date = esc_html(date_i18n(get_option('date_format'), strtotime((string) $r->granted_at)));
						}

						$is_extra = $has_certs_overflow && $idx >= $collapse;
						$extra_class = $is_extra ? ' mb-badge-extra' : '';
						$extra_style = $is_extra ? ' style="display:none"' : '';
						?>
						<div class="mb-badge <?php echo $tier_class . $extra_class; ?>"<?php echo $extra_style; ?>>
							<?php echo self::render_icon($icon_raw, 'mb-badge-icon', 80); ?>
							<span class="mb-badge-name"><?php echo $name; ?></span>
							<?php if ($show_dates && $date): ?>
								<span class="mb-badge-date"><?php echo $date; ?></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>
			<?php if (!$certifications && !$community): ?>
				<div class="mb-badges mb-empty"><?php echo esc_html((string) $atts['empty']); ?></div>
			<?php endif; ?>

			<?php if ($show_other && $community): ?>
			<div class="mb-other-section">
				<input type="checkbox" id="<?php echo esc_attr($toggle_other); ?>" class="mb-toggle-input" aria-hidden="true" />
				<label for="<?php echo esc_attr($toggle_other); ?>" class="mb-section-trigger mb-other-trigger">
					<span class="mb-trigger-left">
						<span class="mb-trigger-title"><?php esc_html_e('Community activity', 'mautic-badges'); ?></span>
						<span class="mb-count"><?php echo (int) count($community); ?></span>
					</span>
					<span class="mb-trigger-right">
						<span class="mb-toggle-show"><?php esc_html_e('Show', 'mautic-badges'); ?></span>
						<span class="mb-toggle-hide" style="display:none"><?php esc_html_e('Hide', 'mautic-badges'); ?></span>
						<span class="mb-trigger-chevron" aria-hidden="true"></span>
					</span>
				</label>
				<div class="mb-other-badges mb-overflow" style="display:none">
					<?php foreach ($community as $r): ?>
						<?php
						$icon_raw   = (string) ($r->icon_url ?? '');
						$name       = esc_html((string) ($r->name ?? ''));
						$badge_type = (int) ($r->badge_type ?? 3);
						$tier_class = $badge_type === 1 ? 'mb-tier-gold' : ($badge_type === 2 ? 'mb-tier-silver' : 'mb-tier-bronze');
						$date = '';
						if (!empty($r->granted_at)) {
							$date = esc_html(date_i18n(get_option('date_format'), strtotime((string) $r->granted_at)));
						}
						?>
						<div class="mb-badge <?php echo $tier_class; ?>">
							<?php echo self::render_icon($icon_raw, 'mb-badge-icon', 80); ?>
							<span class="mb-badge-name"><?php echo $name; ?></span>
							<?php if ($show_dates && $date): ?>
								<span class="mb-badge-date"><?php echo $date; ?></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<script>
		(function(){
			var w=document.currentScript&&document.currentScript.previousElementSibling;
			if(!w||!w.classList.contains('mb-badges-wrap'))return;
			var inputs=w.querySelectorAll('.mb-toggle-input');
			for(var i=0;i<inputs.length;i++){
				(function(cb){
					cb.addEventListener('change',function(){
						var p=cb.parentNode;
						/* Flat badges: toggle .mb-badge-extra siblings */
						var extras=p.querySelectorAll('.mb-badge-extra');
						if(extras.length){
							for(var k=0;k<extras.length;k++){
								extras[k].style.display=cb.checked?'':'none';
							}
						}
						/* Wrapped overflow (community activity section) */
						var ov=p.querySelector('.mb-overflow');
						if(ov)ov.style.display=cb.checked?'grid':'none';
						/* Toggle show/hide labels */
						var sh=p.querySelectorAll('.mb-toggle-show');
						var hi=p.querySelectorAll('.mb-toggle-hide');
						for(var j=0;j<sh.length;j++)sh[j].style.display=cb.checked?'none':'';
						for(var j=0;j<hi.length;j++)hi[j].style.display=cb.checked?'inline':'none';
					});
				})(inputs[i]);
			}
		})();
		</script>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_directory_breadcrumb($atts = []): string
	{
		$defaults = [
			'url'     => home_url('/mautician-directory/'),
			'label'   => __('Mautician Directory', 'mautic-badges'),
			'user_id' => 0,
		];
		$atts = shortcode_atts($defaults, $atts, 'mautic_directory_breadcrumb');

		$user = null;
		$user_id = (int) $atts['user_id'];
		if ($user_id > 0) {
			$user = get_user_by('ID', $user_id);
		}
		if (!$user && is_author()) {
			$author = get_queried_object();
			if ($author instanceof \WP_User) {
				$user = $author;
			}
		}
		if (!$user) {
			$current = wp_get_current_user();
			if ($current && $current->ID) {
				$user = $current;
			}
		}

		if (!$user) {
			return '';
		}

		$name = trim((string) $user->display_name);
		if ($name === '') {
			return '';
		}

		$url   = (string) $atts['url'];
		$label = (string) $atts['label'];

		ob_start();
		?>
		<nav class="mb-directory-breadcrumb" aria-label="<?php esc_attr_e('Directory breadcrumb', 'mautic-badges'); ?>">
			<a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
			<span class="mb-breadcrumb-sep"> | </span>
			<span class="mb-breadcrumb-current"><?php echo esc_html($name); ?></span>
		</nav>
		<?php
		return (string) ob_get_clean();
	}
}