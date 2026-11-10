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
 *   6. Updater: clears the plugin-update-checker (PUC) bookkeeping — its
 *      update-state site option and its recurring cron check — which otherwise
 *      linger after the plugin is gone.
 *   7. FS: removes uploads/ajax-snippets-fs-backups (pre-write backups made by
 *      the MCP file tools) together with its .htaccess/index.php guards.
 *
 * Deactivation alone (Plugins → Deactivate) does NOT trigger this script —
 * deactivate is reversible by design, only uninstall does a full wipe.
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

require_once __DIR__ . '/includes/mcp/setup.php';
require_once __DIR__ . '/includes/mcp/keys.php';
require_once __DIR__ . '/includes/mcp/registry-client.php';
require_once __DIR__ . '/includes/mcp/fs.php';

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

// 6. Plugin Update Checker (PUC) leftovers. includes/updater.php builds a
//    checker with slug "ajax-snippets"; uninstall.php does NOT load updater.php
//    (that would rebuild the checker), so we clear PUC's own state by its known
//    names. Verified against vendor/yahnis-elsts/plugin-update-checker (v5p6):
//      - StateStore keeps the update cache in the "external_updates-<slug>"
//        SITE option (delete_site_option), not a plain option;
//      - Scheduler registers a recurring "puc_cron_check_updates-<slug>" event;
//      - Ui stores a short-lived "puc_manual_check_errors-<slug>" site transient.
//    filterSuffix is empty for our single checker, so these names are exact.
$puc_slug = 'ajax-snippets';
delete_site_option('external_updates-' . $puc_slug);
wp_clear_scheduled_hook('puc_cron_check_updates-' . $puc_slug);
delete_site_transient('puc_manual_check_errors-' . $puc_slug);

// 7. Backups the MCP file tools made before each write. Flat dir under uploads,
//    guarded by .htaccess + index.php; the heartbeat prunes it by mtime while the
//    plugin lives, uninstall drops it whole.
Ajax_Snippets_FS::purge_backups();

// In a multisite install the same plugin can be deactivated network-wide; the
// per-site wp_options table is what holds the transients here so the single
// DELETE above covers the current site. WP itself iterates uninstall.php per
// site when running a network uninstall.
