<?php

defined('ABSPATH') || exit;

/**
 * Zero-click registration flow. Runs on admin_init in the background:
 *
 *   1. If MCP is enabled and no keypair exists → generate Ed25519 keypair
 *   2. If we have a keypair but never registered (or registry returned non-enabled
 *      last time) → POST /v1/sites with pubkey
 *   3. If we registered but never pulled admin keys → fetch them
 *
 * Each step is best-effort — failures get logged and re-tried on the next
 * admin page load. A small backoff stops us from hammering an offline registry.
 */

const AJAX_SNIPPETS_MCP_AUTOREG_DONE_OPT      = 'ajax_snippets_mcp_autoregister_done';
const AJAX_SNIPPETS_MCP_AUTOREG_BACKOFF_OPT   = 'ajax_snippets_mcp_autoregister_backoff_until';
const AJAX_SNIPPETS_MCP_AUTOREG_BACKOFF_SEC   = 600; // 10 minutes between failed attempts

/**
 * Hosts blocked from zero-click auto-registration. Staging copies and shared
 * dev environments would otherwise self-enroll under the production team's
 * registry and pollute the site list. The manual snippet template ("MCP:
 * register this site") still works on these hosts when explicitly invoked.
 *
 * Patterns are matched against the home_url host (lowercased) with `fnmatch`.
 */
function ajax_snippets_mcp_autoregister_blocked_patterns()
{
    return apply_filters('ajax_snippets_mcp_autoregister_blocked_hosts', [
        '*.wpstage.net',
        '*.dev.apturn.pl',
    ]);
}

function ajax_snippets_mcp_autoregister_is_blocked_host()
{
    $host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    if ($host === '') {
        return false;
    }
    foreach (ajax_snippets_mcp_autoregister_blocked_patterns() as $pattern) {
        if (fnmatch((string) $pattern, $host)) {
            return true;
        }
    }
    return false;
}

/**
 * If the keypair was generated on a different host, wipe it so a fresh identity
 * is generated on the next registration. A clone/staging copy inherits the
 * source site's keypair + origin-host hash (sha256(host), untouched by URL
 * search-replace), so without this it would register under the source site's fp
 * and clobber its registry row. Shared by the admin_init tick and the /sync
 * path so both enroll a clone under its own fp. Returns true if it reset.
 */
function ajax_snippets_mcp_reset_keypair_if_host_changed()
{
    if (!ajax_snippets_mcp_has_keypair()
        || ajax_snippets_mcp_keypair_matches_current_host() !== false) {
        return false;
    }
    $host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    ajax_snippets_mcp_debug_log('[ajax-snippets-mcp] Keypair origin-host mismatch (' . $host . ') — regenerating keypair and re-registering.');
    ajax_snippets_mcp_wipe_keypair();
    delete_option(AJAX_SNIPPETS_MCP_AUTOREG_DONE_OPT);
    delete_option(AJAX_SNIPPETS_MCP_AUTOREG_BACKOFF_OPT);
    return true;
}

add_action('admin_init', 'ajax_snippets_mcp_autoregister_tick', 20);

