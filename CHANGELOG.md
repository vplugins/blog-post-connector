# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.6]
- Added `/webhook/enable` and `/webhook/disable` API endpoints so Social Marketing can toggle webhook delivery for a connection without deactivating the plugin.
- Both endpoints are idempotent and return the current webhook state (`enabled`/`disabled`).
- Webhook delivery now checks this state before firing, preventing webhooks from continuing to fire against dead/disconnected connections.

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
