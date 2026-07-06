<?php

defined('ABSPATH') || exit;

/**
 * WP-cron hourly heartbeat: touches `last_seen` in the registry, refreshes admin
 * pubkey cache (so revoked admin keys stop being honoured within ~1h), garbage-
 * collects local nonces and old audit entries, retries any audit entries that
 * failed to push synchronously.
 */
add_action(AJAX_SNIPPETS_MCP_CRON_HEARTBEAT, 'ajax_snippets_mcp_heartbeat_tick');

function ajax_snippets_mcp_heartbeat_tick()
{
    if (!ajax_snippets_mcp_is_enabled()) {
        return;
    }

    // Guard: don't touch the registry if this install's keypair belongs to a
    // different host — avoids a staging clone silently updating the source site's
    // last_seen or URL in the registry.
    if (ajax_snippets_mcp_has_keypair()) {
        $matches = ajax_snippets_mcp_keypair_matches_current_host();
        if ($matches === false) {
            // Domain mismatch — auto-register tick will handle rotation; skip heartbeat.
            return;
        }
        // No hash stored + blocked host: never registered here (clone of pre-fix install).
        if ($matches === null && ajax_snippets_mcp_autoregister_is_blocked_host()) {
            return;
        }
        // No hash stored + non-blocked host: pre-fix migration — backfill hash and proceed.
        if ($matches === null) {
            $host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
            update_option(AJAX_SNIPPETS_MCP_OPT_ORIGIN_HOST_HASH, hash('sha256', $host), false);
        }
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
