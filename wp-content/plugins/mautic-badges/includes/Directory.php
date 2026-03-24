<?php

namespace MB;

if (!defined('ABSPATH')) {
	exit;
}

class Directory
{
	public static function init(): void
	{
		add_action('wp_enqueue_scripts', [__CLASS__, 'register_assets']);
		add_shortcode('mautic_user_directory', [__CLASS__, 'render']);
	}

	public static function register_assets(): void
	{
		wp_register_style(
			'mb-directory',
			plugins_url('assets/directory.css', MB_PLUGIN_FILE),
			[],
			MB_VERSION
		);
	}

	private static function qp(string $key, $default = '')
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset($_GET[$key]) ? wp_unslash($_GET[$key]) : $default;
	}

	private static function normalize_bool($v): ?string
	{
		$v = strtolower(trim((string) $v));
		if ($v === '' || $v === 'any') {
			return null;
		}
		if (in_array($v, ['1', 'true', 'yes', 'on'], true)) {
			return '1';
		}
		if (in_array($v, ['0', 'false', 'no', 'off'], true)) {
			return '0';
		}
		return null;
	}

	/**
	 * Resolve user IDs who have a specific Discourse badge id.
	 *
	 * @return int[]|null null means "no badge filter"
	 */
	private static function user_ids_with_badge(?int $badge_id): ?array
	{
		if (!$badge_id) {
			return null;
		}
		global $wpdb;
		$table = Sync::user_badges_table();

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT wp_user_id FROM {$table} WHERE discourse_badge_id = %d",
				$badge_id
			)
		);

		return array_map('intval', is_array($ids) ? $ids : []);
	}

	/**
	 * Discourse badge id for “Individual member” (directory “real” members).
	 * Set via filter mb_individual_member_badge_id, or resolved by name via mb_individual_member_badge_name.
	 */
	private static function individual_member_badge_id(): int
	{
		static $cached = null;
		if ($cached !== null) {
			return $cached;
		}
		$id = (int) apply_filters('mb_individual_member_badge_id', 0);
		if ($id > 0) {
			$cached = $id;
			return $cached;
		}
		$name = (string) apply_filters('mb_individual_member_badge_name', 'Individual member');
		if ($name === '') {
			$cached = 0;
			return $cached;
		}
		global $wpdb;
		$table = Sync::badges_table();
		$found = $wpdb->get_var($wpdb->prepare(
			"SELECT discourse_badge_id FROM {$table} WHERE LOWER(TRIM(name)) = LOWER(TRIM(%s)) LIMIT 1",
			$name
		));
		$cached = $found ? (int) $found : 0;
		return $cached;
	}

	/**
	 * Lowercased badge names to hide on directory cards (member duplicate of the star).
	 *
	 * @return string[]
	 */
	private static function directory_card_hidden_badge_names_lower(): array
	{
		$names = (array) apply_filters('mb_directory_hide_badge_names', [
			(string) apply_filters('mb_individual_member_badge_name', 'Individual member'),
			'Individual member',
			'Individual Member',
		]);
		$out = [];
		foreach ($names as $n) {
			$t = strtolower(trim((string) $n));
			if ($t !== '') {
				$out[$t] = true;
			}
		}
		return array_keys($out);
	}

	/**
	 * Discourse badge ids to omit from directory card badge strip (member badge duplicate of the star).
	 * Merges catalog lookup by name (case-insensitive), mb_directory_hidden_badge_ids, and individual_member_badge_id().
	 *
	 * @return int[]
	 */
	private static function directory_card_hidden_badge_ids(): array
	{
		$ids = [];
		$primary = self::individual_member_badge_id();
		if ($primary > 0) {
			$ids[] = $primary;
		}
		$lower = self::directory_card_hidden_badge_names_lower();
		if ($lower) {
			global $wpdb;
			$table = Sync::badges_table();
			$placeholders = implode(',', array_fill(0, count($lower), '%s'));
			$sql = $wpdb->prepare(
				"SELECT discourse_badge_id FROM {$table} WHERE LOWER(TRIM(name)) IN ({$placeholders})",
				...$lower
			);
			foreach ($wpdb->get_col($sql) as $bid) {
				$ids[] = (int) $bid;
			}
		}
		$extra = (array) apply_filters('mb_directory_hidden_badge_ids', []);
		foreach ($extra as $e) {
			$ids[] = (int) $e;
		}
		$ids = array_values(array_unique(array_filter($ids)));
		return $ids;
	}

	/**
	 * @param int[] $hidden_ids
	 * @param string[] $hidden_name_lower
	 */
	private static function directory_card_should_show_badge(array $b, array $hidden_ids, array $hidden_name_lower): bool
	{
		$id = (int) ($b['discourse_badge_id'] ?? 0);
		if ($id > 0 && in_array($id, $hidden_ids, true)) {
			return false;
		}
		$bn = strtolower(trim((string) ($b['name'] ?? '')));
		if ($bn !== '' && in_array($bn, $hidden_name_lower, true)) {
			return false;
		}
		return true;
	}

	/**
	 * All user IDs considered directory “members”: Individual member badge only (financial membership).
	 *
	 * @return int[]
	 */
	private static function directory_member_user_ids(): array
	{
		$bid = self::individual_member_badge_id();
		if ($bid <= 0) {
			return [];
		}
		return self::user_ids_with_badge($bid);
	}

	/**
	 * Map user_id => true for users who have the Individual member badge.
	 *
	 * @param int[] $user_ids
	 * @return array<int, true>
	 */
	private static function directory_member_map(array $user_ids): array
	{
		$user_ids = array_values(array_unique(array_filter(array_map('intval', $user_ids))));
		$out = [];
		if (!$user_ids) {
			return $out;
		}
		$bid = self::individual_member_badge_id();
		if ($bid <= 0) {
			return $out;
		}
		global $wpdb;
		$t = Sync::user_badges_table();
		$ph = implode(',', array_fill(0, count($user_ids), '%d'));
		$sql = $wpdb->prepare(
			"SELECT DISTINCT wp_user_id FROM {$t} WHERE discourse_badge_id = %d AND wp_user_id IN ({$ph})",
			array_merge([$bid], $user_ids)
		);
		foreach ($wpdb->get_col($sql) as $id) {
			$out[(int) $id] = true;
		}
		return $out;
	}

	/**
	 * Fetch badges for a set of WP user IDs in a single query.
	 *
	 * @param int[] $user_ids
	 * @return array<int, array<int, array{name:string,icon_url:string,granted_at:?string,discourse_badge_id:int}>>
	 */
	private static function badges_for_users(array $user_ids): array
	{
		$user_ids = array_values(array_filter(array_map('intval', $user_ids)));
		if (!$user_ids) {
			return [];
		}

		global $wpdb;
		$user_badges = Sync::user_badges_table();
		$badges = Sync::badges_table();

		$placeholders = implode(',', array_fill(0, count($user_ids), '%d'));
		$featured_ids = Shortcode::featured_group_ids();
		$feat_placeholders = implode(',', array_fill(0, count($featured_ids), '%d'));

		$sql = $wpdb->prepare(
			"SELECT ub.wp_user_id, ub.discourse_badge_id, ub.granted_at,
			        b.name, b.icon_url, b.badge_type, b.badge_grouping_id
			 FROM {$user_badges} ub
			 JOIN {$badges} b ON b.discourse_badge_id = ub.discourse_badge_id
			 WHERE ub.wp_user_id IN ({$placeholders})
			   AND b.badge_grouping_id IN ({$feat_placeholders})",
			...array_merge($user_ids, $featured_ids)
		);

		$rows = $wpdb->get_results($sql);
		$out = [];
		if (is_array($rows)) {
			foreach ($rows as $r) {
				$uid = (int) ($r->wp_user_id ?? 0);
				if (!$uid) {
					continue;
				}
				if (!isset($out[$uid])) {
					$out[$uid] = [];
				}
				$out[$uid][] = [
					'discourse_badge_id' => (int) ($r->discourse_badge_id ?? 0),
					'name' => (string) ($r->name ?? ''),
					'icon_url' => (string) ($r->icon_url ?? ''),
					'badge_type' => (int) ($r->badge_type ?? 3),
					'badge_grouping_id' => (int) ($r->badge_grouping_id ?? 0),
					'granted_at' => isset($r->granted_at) ? (string) $r->granted_at : null,
				];
			}
		}

		foreach ($out as $uid => $user_badge_list) {
			$out[$uid] = Shortcode::sort_badges($user_badge_list);
		}

		return $out;
	}

	/**
	 * Render the directory with filter UI and results.
	 *
	 * Query params:
	 * - q: free text search (name / username / email). (We avoid `s` because WordPress treats it as global search.)
	 * - badge_id: Discourse badge id
	 * - for_hire: 1|0
	 * - user_type: member (Individual member badge only) | non_member (excludes badge holders)
	 * - contributor: 1|0
	 * - language: e.g. en, fr (stored as CSV in usermeta)
	 * - country: e.g. US, DE
	 * - timezone: IANA tz
	 * - pg: page number
	 */
	public static function render($atts = []): string
	{
		wp_enqueue_style('mb-directory');
		wp_enqueue_style('mb-badges'); // reuse badge chip styles

		$atts = shortcode_atts([
			'per_page' => 24,
		], $atts, 'mautic_user_directory');

		// Use `q` for directory search; accept legacy `s` if present.
		$search = sanitize_text_field((string) self::qp('q', ''));
		if ($search === '') {
			$search = sanitize_text_field((string) self::qp('s', ''));
		}
		$badge_id = (int) self::qp('badge_id', 0);
		$for_hire = self::normalize_bool(self::qp('for_hire', ''));
		$contributor = self::normalize_bool(self::qp('contributor', ''));
		$user_type = sanitize_key((string) self::qp('user_type', ''));
		$language = sanitize_key((string) self::qp('language', ''));
		$country = strtoupper(sanitize_key((string) self::qp('country', '')));
		$timezone = sanitize_text_field((string) self::qp('timezone', ''));

		$page = max(1, (int) self::qp('pg', 1));
		$per_page = max(1, min(100, (int) $atts['per_page']));

		$badge_user_ids = self::user_ids_with_badge($badge_id);

		$meta_query = ['relation' => 'AND'];

		// Directory opt-out: if a user has the opt-out usermeta set, I exclude them from the directory entirely.
		$opt_out_meta_key = (string) apply_filters('mb_directory_opt_out_meta_key', 'directory_opt_out');
		$opt_out_meta_yes = (string) apply_filters('mb_directory_opt_out_meta_yes_value', '1');
		$meta_query[] = [
			'relation' => 'OR',
			[
				'key' => $opt_out_meta_key,
				'compare' => 'NOT EXISTS',
			],
			[
				'key' => $opt_out_meta_key,
				'value' => $opt_out_meta_yes,
				'compare' => '!=',
			],
		];

		if ($for_hire !== null) {
			// Default: treat missing value as "not for hire" (0) until we decide the real source of truth.
			if ($for_hire === '0') {
				$meta_query[] = [
					'relation' => 'OR',
					[
						'key' => 'directory_for_hire',
						'compare' => 'NOT EXISTS',
					],
					[
						'key' => 'directory_for_hire',
						'value' => '0',
						'compare' => '=',
					],
				];
			} else {
				$meta_query[] = [
					'key' => 'directory_for_hire',
					'value' => '1',
					'compare' => '=',
				];
			}
		}
		if ($contributor !== null) {
			// Default: treat missing value as "not a contributor" (0).
			if ($contributor === '0') {
				$meta_query[] = [
					'relation' => 'OR',
					[
						'key' => 'directory_contributor',
						'compare' => 'NOT EXISTS',
					],
					[
						'key' => 'directory_contributor',
						'value' => '0',
						'compare' => '=',
					],
				];
			} else {
				$meta_query[] = [
					'key' => 'directory_contributor',
					'value' => '1',
					'compare' => '=',
				];
			}
		}
		if ($language !== '') {
			// MVP: stored as CSV in usermeta (e.g. "en,fr,de"); use LIKE.
			$meta_query[] = [
				'key' => 'directory_languages',
				'value' => $language,
				'compare' => 'LIKE',
			];
		}
		if ($country !== '') {
			$meta_query[] = [
				'key' => 'directory_country',
				'value' => $country,
				'compare' => '=',
			];
		}
		if ($timezone !== '') {
			$meta_query[] = [
				'key' => 'directory_timezone',
				'value' => $timezone,
				'compare' => '=',
			];
		}

		$members_first = ($user_type !== 'member' && $user_type !== 'non_member');
		$args = [
			'number' => $members_first ? -1 : $per_page,
			'paged' => $members_first ? 1 : $page,
			'orderby' => 'display_name',
			'order' => 'ASC',
			'fields' => ['ID', 'display_name', 'user_email', 'user_login'],
		];
		if ($search !== '') {
			$args['search'] = '*' . $search . '*';
			$args['search_columns'] = ['user_login', 'user_nicename', 'display_name', 'user_email'];
		}
		if (count($meta_query) > 1) {
			$args['meta_query'] = $meta_query;
		}

		$include_sets = [];
		if (is_array($badge_user_ids)) {
			$include_sets[] = $badge_user_ids ?: [0];
		}
		if ($user_type === 'member') {
			$mids = self::directory_member_user_ids();
			$include_sets[] = $mids ? $mids : [0];
		}
		if (count($include_sets) > 0) {
			$merged = $include_sets[0];
			for ($i = 1, $n = count($include_sets); $i < $n; $i++) {
				$merged = array_values(array_intersect($merged, $include_sets[$i]));
			}
			$args['include'] = $merged ?: [0];
		}
		if ($user_type === 'non_member') {
			$ex = self::directory_member_user_ids();
			if ($ex) {
				$args['exclude'] = $ex;
			}
		}

		$q = new \WP_User_Query($args);
		$users = $q->get_results();
		$total = (int) $q->get_total();

		if ($members_first && $users) {
			$sort_ids = array_map(static fn($u) => (int) $u->ID, $users);
			$member_map = self::directory_member_map($sort_ids);
			usort($users, static function ($a, $b) use ($member_map) {
				$a_member = !empty($member_map[(int) $a->ID]);
				$b_member = !empty($member_map[(int) $b->ID]);
				if ($a_member !== $b_member) {
					return $a_member ? -1 : 1;
				}
				return strcasecmp($a->display_name, $b->display_name);
			});
			$total = count($users);
			$users = array_slice($users, ($page - 1) * $per_page, $per_page);
		}

		$total_pages = (int) ceil($total / $per_page);

		$user_ids = array_map(static fn($u) => (int) $u->ID, is_array($users) ? $users : []);

		// Just-in-time refresh for a small number of users whose cache is missing or stale (>24h).
		$max_jit = 3;
		$now = time();
		$stale_before = $now - DAY_IN_SECONDS;
		$jit_count = 0;
		foreach ($user_ids as $uid) {
			if ($jit_count >= $max_jit) {
				break;
			}
			$last = (int) get_user_meta($uid, 'discourse_badges_updated_at', true);
			if ($last >= $stale_before) {
				continue;
			}
			$mapped = (string) get_user_meta($uid, 'discourse_username', true);
			// Fall back to WP login if no explicit mapping yet.
			$user_obj = get_user_by('ID', $uid);
			$username = $mapped !== '' ? $mapped : ($user_obj ? (string) $user_obj->user_login : '');
			if ($username === '') {
				continue;
			}
			$res = Sync::refresh_user_by_username($username, $uid);
			if (!is_wp_error($res)) {
				$jit_count++;
			}
		}

		$badges_by_user = self::badges_for_users($user_ids);
		$member_map = self::directory_member_map($user_ids);
		$hide_member_badge_on_card = (bool) apply_filters('mb_directory_hide_individual_member_badge', true);
		$directory_hidden_badge_ids = self::directory_card_hidden_badge_ids();
		$directory_hidden_badge_names_lower = self::directory_card_hidden_badge_names_lower();

		// Badge filter options (from cached catalog).
		global $wpdb;
		$badge_catalog = $wpdb->get_results("SELECT discourse_badge_id, name FROM " . Sync::badges_table() . " ORDER BY name ASC");

		// Build action URL (keep on same page). Keep this unescaped until output.
		$action_raw = remove_query_arg(['pg']);

		ob_start();
		?>
		<div class="mb-directory">
			<form class="mb-directory-filters" method="get" action="<?php echo esc_url($action_raw); ?>">
				<?php
				// Preserve unrelated query params (like Elementor preview params).
				foreach ($_GET as $k => $v) {
					if (in_array($k, ['q','s','badge_id','for_hire','user_type','contributor','language','country','timezone','pg'], true)) {
						continue;
					}
					if (is_scalar($v)) {
						echo '<input type="hidden" name="' . esc_attr((string) $k) . '" value="' . esc_attr((string) wp_unslash($v)) . '">';
					}
				}
				?>

				<div class="mb-field">
					<label>Search</label>
					<input type="search" name="q" value="<?php echo esc_attr($search); ?>" placeholder="Name, username, or email" />
				</div>

				<div class="mb-field">
					<label>Badge</label>
					<select name="badge_id">
						<option value="0">Any</option>
						<?php if (is_array($badge_catalog)): ?>
							<?php foreach ($badge_catalog as $b): ?>
								<?php
								$bid = (int) ($b->discourse_badge_id ?? 0);
								$bn = (string) ($b->name ?? '');
								if (!$bid || $bn === '') continue;
								?>
								<option value="<?php echo esc_attr((string) $bid); ?>" <?php selected($badge_id, $bid); ?>>
									<?php echo esc_html($bn); ?>
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>

				<div class="mb-field">
					<label>Availability</label>
					<select name="for_hire">
						<option value="any" <?php selected($for_hire, null); ?>>Any</option>
						<option value="1" <?php selected($for_hire, '1'); ?>>For hire</option>
						<option value="0" <?php selected($for_hire, '0'); ?>>Not for hire</option>
					</select>
				</div>

				<div class="mb-field">
					<label>User type</label>
					<select name="user_type">
						<option value="">Any</option>
						<option value="member" <?php selected($user_type, 'member'); ?>>Member</option>
						<option value="non_member" <?php selected($user_type, 'non_member'); ?>>Non-member</option>
					</select>
				</div>

				<div class="mb-field">
					<label>Contributor</label>
					<select name="contributor">
						<option value="any" <?php selected($contributor, null); ?>>Any</option>
						<option value="1" <?php selected($contributor, '1'); ?>>Yes</option>
						<option value="0" <?php selected($contributor, '0'); ?>>No</option>
					</select>
				</div>

				<div class="mb-field">
					<label>Languages (code)</label>
					<input type="text" name="language" value="<?php echo esc_attr($language); ?>" placeholder="en" />
				</div>

				<div class="mb-field">
					<label>Country (code)</label>
					<input type="text" name="country" value="<?php echo esc_attr($country); ?>" placeholder="US" />
				</div>

				<div class="mb-field">
					<label>Timezone</label>
					<input type="text" name="timezone" value="<?php echo esc_attr($timezone); ?>" placeholder="America/New_York" />
				</div>

				<div class="mb-actions">
					<button type="submit">Apply</button>
					<a class="mb-clear" href="<?php echo esc_url(remove_query_arg(['q','s','badge_id','for_hire','user_type','contributor','language','country','timezone','pg'])); ?>">Clear</a>
				</div>
			</form>

			<div class="mb-directory-meta">
				<?php echo esc_html($total . ' users'); ?>
			</div>

			<div class="mb-directory-grid">
				<?php if (!$users): ?>
					<div class="mb-directory-empty">
						<p><?php esc_html_e('No users matched your filters.', 'mautic-badges'); ?></p>
						<a href="<?php echo esc_url(remove_query_arg(['q','s','badge_id','for_hire','user_type','contributor','language','country','timezone','pg'])); ?>">
							<?php esc_html_e('Clear all filters', 'mautic-badges'); ?>
						</a>
					</div>
				<?php else: ?>
					<?php foreach ($users as $u): ?>
						<?php
						$uid = (int) $u->ID;
						$name = (string) $u->display_name;
						$profile_url = get_author_posts_url($uid);
						$avatar = get_avatar_url($uid, ['size' => 96]);
						$meta_for_hire = get_user_meta($uid, 'directory_for_hire', true);
						$meta_country = (string) get_user_meta($uid, 'directory_country', true);
						$meta_tz = (string) get_user_meta($uid, 'directory_timezone', true);
						$user_badges = $badges_by_user[$uid] ?? [];
						if ($hide_member_badge_on_card && $user_badges) {
							$user_badges = array_values(array_filter(
								$user_badges,
								function ($b) use ($directory_hidden_badge_ids, $directory_hidden_badge_names_lower) {
									return self::directory_card_should_show_badge($b, $directory_hidden_badge_ids, $directory_hidden_badge_names_lower);
								}
							));
						}
						$is_member = !empty($member_map[$uid]);
						$card_class = 'mb-user-card' . ($is_member ? ' mb-is-member' : '');
						?>
						<div class="<?php echo esc_attr($card_class); ?>">
							<?php if ($is_member): ?>
								<div class="mb-member-flag" aria-hidden="true">
									<span class="mb-member-star">★</span>
									<span class="mb-member-tooltip"><?php esc_html_e('Mautic member', 'mautic-badges'); ?></span>
								</div>
							<?php endif; ?>
							<a class="mb-user-main" href="<?php echo esc_url($profile_url); ?>">
								<img class="mb-user-avatar" src="<?php echo esc_url($avatar); ?>" alt="" loading="lazy" />
								<div class="mb-user-title">
									<div class="mb-user-name"><?php echo esc_html($name); ?></div>
									<div class="mb-user-sub">
										<?php if ($meta_country !== ''): ?>
											<span><?php echo esc_html($meta_country); ?></span>
										<?php endif; ?>
										<?php if ($meta_tz !== ''): ?>
											<span><?php echo esc_html($meta_tz); ?></span>
										<?php endif; ?>
										<?php if ($meta_for_hire === '1'): ?>
											<span class="mb-tag mb-tag-hire"><?php esc_html_e('For hire', 'mautic-badges'); ?></span>
										<?php endif; ?>
									</div>
								</div>
							</a>

							<?php if ($user_badges): ?>
								<?php
								$visible_max = 5;
								$badge_total = count($user_badges);
								$visible     = array_slice($user_badges, 0, $visible_max);
								$overflow    = $badge_total - count($visible);
								?>
								<div class="mb-badges-compact">
								<?php foreach ($visible as $b): ?>
									<?php
									$bn = esc_html((string) ($b['name'] ?? ''));
									$bt = (int) ($b['badge_type'] ?? 3);
									$tc = $bt === 1 ? 'mb-tier-gold' : ($bt === 2 ? 'mb-tier-silver' : 'mb-tier-bronze');
									?>
									<span class="mb-badge-dot <?php echo $tc; ?>" title="<?php echo $bn; ?>">
										<?php echo Shortcode::render_icon((string) ($b['icon_url'] ?? ''), 'mb-dot-icon', 22); ?>
									</span>
								<?php endforeach; ?>
								<?php if ($overflow > 0): ?>
									<a href="<?php echo esc_url($profile_url); ?>" class="mb-badge-more" title="<?php esc_attr_e('View all badges', 'mautic-badges'); ?>">+<?php echo $overflow; ?></a>
								<?php endif; ?>
										<a href="<?php echo esc_url($profile_url); ?>" class="mb-card-more"><?php esc_html_e('View profile', 'mautic-badges'); ?></a>
								</div>
									<?php else: ?>
										<div class="mb-badges-compact mb-badges-empty">
											<span class="mb-badges-empty-text">
												<?php esc_html_e('No highlights yet', 'mautic-badges'); ?>
											</span>
											<a href="<?php echo esc_url($profile_url); ?>" class="mb-card-more"><?php esc_html_e('View profile', 'mautic-badges'); ?></a>
										</div>
									<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<?php if ($total_pages > 1): ?>
				<div class="mb-pagination">
					<?php
					$base_args = $_GET;
					$base_args['pg'] = '%#%';
					echo paginate_links([
						'base' => esc_url_raw(add_query_arg($base_args, $action_raw)),
						'format' => '',
						'current' => $page,
						'total' => $total_pages,
					]);
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

