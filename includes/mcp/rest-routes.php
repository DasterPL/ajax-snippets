<?php

defined('ABSPATH') || exit;

/**
 * REST surface exposed to the MCP bridge. Five endpoints, all signed Ed25519,
 * all running with administrator capability after switch_to_user().
 *
 *   POST /wp-json/ajax-snippets/v1/execute
 *   POST /wp-json/ajax-snippets/v1/batch/init
 *   POST /wp-json/ajax-snippets/v1/batch/next
 *   GET  /wp-json/ajax-snippets/v1/batch/status
 *   GET  /wp-json/ajax-snippets/v1/search
 */

const AJAX_SNIPPETS_MCP_REST_NS = 'ajax-snippets/v1';

add_action('rest_api_init', 'ajax_snippets_mcp_register_rest_routes');

/**
 * Suppress WordPress's built-in REST auth (Application Passwords, cookie auth)
 * for our endpoints. We rely entirely on the Ed25519 signature verified in
 * `ajax_snippets_mcp_rest_permission`, and the `Authorization` header is
 * needed for a different purpose here: getting through a server-level HTTP
 * Basic Auth prompt (typical for staging hosts like *.wpstage.net).
 *
 * Without this, WP sees the bridge's `Authorization: Basic <nginx-creds>`
 * header, tries to authenticate it as an Application Password, fails, and
 * returns 401 `invalid_username` before our permission_callback ever runs.
 *
 * Priority 999 to run after `wp_authenticate_application_password` has
 * populated the error so we can override it.
 */
add_filter('rest_authentication_errors', 'ajax_snippets_mcp_suppress_wp_rest_auth_for_our_routes', 999);

/**
 * Resolve the REST route this request targets, relative to the site's REST
 * prefix, from EITHER the pretty path (/<prefix>/<ns>/…) or the plain
 * `rest_route` query PARAMETER. Returns '' when this is not a REST request.
 *
 * It reads the actual `rest_route` parameter value — not a loose substring of
 * the raw URI — so a request to an unrelated route that merely carries
 * `?x=rest_route=/ajax-snippets/v1/…` in some OTHER parameter can never be
 * mistaken for one of ours.
 *
 * @return string e.g. '/ajax-snippets/v1/sync', or '' if not a REST request.
 */
function ajax_snippets_mcp_current_rest_route()
{
    if (!isset($_SERVER['REQUEST_URI'])) {
        return '';
    }
    // Parsed faithfully from the raw (only unslashed) URI; the extracted route
    // is validated by anchoring against our namespace in the caller, so no
    // further sanitisation is needed here.
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $uri   = (string) wp_unslash($_SERVER['REQUEST_URI']);
    $parts = wp_parse_url($uri);
    if (!is_array($parts)) {
        return '';
    }

    // Plain form: ?rest_route=/namespace/route
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $q);
        if (isset($q['rest_route']) && is_string($q['rest_route']) && $q['rest_route'] !== '') {
            return '/' . ltrim($q['rest_route'], '/');
        }
    }

    // Pretty form: /<rest_prefix>/<namespace>/route
    $prefix = function_exists('rest_get_url_prefix') ? rest_get_url_prefix() : 'wp-json';
    $path   = isset($parts['path']) ? (string) $parts['path'] : '';
    $needle = '/' . trim($prefix, '/') . '/';
    $pos    = strpos($path, $needle);
    if ($pos !== false) {
        return '/' . ltrim(substr($path, $pos + strlen($needle)), '/');
    }

    return '';
}

function ajax_snippets_mcp_suppress_wp_rest_auth_for_our_routes($result)
{
    $route = ajax_snippets_mcp_current_rest_route();
    $ns    = '/' . AJAX_SNIPPETS_MCP_REST_NS . '/';

    // Anchored: the RESOLVED route must BEGIN with our namespace. The old code
    // matched our namespace as a substring anywhere in the raw URI, so any
    // route could smuggle it through an unrelated query parameter and get this
    // filter to wipe another security plugin's REST authentication error.
    if (strpos($route, $ns) !== 0) {
        return $result;
    }

    // The public /sync endpoint authenticates nothing of its own; let it through
    // so server-level Basic Auth (staging) doesn't surface as a WP 401. Matched
    // by EXACT route, never a substring, so it can't blanket-suppress auth.
    if ($route === $ns . 'sync') {
        return null;
    }

    // Every other route is signed: only suppress a prior auth error when MCP is
    // enabled AND the request actually carries the MCP signature headers
    // (X-Auth-Fp / X-Auth-Signature). The route's own permission_callback still
    // verifies the Ed25519 signature, so this only removes another filter's
    // 401 on a request that is genuinely a signed MCP call.
    if (!function_exists('ajax_snippets_mcp_is_enabled') || !ajax_snippets_mcp_is_enabled()) {
        return $result;
    }
    if (!isset($_SERVER['HTTP_X_AUTH_FP'], $_SERVER['HTTP_X_AUTH_SIGNATURE'])) {
        return $result;
    }
    return null;
}

