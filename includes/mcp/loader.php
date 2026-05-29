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
    // Admin UI is opt-in: define('AJAX_SNIPPETS_MCP_SHOW_UI', true) in wp-config.php
    // exposes the settings page (status, audit log, manual register/refresh actions).
    // Without it the integration runs silently — the client never sees a submenu.
    if (defined('AJAX_SNIPPETS_MCP_SHOW_UI') && constant('AJAX_SNIPPETS_MCP_SHOW_UI')) {
        require_once __DIR__ . '/admin-ui.php';
    }
}
