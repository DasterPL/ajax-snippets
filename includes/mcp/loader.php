<?php

defined('ABSPATH') || exit;

/**
 * Entry point for the MCP integration. Loaded unconditionally from the plugin
 * bootstrap so the settings page and activation hooks always exist; the
 * runtime REST surface only activates when the `enabled` option is on.
 */

require_once __DIR__ . '/setup.php';
require_once __DIR__ . '/snippet-core.php';
require_once __DIR__ . '/keys.php';
require_once __DIR__ . '/registry-client.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/heartbeat.php';
require_once __DIR__ . '/fs.php';
require_once __DIR__ . '/rest-routes.php';
// auto-register registers an admin_init hook with its own internal guards
// (skips during AJAX/REST/cron). Loading it unconditionally keeps the
// functions reachable from wp-cli `wp eval` and other non-admin contexts.
require_once __DIR__ . '/auto-register.php';

if (is_admin()) {
    // The settings page hides or shows itself with the rest of the plugin —
    // see includes/visibility.php (AJAX_SNIPPETS_REVEAL). Without the reveal
    // the integration runs silently and the client never sees a submenu.
    require_once __DIR__ . '/admin-ui.php';
}