function ajax_snippets_mcp_autoregister_tick()
{
    if (!ajax_snippets_mcp_is_enabled()) {
        return;
    }
    if (ajax_snippets_mcp_autoregister_is_blocked_host()) {
        return;
    }
    // Backfill origin-host hash for keypairs generated before this feature was
    // introduced (one-time, runs only while the option is absent). Skipped on
    // blocked hosts — a blocked host with no hash is treated as a clone below.
    if (ajax_snippets_mcp_has_keypair()
        && (string) get_option(AJAX_SNIPPETS_MCP_OPT_ORIGIN_HOST_HASH, '') === '') {
        $host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        update_option(AJAX_SNIPPETS_MCP_OPT_ORIGIN_HOST_HASH, hash('sha256', $host), false);
    }
    // Before checking AUTOREG_DONE: detect a clone/domain change (a cloned site
    // inherits the keypair *and* AUTOREG_DONE) and regenerate, so the tick doesn't
    // skip re-registration under a stale keypair.
    ajax_snippets_mcp_reset_keypair_if_host_changed();
    if (get_option(AJAX_SNIPPETS_MCP_AUTOREG_DONE_OPT)) {
        return;
    }
    $backoffUntil = (int) get_option(AJAX_SNIPPETS_MCP_AUTOREG_BACKOFF_OPT, 0);
    if ($backoffUntil > time()) {
        return;
    }
    // Skip during AJAX, REST, cron — admin_init fires on those too and we
    // don't want a snippet executed via MCP to incidentally trigger our own
    // outbound calls on the same request.
    if (wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    try {
        ajax_snippets_mcp_autoregister_run();
        update_option(AJAX_SNIPPETS_MCP_AUTOREG_DONE_OPT, time(), true);
        delete_option(AJAX_SNIPPETS_MCP_AUTOREG_BACKOFF_OPT);
    } catch (\Throwable $e) {
        update_option(
            AJAX_SNIPPETS_MCP_AUTOREG_BACKOFF_OPT,
            time() + AJAX_SNIPPETS_MCP_AUTOREG_BACKOFF_SEC,
            false
        );
        ajax_snippets_mcp_debug_log('[ajax-snippets-mcp] auto-register tick failed: ' . $e->getMessage());
    }
}

function ajax_snippets_mcp_autoregister_run()
{
    // 0) Clone/host-change guard. The admin_init tick already does this, but the
    //    public /sync endpoint calls run() directly (that's how a blocked staging
    //    host is enrolled), so it must regenerate a clone's inherited keypair here
    //    too — otherwise the staging registers under the source site's fp and
    //    overwrites its registry row.
    ajax_snippets_mcp_reset_keypair_if_host_changed();

    // 1) Make sure we have an Ed25519 keypair.
    if (!ajax_snippets_mcp_has_keypair()) {
        // Double-check directly from DB, bypassing the WP object-cache layer.
        // On environments with read replicas or a persistent cache that lags
        // behind recent primary writes, get_option() may return stale empty
        // data while another server already wrote the keypair. Reading $wpdb
        // directly uses the same connection but skips any in-process cache,
        // guarding against the most common case (per-process stale cache).
        global $wpdb;
        wp_cache_delete(AJAX_SNIPPETS_MCP_OPT_SECRET_KEY, 'options');
        $inDb = (string) $wpdb->get_var($wpdb->prepare(
            "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
            AJAX_SNIPPETS_MCP_OPT_SECRET_KEY
        ));
        if ($inDb === '') {
            ajax_snippets_mcp_generate_keypair();
        }
        // else: key exists in DB but wasn't in cache — proceed with existing key
    }

    // 2) Register with the registry (or heartbeat if already known).
    // We always include the pubkey: the registry ignores it when the fp
    // already exists in `sites`. Keeping the request shape uniform means
    // a recovery path (e.g. the registry was wiped, our local status drifted)
    // still re-bootstraps correctly without special handling.
    $resp = ajax_snippets_mcp_registry_self_register(true);
    $newStatus = is_array($resp) && isset($resp['status']) ? (string) $resp['status'] : '';

    // Persist registration metadata. generate_keypair() writes these too, but
    // sites with keypairs pre-dating this feature lack them.
    update_option(AJAX_SNIPPETS_MCP_OPT_REGISTERED_URL, home_url('/'), false);
    $host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    update_option(AJAX_SNIPPETS_MCP_OPT_ORIGIN_HOST_HASH, hash('sha256', $host), false);

    // 3) If the registry approved us (auto-approve or manual), pull the admin
    //    pubkey list so REST requests from the bridge can be verified.
    if ($newStatus === 'enabled') {
        ajax_snippets_mcp_registry_refresh_admin_keys(true);
    } elseif ($newStatus === 'pending') {
        // Approved manually later — don't mark done so we retry once more
        // after the cooldown, hoping the admin has approved by then.
        throw new \RuntimeException('Site registered but still pending approval.');
    }
}
