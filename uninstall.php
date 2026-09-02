<?php

/**
 * Fired when the user deletes the plugin (Plugins → Delete). Wipes every
 * trace of the plugin's state from the database:
 *
 *   1. MCP: best-effort POST /v1/sites/deregister so the registry soft-disables
 *      this site immediately instead of waiting for last_seen staleness.
 *   2. MCP: drops the two MCP tables (wp_ajax_snippets_audit, wp_ajax_snippets_mcp_nonces).
 *   3. MCP: deletes every ajax_snippets_mcp_* option (explicit list).
 *   4. MCP: clears scheduled cron hooks (heartbeat + async key refresh).
 *   5. Core: clears the batch-runner transients used by both the admin UI and
 *      the MCP REST endpoint (`ajax-snippet-batch-{data,index,prev}_<uid>`).
 *      These are per-user, so we DELETE them in bulk via the options table.
 *
 * Deactivation alone (Plugins → Deactivate) does NOT trigger this script —
 * deactivate is reversible by design, only uninstall does a full wipe.
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
        ajax_snippets_mcp_debug_log('[ajax-snippets-mcp] uninstall deregister failed: ' . $e->getMessage());
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
    AJAX_SNIPPETS_MCP_OPT_REGISTERED_URL,
    AJAX_SNIPPETS_MCP_OPT_ORIGIN_HOST_HASH,
    'ajax_snippets_mcp_autoregister_done',
    'ajax_snippets_mcp_autoregister_backoff_until',
];
foreach ($options_to_delete as $opt) {
    delete_option($opt);
}

// 4. Clear scheduled hooks.
wp_clear_scheduled_hook(AJAX_SNIPPETS_MCP_CRON_HEARTBEAT);
wp_clear_scheduled_hook('ajax_snippets_mcp_refresh_keys_now');

// 5. Wipe batch-runner transients from both code paths (admin AJAX + MCP REST).
//    Transients are stored as `_transient_<key>` and `_transient_timeout_<key>`
//    rows in wp_options. The keys include the user id suffix, so we wildcard.
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '\\_transient\\_ajax-snippet-batch-%'
        OR option_name LIKE '\\_transient\\_timeout\\_ajax-snippet-batch-%'"
);

// In a multisite install the same plugin can be deactivated network-wide; the
// per-site wp_options table is what holds the transients here so the single
// DELETE above covers the current site. WP itself iterates uninstall.php per
// site when running a network uninstall.
