# AJAX Snippets

Quickly run and test PHP snippets in the WordPress admin, with a batch mode for bulk operations.

## Requirements

- WordPress >= 4.9
- PHP >= 7.0

## Installation

1. Upload the plugin to `wp-content/plugins/ajax-snippets`.
2. Activate it in the WordPress admin.

## GitHub updates

The plugin uses `plugin-update-checker`.

## Usage

The admin menu adds two pages:

- **Ajax Snippets** - single snippets.
- **Batch Runner** - process multiple items in batches.

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