function ajax_snippets_mcp_register_rest_routes()
{
    register_rest_route(AJAX_SNIPPETS_MCP_REST_NS, '/execute', [
        'methods'             => 'POST',
        'callback'            => 'ajax_snippets_mcp_rest_execute',
        'permission_callback' => 'ajax_snippets_mcp_rest_permission',
    ]);
    register_rest_route(AJAX_SNIPPETS_MCP_REST_NS, '/batch/init', [
        'methods'             => 'POST',
        'callback'            => 'ajax_snippets_mcp_rest_batch_init',
        'permission_callback' => 'ajax_snippets_mcp_rest_permission',
    ]);
    register_rest_route(AJAX_SNIPPETS_MCP_REST_NS, '/batch/next', [
        'methods'             => 'POST',
        'callback'            => 'ajax_snippets_mcp_rest_batch_next',
        'permission_callback' => 'ajax_snippets_mcp_rest_permission',
    ]);
    register_rest_route(AJAX_SNIPPETS_MCP_REST_NS, '/batch/status', [
        'methods'             => 'GET',
        'callback'            => 'ajax_snippets_mcp_rest_batch_status',
        'permission_callback' => 'ajax_snippets_mcp_rest_permission',
    ]);
    register_rest_route(AJAX_SNIPPETS_MCP_REST_NS, '/search', [
        'methods'             => 'GET',
        'callback'            => 'ajax_snippets_mcp_rest_search',
        'permission_callback' => 'ajax_snippets_mcp_rest_permission',
    ]);

    // Public sync endpoint — no auth, triggers registration/heartbeat on demand.
    register_rest_route(AJAX_SNIPPETS_MCP_REST_NS, '/sync', [
        'methods'             => ['GET', 'POST'],
        'callback'            => 'ajax_snippets_mcp_rest_sync',
        'permission_callback' => '__return_true',
    ]);

    // Safe filesystem operations (read/write/edit/list) — see includes/mcp/fs.php.
    register_rest_route(AJAX_SNIPPETS_MCP_REST_NS, '/fs/list', [
        'methods'             => 'POST',
        'callback'            => 'ajax_snippets_mcp_rest_fs_list',
        'permission_callback' => 'ajax_snippets_mcp_rest_permission',
    ]);
    register_rest_route(AJAX_SNIPPETS_MCP_REST_NS, '/fs/read', [
        'methods'             => 'POST',
        'callback'            => 'ajax_snippets_mcp_rest_fs_read',
        'permission_callback' => 'ajax_snippets_mcp_rest_permission',
    ]);
    register_rest_route(AJAX_SNIPPETS_MCP_REST_NS, '/fs/write', [
        'methods'             => 'POST',
        'callback'            => 'ajax_snippets_mcp_rest_fs_write',
        'permission_callback' => 'ajax_snippets_mcp_rest_permission',
    ]);
    register_rest_route(AJAX_SNIPPETS_MCP_REST_NS, '/fs/edit', [
        'methods'             => 'POST',
        'callback'            => 'ajax_snippets_mcp_rest_fs_edit',
        'permission_callback' => 'ajax_snippets_mcp_rest_permission',
    ]);
    register_rest_route(AJAX_SNIPPETS_MCP_REST_NS, '/fs/grep', [
        'methods'             => 'POST',
        'callback'            => 'ajax_snippets_mcp_rest_fs_grep',
        'permission_callback' => 'ajax_snippets_mcp_rest_permission',
    ]);
}

/**
 * permission_callback: verify the request's Ed25519 signature against the
 * cached admin pubkey list. We piggyback admin-keys-version refresh on every
 * call so plugin sites stay in sync without polling.
 */
