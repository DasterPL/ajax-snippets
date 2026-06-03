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

add_action('admin_init', 'ajax_snippets_mcp_autoregister_tick', 20);

function ajax_snippets_mcp_autoregister_tick()
{
    if (!ajax_snippets_mcp_is_enabled()) {
        return;
    }
    if (ajax_snippets_mcp_autoregister_is_blocked_host()) {
        return;
    }
    // Before checking AUTOREG_DONE: detect domain change caused by cloning this
    // install to a new host. A cloned site inherits the keypair *and* AUTOREG_DONE,
    // so without this check the tick would skip re-registration and the old keypair
    // would never be rotated.
    if (ajax_snippets_mcp_has_keypair()) {
        $registeredUrl = (string) get_option(AJAX_SNIPPETS_MCP_OPT_REGISTERED_URL, '');
        if ($registeredUrl !== '' && $registeredUrl !== home_url('/')) {
            error_log('[ajax-snippets-mcp] Domain changed ' . $registeredUrl . ' → ' . home_url('/') . ' — regenerating keypair and re-registering.');
            ajax_snippets_mcp_wipe_keypair();
            delete_option(AJAX_SNIPPETS_MCP_AUTOREG_DONE_OPT);
            delete_option(AJAX_SNIPPETS_MCP_AUTOREG_BACKOFF_OPT);
        }
    }
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
        error_log('[ajax-snippets-mcp] auto-register tick failed: ' . $e->getMessage());
    }
}

function ajax_snippets_mcp_autoregister_run()
{
    // 1) Make sure we have an Ed25519 keypair.
    if (!ajax_snippets_mcp_has_keypair()) {
        ajax_snippets_mcp_generate_keypair();
    }

    // 2) Register with the registry (or heartbeat if already known).
    // We always include the pubkey: the registry ignores it when the fp
    // already exists in `sites`. Keeping the request shape uniform means
    // a recovery path (e.g. the registry was wiped, our local status drifted)
    // still re-bootstraps correctly without special handling.
    $resp = ajax_snippets_mcp_registry_self_register(true);
    $newStatus = is_array($resp) && isset($resp['status']) ? (string) $resp['status'] : '';

    // Persist the URL this keypair was registered under. generate_keypair() writes
    // this too, but sites with keypairs pre-dating this feature lack the option.
    update_option(AJAX_SNIPPETS_MCP_OPT_REGISTERED_URL, home_url('/'), false);

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
