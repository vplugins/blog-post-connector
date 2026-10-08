# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.8]
- Fixed the self-updater moving other plugins and themes into the Blog Post Connector folder. Its `upgrader_post_install` handler ran for every plugin and theme install and update, and on sites where Blog Post Connector was not in `wp-content/plugins/blog-post-connector/` (for example installed by uploading the release zip, which creates `sm-post-connector-package/`) the item being installed (for example WooCommerce) ended up in that folder and showed as missing. It now acts only when Blog Post Connector itself is updated.
- Release zips once again hold the plugin in a `blog-post-connector/` folder, as up to 1.0.3, so uploading the zip installs it in `wp-content/plugins/blog-post-connector/`. The 1.0.6 and 1.0.7 zips (`sm-post-connector-package.zip`) had no folder, so WordPress installed them in `sm-post-connector-package/`. Those installs keep updating in place.
- A failed file move during the plugin's own update now returns an error instead of reporting success.
- `/create-post` now accepts titles that already exist. WordPress gives the new post a unique slug (`my-post-2`, `my-post-3`), matching the dashboard. The `post_with_title_exists` error has been removed.
- Added unit tests for the updater's post-install handler.

## [1.0.7]
- The `/status` endpoint now returns `default_author`, as `{ "ID", "data": { "display_name" } }`: the Default Author saved in Post Settings, or the site default when none is saved. `null` only when the site has no administrator.
- A site default now exists without saving anything: when no valid Default Author is saved, the administrator with the lowest user ID is used. `/status`, `/create-post` and the Post Settings dropdown all resolve the same user.
- `/create-post` requests without an `author` are assigned to that default. Previously they fell back to user ID 1, which fails with `invalid_author_id` on sites where that user does not exist.
- The Default Author dropdown in Post Settings now preselects the author in effect and marks it as the site default when nothing is saved, instead of silently showing the first name in the list.
- Each `/status` author entry now carries only `ID` and `data.display_name`. Previously every entry was a full WordPress user object, including the password hash, email and activation key.
- Added unit tests for the `/status` author fields, the create-post author fallback and the Default Author dropdown.

## [1.0.6]
- Added `/webhook/enable` and `/webhook/disable` API endpoints so Social Marketing can toggle webhook delivery for a connection without deactivating the plugin.
- Both endpoints are idempotent and return the current webhook state (`enabled`/`disabled`).
- Webhook delivery now checks this state before firing, preventing webhooks from continuing to fire against dead/disconnected connections.
- Plugin lifecycle webhooks (`activated`, `deactivated`, `deleted`) are still delivered while disabled, so a muted site can be detected as available again.
- The `/status` endpoint now reports the current webhook state as `webhook_status`.
- Fixed the PHPUnit bootstrap, which aborted the whole suite before any test ran, and corrected a `TokenTest` mock that no longer matched `validate_token()`.
- Pinned `10up/phpcs-composer` to `^3.0`; the previous `dev-master` constraint no longer satisfied the lock file and pulled a `wp-coding-standards/wpcs` release blocked by a security advisory, breaking `composer install`.

## [1.0.5]
- Added optional `slug` parameter to Create Post and Update Post endpoints for custom permalinks.
- PHP 8.4 compatibility: hardened token validation, replaced deprecated `get_page_by_title()` with `WP_Query`, guarded `strtotime()`/`date()` and term lookups against null/false returns.
- Updated PHPCS lint range to `7.4-8.4`.

## [1.0.4]
- Display a warning indicating that the plugin is not compatible with WordPress Multisite installations.

## [1.0.3]
- Added Logs Tab to view recent API requests and responses with pagination.
- New Download Logs button to export logs in JSON format with system info (plugins, theme, permalink).
- Added Log Settings to enable/disable logging and set log retention period (auto cleanup included).

## [1.0.2]
- Introduced ID fields for authors and categories, returned tags as arrays, and updated API documentation.
- Fixed featured image payload handling and added `CHANGELOG.md` file.

## [1.0.1]
### Added
- Introduced hooks for categories, tags, authors, and additional functionalities with error logging implemented.
- Added hooks for plugin activation, deactivation, and deletion.

## [1.0.0Beta] 
### Added
- Initial release of the plugin with core functionality.
