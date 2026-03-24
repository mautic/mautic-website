# Mautic Badges

WordPress plugin that syncs Discourse forum badges into WordPress and displays them on user profiles and a filterable member directory.

## How It Works

Discourse is the source of truth. WordPress caches badge data locally for fast rendering.

```
Discourse webhook -> n8n (HMAC verify) -> POST /wp-json/mautic-badges/v1/ingest
                                           -> WP pulls from Discourse API
                                           -> Caches in local DB tables
                                           -> Renders via shortcodes
```

Badges are never trusted from webhook payloads directly. Webhooks are signals; the plugin always pulls canonical data from the Discourse API.

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Discourse forum with API access
- n8n instance (for webhook relay) or direct Discourse webhook

## Installation

1. Copy the `mautic-badges` directory into `wp-content/plugins/`.
2. Add required constants to `wp-config.php`:

```php
define('DISCOURSE_BASE_URL', 'https://forum.mautic.org');
define('DISCOURSE_API_KEY', 'your-discourse-api-key');
define('DISCOURSE_API_USERNAME', 'mautibot');
define('MB_INGEST_TOKEN', 'shared-secret-for-n8n');
```

3. Activate the plugin. Database tables are created automatically.

## Configuration

| Constant | Required | Description |
|----------|----------|-------------|
| `DISCOURSE_BASE_URL` | Yes | Discourse forum URL (no trailing slash) |
| `DISCOURSE_API_KEY` | Yes | API key for the service account |
| `DISCOURSE_API_USERNAME` | Yes | Discourse username for API calls |
| `MB_INGEST_TOKEN` | Yes | Shared secret for webhook authentication |
| `MB_DELETE_DATA_ON_UNINSTALL` | No | Set to `true` to delete tables on uninstall |

## Shortcodes

### `[mautic_badges]`

Displays badges for a user.

| Attribute | Default | Description |
|-----------|---------|-------------|
| `user_id` | `0` | WordPress user ID. 0 = current author or logged-in user |
| `limit` | `0` | Max badges to display. 0 = show all |
| `show_dates` | `0` | Set to `1` to show grant dates |
| `empty` | `No badges yet.` | Message when user has no badges |

```
[mautic_badges show_dates="1" limit="5"]
```

Legacy alias `[discourse_badges]` is also supported.

### `[mautic_user_directory]`

Renders a filterable user directory with badge display.

| Attribute | Default | Description |
|-----------|---------|-------------|
| `per_page` | `24` | Users per page |

Supports URL query parameters for filtering: `q`, `badge_id`, `for_hire`, `user_type`, `contributor`, `language`, `country`, `timezone`, `pg`.

```
[mautic_user_directory per_page="12"]
```

## REST API

### POST `/wp-json/mautic-badges/v1/ingest`

Webhook endpoint for n8n. Triggers a badge refresh for a specific user.

**Headers:** `X-Bridge-Token: <MB_INGEST_TOKEN>`
**Body:** `{"username": "discourse_username"}`
**Response:** `{"ok": true}`

### POST `/wp-json/mautic-badges/v1/refresh`

Admin-only manual refresh. Requires `manage_options` capability and WordPress authentication.

**Body (catalog):** `{"mode": "catalog"}`
**Body (user):** `{"mode": "user", "username": "discourse_username"}`

## Admin Tools

Navigate to **Tools -> Mautic Badges** in WP Admin to:
- View configuration status
- See shortcode and endpoint reference
- Trigger batch refresh for all mapped users

## User Mapping

Each WordPress user can have a `discourse_username` meta field linking them to their Discourse account. If not set, the plugin falls back to the WordPress login name.

## Directory Opt-out (developer)
If you want a user to not appear in the Mautician directory, I added an opt-out flag that you can set via usermeta.

- If usermeta `directory_opt_out` is set to `1`, the user will be excluded from `[mautic_user_directory]`.
- The meta key and “yes” value are filterable:
  - `mb_directory_opt_out_meta_key`
  - `mb_directory_opt_out_meta_yes_value`

Example (developer snippet):
```php
update_user_meta($user_id, 'directory_opt_out', 1);
```

## Cron

A nightly cron job (`mb_nightly_refresh_mapped_users`) refreshes badges for all mapped users in batches. Automatically scheduled on activation and cleared on deactivation.

## Database Tables

- `{prefix}discourse_badges` — Badge catalog from Discourse
- `{prefix}discourse_user_badges` — User-to-badge assignments

Tables are created on activation and optionally dropped on uninstall (requires `MB_DELETE_DATA_ON_UNINSTALL`).

## File Structure

```
mautic-badges/
├── mautic-badges.php           Main plugin bootstrap
├── uninstall.php               Opt-in data cleanup
├── README.md                   This file
├── mautic-badges-scope-doc.md  Full project scope
├── assets/
│   ├── badges.css              Badge chip styles
│   └── directory.css           Directory grid styles
└── includes/
    ├── Admin.php               WP Admin tools page
    ├── Client.php              Discourse API client
    ├── Directory.php           User directory shortcode
    ├── Installer.php           DB table creation
    ├── ProfileHook.php         Author archive injection
    ├── Rest.php                REST endpoint registration
    ├── Shortcode.php           Badge display shortcode
    └── Sync.php                Sync orchestration
```

## Security

- Token authentication with timing-safe comparison (`hash_equals`)
- HMAC verification in n8n layer
- Capability checks on admin endpoints
- Nonce verification on admin forms
- Prepared statements for all database queries
- Output escaping on all rendered content
- Secrets stored in `wp-config.php`, never in the database

## License

GPLv2 or later.