function ajax_snippets_mcp_rest_permission(WP_REST_Request $request)
{
    if (!ajax_snippets_mcp_is_enabled()) {
        return new WP_Error('mcp_disabled', 'MCP integration is disabled on this site.', ['status' => 403]);
    }

    try {
        $admin = ajax_snippets_mcp_verify_admin_request($request);
    } catch (\Throwable $e) {
        return new WP_Error('mcp_unauthorized', $e->getMessage(), ['status' => 401]);
    }

    // Async key refresh if the bridge says it has a newer version.
    $remoteVersion = (int) $request->get_header('x_mcp_keys_version');
    $localVersion  = (int) get_option(AJAX_SNIPPETS_MCP_OPT_KEYS_VERSION, 0);
    if ($remoteVersion > 0 && $remoteVersion > $localVersion) {
        if (!wp_next_scheduled('ajax_snippets_mcp_refresh_keys_now')) {
            wp_schedule_single_event(time(), 'ajax_snippets_mcp_refresh_keys_now');
        }
    }

    // Stash for the handler.
    $request->set_param('_mcp_admin', $admin);
    return true;
}

add_action('ajax_snippets_mcp_refresh_keys_now', static function () {
    try {
        ajax_snippets_mcp_registry_refresh_admin_keys(true);
    } catch (\Throwable $e) {
        ajax_snippets_mcp_debug_log('[ajax-snippets-mcp] async key refresh failed: ' . $e->getMessage());
    }
});

/**
 * Wrap a handler with: user switching, snippet helpers, audit logging, and
 * canonical success/error envelope shaping.
 *
 * @param callable(WP_REST_Request):array $fn
 */
function ajax_snippets_mcp_with_runner(WP_REST_Request $request, $action, $code, callable $fn)
{
    require_once AJAX_SNIPPETS_DIR . 'includes/ajax-handlers.php';
    require_once AJAX_SNIPPETS_DIR . 'includes/pretty-table.php';
    require_once AJAX_SNIPPETS_DIR . 'includes/csv-helper.php';

    $admin       = $request->get_param('_mcp_admin');
    $runAsUserId = (int) get_option(AJAX_SNIPPETS_MCP_OPT_RUN_AS_USER, 1);
    $previousId  = get_current_user_id();
    if ($runAsUserId > 0) {
        wp_set_current_user($runAsUserId);
    }

    $start = (int) (microtime(true) * 1000);
    try {
        $result = $fn();
        $duration = (int) (microtime(true) * 1000) - $start;
        ajax_snippets_mcp_audit_record([
            'caller_fp'   => $admin['fp'] ?? null,
            'caller_kind' => 'admin',
            'action'      => $action,
            'code'        => (string) $code,
            'status'      => 'ok',
            'duration_ms' => $duration,
        ]);
        return rest_ensure_response($result);
    } catch (\Throwable $th) {
        $duration  = (int) (microtime(true) * 1000) - $start;
        $classified = ajax_snippets_classify_throwable($th);
        ajax_snippets_mcp_audit_record([
            'caller_fp'   => $admin['fp'] ?? null,
            'caller_kind' => 'admin',
            'action'      => $action,
            'code'        => (string) $code,
            'status'      => 'error',
            'error_type'  => $classified['type'],
            'error_msg'   => $th->getMessage(),
            'duration_ms' => $duration,
        ]);
        return new WP_REST_Response(
            [
                'ok'    => false,
                'error' => [
                    'code'    => $classified['status'],
                    'type'    => $classified['type'],
                    'message' => $th->getMessage(),
                    'line'    => $th->getLine(),
                    'file'    => $th->getFile(),
                ],
            ],
            $classified['status']
        );
    } finally {
        if ($runAsUserId > 0) {
            wp_set_current_user($previousId);
        }
    }
}

function ajax_snippets_mcp_rest_execute(WP_REST_Request $request)
{
    $body = $request->get_json_params();
    $code = is_array($body) && isset($body['code']) ? (string) $body['code'] : '';
    if ($code === '') {
        return new WP_REST_Response(['ok' => false, 'error' => ['message' => 'Empty code']], 422);
    }
    return ajax_snippets_mcp_with_runner($request, 'execute', $code, function () use ($code) {
        $result = ajax_snippets_core_execute($code);
        return [
            'ok'     => true,
            'output' => $result['output'],
            'return' => $result['return'],
        ];
    });
}

