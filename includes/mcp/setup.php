<?php

defined('ABSPATH') || exit;

/**
 * Schema + options used by the MCP integration. All MCP state is kept in
 * dedicated rows/tables so deactivating the plugin (or just the MCP feature)
 * never touches the snippet history a user might want to keep.
 */

const AJAX_SNIPPETS_MCP_DB_VERSION = '2';

const AJAX_SNIPPETS_MCP_OPT_ENABLED       = 'ajax_snippets_mcp_enabled';
const AJAX_SNIPPETS_MCP_OPT_SECRET_KEY    = 'ajax_snippets_mcp_secret_key';
const AJAX_SNIPPETS_MCP_OPT_PUBKEY        = 'ajax_snippets_mcp_pubkey';
const AJAX_SNIPPETS_MCP_OPT_FP            = 'ajax_snippets_mcp_fp';
const AJAX_SNIPPETS_MCP_OPT_STATUS        = 'ajax_snippets_mcp_status';
const AJAX_SNIPPETS_MCP_OPT_ADMIN_KEYS    = 'ajax_snippets_mcp_admin_keys';
const AJAX_SNIPPETS_MCP_OPT_KEYS_VERSION  = 'ajax_snippets_mcp_admin_keys_version';
const AJAX_SNIPPETS_MCP_OPT_KEYS_UPDATED  = 'ajax_snippets_mcp_admin_keys_updated';
const AJAX_SNIPPETS_MCP_OPT_RUN_AS_USER        = 'ajax_snippets_mcp_run_as_user';
const AJAX_SNIPPETS_MCP_OPT_DB_VERSION         = 'ajax_snippets_mcp_db_version';
const AJAX_SNIPPETS_MCP_OPT_REGISTERED_URL     = 'ajax_snippets_mcp_registered_home_url';
// sha256(host) — hex, intentionally not a URL so WP search-replace during
// staging setup never alters it. Used for domain-change / clone detection.
const AJAX_SNIPPETS_MCP_OPT_ORIGIN_HOST_HASH   = 'ajax_snippets_mcp_origin_host_hash';

const AJAX_SNIPPETS_MCP_CRON_HEARTBEAT    = 'ajax_snippets_mcp_heartbeat';

const AJAX_SNIPPETS_MCP_NONCE_TTL         = 300;
const AJAX_SNIPPETS_MCP_MAX_SKEW          = 60;
const AJAX_SNIPPETS_MCP_AUDIT_RETENTION   = 30 * DAY_IN_SECONDS;

/**
 * Built-in registry URL. Overridable in three ways:
 *   1. `define('AJAX_SNIPPETS_MCP_REGISTRY_URL', '...')` in wp-config.php — takes precedence
 *   2. Stored option (set via UI) — runner-up
 *   3. This default — fallback so post-update sites auto-register without configuration
 */
const AJAX_SNIPPETS_MCP_DEFAULT_REGISTRY  = 'https://mcp.apturn.pl';

function ajax_snippets_mcp_audit_table()
{
    global $wpdb;
    return $wpdb->prefix . 'ajax_snippets_audit';
}

function ajax_snippets_mcp_nonce_table()
{
    global $wpdb;
    return $wpdb->prefix . 'ajax_snippets_mcp_nonces';
}

