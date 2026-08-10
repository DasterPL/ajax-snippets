# AJAX Snippets

Quickly run and test PHP snippets in the WordPress admin, with a batch mode for bulk operations.

## Requirements

- WordPress >= 6.6
- PHP >= 7.0

## Installation

1. Upload the plugin to `wp-content/plugins/ajax-snippets`.
2. Activate it in the WordPress admin.

## GitHub updates

The plugin uses `plugin-update-checker`. Hiding (see below) does not touch
updates: a new release still shows on Dashboard → Updates and installs normally.

## Visibility

The plugin is hidden by default: no admin menu and no row on the Plugins screen.
It keeps running (AJAX, MCP, cron) — it is only invisible in wp-admin. Reveal it
with `AJAX_SNIPPETS_REVEAL` in `wp-config.php`:

```php
define('AJAX_SNIPPETS_REVEAL', true);   // visible to everyone who can see it
define('AJAX_SNIPPETS_REVEAL', 5);      // visible only to user ID 5
define('AJAX_SNIPPETS_REVEAL', [1, 5]); // ... or to any of those user IDs
define('AJAX_SNIPPETS_REVEAL', '1,5');  // same, as a CSV string
```

`true` means "any user", `1` means "user ID 1". Capability checks
(`manage_options`) still apply on top of this.

## Usage

When revealed, the admin menu adds three pages:

- **Ajax Snippets** - single snippets.
- **Batch Runner** - process multiple items in batches.
- **MCP** - status and settings of the MCP integration.

## Batch mode

- **Fetch data**: code must return an array of items.
- **Batch operations**: executed for each item.
- **Items / AJAX**: how many items per request.
- **Pause/Resume**: pause and continue after a page refresh.

## Local storage

Editor contents are stored in the browser `localStorage`.

## Translations

The default UI is English. Polish translations live in `languages/ajax-snippets-pl_PL.po`.

## Structure

```
ajax-snippets.php
includes/
  assets.php
  ajax-handlers.php
  admin-menu.php
  visibility.php
  updater.php
  snippet-templates.php
admin/
  admin-page.php
  batch-page.php
assets/
  css/
  js/
```

## Security

The plugin executes PHP via `eval` and is intended for administrators only.