function ajax_snippets_mcp_rest_batch_init(WP_REST_Request $request)
{
    $body = $request->get_json_params();
    $code = is_array($body) && isset($body['fetch_code']) ? (string) $body['fetch_code'] : '';
    if ($code === '') {
        return new WP_REST_Response(['ok' => false, 'error' => ['message' => 'Empty fetch_code']], 422);
    }
    return ajax_snippets_mcp_with_runner($request, 'batch_init', $code, function () use ($code) {
        $result = ajax_snippets_core_batch_init($code, get_current_user_id());
        return [
            'ok'     => true,
            'output' => $result['output'],
            'count'  => $result['count'],
        ];
    });
}

function ajax_snippets_mcp_rest_batch_next(WP_REST_Request $request)
{
    $body = $request->get_json_params();
    $code = is_array($body) && isset($body['process_code']) ? (string) $body['process_code'] : '';
    if ($code === '') {
        return new WP_REST_Response(['ok' => false, 'error' => ['message' => 'Empty process_code']], 422);
    }
    return ajax_snippets_mcp_with_runner($request, 'batch_next', $code, function () use ($request, $code) {
        $body      = $request->get_json_params();
        $index     = isset($body['index']) ? max(0, (int) $body['index']) : 0;
        $batchSize = isset($body['batch_size']) ? max(1, (int) $body['batch_size']) : 10;

        $result = ajax_snippets_core_batch_next($code, get_current_user_id(), $index, $batchSize);
        // Preserve the historical REST envelope: 'return' present only on a
        // progress (non-done-early) result.
        $response = [
            'ok'     => true,
            'output' => $result['output'],
            'done'   => $result['done'],
            'index'  => $result['index'],
            'total'  => $result['total'],
        ];
        if (array_key_exists('return', $result)) {
            $response['return'] = $result['return'];
        }
        return $response;
    });
}

function ajax_snippets_mcp_rest_batch_status(WP_REST_Request $request)
{
    return ajax_snippets_mcp_with_runner($request, 'batch_status', '', function () {
        return ['ok' => true] + ajax_snippets_core_batch_status(get_current_user_id());
    });
}

function ajax_snippets_mcp_rest_search(WP_REST_Request $request)
{
    $source = sanitize_key((string) $request->get_param('source'));
    $term   = sanitize_text_field((string) $request->get_param('q'));

    return ajax_snippets_mcp_with_runner($request, 'search', '', function () use ($source, $term) {
        $results = ajax_snippets_core_search($source, $term);
        return ['results' => $results];
    });
}

/**
 * Thin adapters over Ajax_Snippets_FS. Each parses JSON body, validates the
 * minimal shape (422 on bad input, mirroring /execute), and delegates to
 * with_runner() for user-switch + audit + success/error envelope.
 */
function ajax_snippets_mcp_rest_fs_list(WP_REST_Request $request)
{
    $body = $request->get_json_params();
    $path = is_array($body) && isset($body['path']) ? (string) $body['path'] : '';
    if ($path === '') {
        return new WP_REST_Response(['ok' => false, 'error' => ['message' => 'Empty path']], 422);
    }
    return ajax_snippets_mcp_with_runner($request, 'fs.list', 'fs.list ' . $path, function () use ($path) {
        return ['ok' => true] + Ajax_Snippets_FS::list_dir($path);
    });
}

function ajax_snippets_mcp_rest_fs_read(WP_REST_Request $request)
{
    $body = $request->get_json_params();
    $path = is_array($body) && isset($body['path']) ? (string) $body['path'] : '';
    if ($path === '') {
        return new WP_REST_Response(['ok' => false, 'error' => ['message' => 'Empty path']], 422);
    }
    return ajax_snippets_mcp_with_runner($request, 'fs.read', 'fs.read ' . $path, function () use ($path) {
        return ['ok' => true] + Ajax_Snippets_FS::read_file($path);
    });
}

function ajax_snippets_mcp_rest_fs_write(WP_REST_Request $request)
{
    $body = $request->get_json_params();
    $path = is_array($body) && isset($body['path']) ? (string) $body['path'] : '';
    if ($path === '') {
        return new WP_REST_Response(['ok' => false, 'error' => ['message' => 'Empty path']], 422);
    }
    if (!is_array($body) || !isset($body['content']) || !is_string($body['content'])) {
        return new WP_REST_Response(['ok' => false, 'error' => ['message' => 'content must be a string']], 422);
    }
    $content = (string) $body['content'];
    $create  = isset($body['create']) ? (bool) $body['create'] : true;
    return ajax_snippets_mcp_with_runner($request, 'fs.write', 'fs.write ' . $path, function () use ($path, $content, $create) {
        return ['ok' => true] + Ajax_Snippets_FS::write_file($path, $content, $create);
    });
}

