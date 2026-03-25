# Mautic.org WordPress (Hello Elementor + Mautic theme/plugins)

This repository contains the WordPress code we use on mautic.org, built on the Hello Elementor template plus the Mautic theme and a small set of custom plugins.

## What’s inside

- **Theme:** `wp-content/themes/mautic-theme/` (Mautic theme **v1.0.0**; child of `hello-elementor`)
- **Plugin:** `wp-content/plugins/mautic-badges/` (Mautic Badges **v1.0.0**)
  - Syncs **Discourse** badges into WordPress
  - Renders badges on profiles
  - Provides the filterable **Mautician directory**

## Integrations (plugin)

The `mautic-badges` plugin expects these constants to be defined in `wp-config.php`:

- `DISCOURSE_BASE_URL`
- `DISCOURSE_API_KEY`
- `DISCOURSE_API_USERNAME`
- `MB_INGEST_TOKEN` (used to authorize the webhook / n8n relay)

Webhook / n8n relay: POST to `/wp-json/mautic-badges/v1/ingest` with header:
`X-Bridge-Token: <MB_INGEST_TOKEN>`
and JSON body like `{"username":"discourse_username"}`.

## Notes
- Theme / plugin code lives under `wp-content/` and changes should ship through WordPress (staging first, then production).
- The plugin includes an admin page to trigger catalog/user refreshes when needed.

