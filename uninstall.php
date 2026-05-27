<?php

/**
 * Fired when the user deletes the plugin (Plugins → Delete). Removes all
 * traces of the MCP integration:
 *
 *   1. Best-effort: tells the registry to soft-disable this site so it stops
 *      showing up in the bridge's list_sites. Audit history on the registry
 *      side is kept; the site row stays with status='disabled'.
 *   2. Drops the two MCP-specific tables (wp_ajax_snippets_audit and
 *      wp_ajax_snippets_mcp_nonces).
 *   3. Deletes every ajax_snippets_mcp_* option.
 *   4. Clears any scheduled cron hooks owned by the integration.
 *
 * Deactivation alone (Plugins → Deactivate) does NOT trigger this script —
 * deactivate is reversible by design, only uninstall does a full wipe.
 *
 * The pre-MCP parts of the plugin (snippet history, user preferences) don't
 * have any state in the database, so there's nothing else to clean.
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

require_once __DIR__ . '/includes/mcp/setup.php';
require_once __DIR__ . '/includes/mcp/keys.php';
require_once __DIR__ . '/includes/mcp/registry-client.php';

// 1. Best-effort deregister with the registry. Don't let failure block the
//    rest of the cleanup — the user already decided this plugin goes.
if (ajax_snippets_mcp_has_keypair()) {
    try {
        ajax_snippets_mcp_registry_call('POST', '/v1/sites/deregister', [
            'reason' => 'plugin_uninstalled',
        ]);
    } catch (\Throwable $e) {
        error_log('[ajax-snippets-mcp] uninstall deregister failed: ' . $e->getMessage());
    }
}

// 2. Drop tables.
global $wpdb;
$audit_table  = ajax_snippets_mcp_audit_table();
$nonce_table  = ajax_snippets_mcp_nonce_table();
$wpdb->query("DROP TABLE IF EXISTS {$audit_table}");
$wpdb->query("DROP TABLE IF EXISTS {$nonce_table}");

// 3. Delete options. Listed explicitly rather than wildcard delete to avoid
//    accidentally removing options from other plugins that happen to share
//    a prefix (none do today, but defense in depth).
$options_to_delete = [
    AJAX_SNIPPETS_MCP_OPT_ENABLED,
    AJAX_SNIPPETS_MCP_OPT_SECRET_KEY,
    AJAX_SNIPPETS_MCP_OPT_PUBKEY,
    AJAX_SNIPPETS_MCP_OPT_FP,
    AJAX_SNIPPETS_MCP_OPT_STATUS,
    AJAX_SNIPPETS_MCP_OPT_ADMIN_KEYS,
    AJAX_SNIPPETS_MCP_OPT_KEYS_VERSION,
    AJAX_SNIPPETS_MCP_OPT_KEYS_UPDATED,
    AJAX_SNIPPETS_MCP_OPT_RUN_AS_USER,
    AJAX_SNIPPETS_MCP_OPT_DB_VERSION,
    'ajax_snippets_mcp_autoregister_done',
    'ajax_snippets_mcp_autoregister_backoff_until',
];
foreach ($options_to_delete as $opt) {
    delete_option($opt);
}

// 4. Clear scheduled hooks.
wp_clear_scheduled_hook(AJAX_SNIPPETS_MCP_CRON_HEARTBEAT);
wp_clear_scheduled_hook('ajax_snippets_mcp_refresh_keys_now');