function ajax_snippets_mcp_install_schema()
{
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();
    $audit  = ajax_snippets_mcp_audit_table();
    $nonces = ajax_snippets_mcp_nonce_table();

    $sql = "CREATE TABLE {$audit} (
        id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        ts            INT UNSIGNED NOT NULL,
        caller_fp     CHAR(64) NULL,
        caller_kind   VARCHAR(16) NOT NULL DEFAULT 'unknown',
        action        VARCHAR(32) NOT NULL,
        code_hash     CHAR(64) NULL,
        code          LONGTEXT NULL,
        status        VARCHAR(8) NOT NULL,
        error_type    VARCHAR(64) NULL,
        error_msg     TEXT NULL,
        duration_ms   INT UNSIGNED NULL,
        pushed_at     INT UNSIGNED NULL,
        PRIMARY KEY  (id),
        KEY idx_ts (ts),
        KEY idx_pushed (pushed_at)
    ) {$charset_collate};";

    dbDelta($sql);

    $sql_nonces = "CREATE TABLE {$nonces} (
        nonce       BINARY(16) NOT NULL,
        expires_at  INT UNSIGNED NOT NULL,
        PRIMARY KEY  (nonce),
        KEY idx_expires (expires_at)
    ) {$charset_collate};";

    dbDelta($sql_nonces);

    update_option(AJAX_SNIPPETS_MCP_OPT_DB_VERSION, AJAX_SNIPPETS_MCP_DB_VERSION);
}

/**
 * Idempotent "first time MCP code runs on this site" setup. Triggered both by
 * the activation hook (manual activate / deactivate+reactivate) and by the
 * `plugins_loaded` schema-upgrade probe (auto-updates that don't fire the
 * activation hook). `add_option` is used everywhere so a user who toggled
 * MCP off in the past keeps that preference across updates.
 */
function ajax_snippets_mcp_apply_zero_click_defaults()
{
    add_option(AJAX_SNIPPETS_MCP_OPT_ENABLED, true, '', true);
    add_option(AJAX_SNIPPETS_MCP_OPT_RUN_AS_USER, 1, '', true);

    // Hourly heartbeat refreshes the admin pubkey cache, so a revoked key stops
    // working within ~1h (was daily). Migrate installs still on the old cadence.
    $scheduled = wp_next_scheduled(AJAX_SNIPPETS_MCP_CRON_HEARTBEAT);
    if (!$scheduled) {
        wp_schedule_event(time() + 60, 'hourly', AJAX_SNIPPETS_MCP_CRON_HEARTBEAT);
    } elseif (wp_get_schedule(AJAX_SNIPPETS_MCP_CRON_HEARTBEAT) !== 'hourly') {
        wp_clear_scheduled_hook(AJAX_SNIPPETS_MCP_CRON_HEARTBEAT);
        wp_schedule_event(time() + 60, 'hourly', AJAX_SNIPPETS_MCP_CRON_HEARTBEAT);
    }
}

function ajax_snippets_mcp_maybe_upgrade_schema()
{
    if (get_option(AJAX_SNIPPETS_MCP_OPT_DB_VERSION) !== AJAX_SNIPPETS_MCP_DB_VERSION) {
        ajax_snippets_mcp_install_schema();
        ajax_snippets_mcp_apply_zero_click_defaults();
        // Force the auto-register flow next admin_init.
        delete_option('ajax_snippets_mcp_autoregister_done');
    }
}

function ajax_snippets_mcp_on_activate()
{
    ajax_snippets_mcp_install_schema();
    ajax_snippets_mcp_apply_zero_click_defaults();
    delete_option('ajax_snippets_mcp_autoregister_done');
}

function ajax_snippets_mcp_on_deactivate()
{
    wp_clear_scheduled_hook(AJAX_SNIPPETS_MCP_CRON_HEARTBEAT);
    // We intentionally keep the audit table + options so reactivation
    // doesn't lose history. Uninstall is the place to drop them.
}

// AJAX_SNIPPETS_PLUGIN is defined only in the main plugin file, which WordPress
// does NOT load during uninstall (uninstall.php requires this file directly).
// Referencing an undefined constant is a fatal Error on PHP 8+, which would
// abort the delete. Guard so this file is safe to include in any context.
if (defined('AJAX_SNIPPETS_PLUGIN')) {
    register_activation_hook(AJAX_SNIPPETS_PLUGIN, 'ajax_snippets_mcp_on_activate');
    register_deactivation_hook(AJAX_SNIPPETS_PLUGIN, 'ajax_snippets_mcp_on_deactivate');
}

add_action('plugins_loaded', 'ajax_snippets_mcp_maybe_upgrade_schema');