function ajax_snippets_mcp_rest_fs_edit(WP_REST_Request $request)
{
    $body = $request->get_json_params();
    $path = is_array($body) && isset($body['path']) ? (string) $body['path'] : '';
    if ($path === '') {
        return new WP_REST_Response(['ok' => false, 'error' => ['message' => 'Empty path']], 422);
    }
    $find = is_array($body) && isset($body['find']) ? (string) $body['find'] : '';
    if ($find === '') {
        return new WP_REST_Response(['ok' => false, 'error' => ['message' => 'Empty find']], 422);
    }
    if (!isset($body['replace']) || !is_string($body['replace'])) {
        return new WP_REST_Response(['ok' => false, 'error' => ['message' => 'replace must be a string']], 422);
    }
    $replace = (string) $body['replace'];
    $all     = isset($body['all']) ? (bool) $body['all'] : false;
    return ajax_snippets_mcp_with_runner($request, 'fs.edit', 'fs.edit ' . $path, function () use ($path, $find, $replace, $all) {
        return ['ok' => true] + Ajax_Snippets_FS::edit_file($path, $find, $replace, $all);
    });
}

function ajax_snippets_mcp_rest_fs_grep(WP_REST_Request $request)
{
    $body = $request->get_json_params();
    // Empty path is allowed: grep() treats it as "whole site root" (ABSPATH).
    $path  = is_array($body) && isset($body['path']) ? (string) $body['path'] : '';
    $query = is_array($body) && isset($body['query']) ? (string) $body['query'] : '';
    if ($query === '') {
        return new WP_REST_Response(['ok' => false, 'error' => ['message' => 'Empty query']], 422);
    }
    $opts = [
        'regex'       => !empty($body['regex']),
        'ignore_case' => !empty($body['ignore_case']),
        'glob'        => isset($body['glob']) ? (string) $body['glob'] : '',
        'max_results' => isset($body['max_results']) ? (int) $body['max_results'] : 0,
    ];
    $auditPath = $path === '' ? '(site root)' : $path;
    return ajax_snippets_mcp_with_runner($request, 'fs.grep', 'fs.grep ' . $auditPath, function () use ($path, $query, $opts) {
        return ['ok' => true] + Ajax_Snippets_FS::grep($path, $query, $opts);
    });
}

/**
 * GET|POST /wp-json/ajax-snippets/v1/sync — public, no auth.
 *
 * Manually triggers the registration/heartbeat flow. Intended for staging and
 * dev sites where zero-click auto-registration is blocked (*.wpstage.net,
 * *.dev.apturn.pl). Safe to call from a browser or curl — never returns keys
 * or credentials, only: ok, status (enabled/pending/unknown), fp fingerprint.
 *
 * Throttled to one outbound registry call per 30 s to prevent hammering.
 */
function ajax_snippets_mcp_rest_sync()
{
    if (!ajax_snippets_mcp_is_enabled()) {
        return new WP_REST_Response(['ok' => false, 'error' => 'mcp_disabled'], 403);
    }

    if (get_transient('ajax_snippets_mcp_sync_lock')) {
        return new WP_REST_Response([
            'ok'     => true,
            'status' => (string) get_option(AJAX_SNIPPETS_MCP_OPT_STATUS, 'unknown'),
            'fp'     => (string) get_option(AJAX_SNIPPETS_MCP_OPT_FP, '') ?: null,
            'note'   => 'throttled',
        ], 200);
    }
    set_transient('ajax_snippets_mcp_sync_lock', 1, 30);

    $note = null;
    try {
        ajax_snippets_mcp_autoregister_run();
        update_option(AJAX_SNIPPETS_MCP_AUTOREG_DONE_OPT, time(), true);
        delete_option(AJAX_SNIPPETS_MCP_AUTOREG_BACKOFF_OPT);
    } catch (\Throwable $e) {
        $note = $e->getMessage();
    }

    $fp     = (string) get_option(AJAX_SNIPPETS_MCP_OPT_FP, '');
    $status = (string) get_option(AJAX_SNIPPETS_MCP_OPT_STATUS, 'unknown');
    $resp   = ['ok' => true, 'status' => $status, 'fp' => $fp ?: null];
    if ($note !== null) {
        $resp['note'] = $note;
    }
    return new WP_REST_Response($resp, 200);
}
