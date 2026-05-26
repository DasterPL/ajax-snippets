<?php

defined('ABSPATH') || exit;

/**
 * WP-cron daily heartbeat: touches `last_seen` in the registry, refreshes admin
 * pubkey cache, garbage-collects local nonces and old audit entries, retries
 * any audit entries that failed to push synchronously.
 */
add_action(AJAX_SNIPPETS_MCP_CRON_HEARTBEAT, 'ajax_snippets_mcp_heartbeat_tick');

function ajax_snippets_mcp_heartbeat_tick()
{
    if (!ajax_snippets_mcp_is_enabled()) {
        return;
    }

    try {
        ajax_snippets_mcp_registry_self_register(false);
    } catch (Ajax_Snippets_Mcp_Registry_Error $e) {
        error_log('[ajax-snippets-mcp] heartbeat self_register failed: ' . $e->getMessage());
    }

    try {
        ajax_snippets_mcp_registry_refresh_admin_keys(true);
    } catch (Ajax_Snippets_Mcp_Registry_Error $e) {
        error_log('[ajax-snippets-mcp] heartbeat key refresh failed: ' . $e->getMessage());
    }

    ajax_snippets_mcp_audit_gc();
    ajax_snippets_mcp_nonce_gc();
    ajax_snippets_mcp_audit_retry_unpushed();
}

function ajax_snippets_mcp_is_enabled()
{
    return (bool) get_option(AJAX_SNIPPETS_MCP_OPT_ENABLED, false);
}

function ajax_snippets_mcp_nonce_gc()
{
    global $wpdb;
    $table = ajax_snippets_mcp_nonce_table();
    $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE expires_at < %d", time()));
}
