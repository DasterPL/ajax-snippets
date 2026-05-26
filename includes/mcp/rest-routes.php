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
        error_log('[ajax-snippets-mcp] async key refresh failed: ' . $e->getMessage());
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
        $result = ajax_snippets_guarded_eval($code);
        return [
            'ok'     => true,
            'output' => (string) $result['output'],
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
        $result = ajax_snippets_guarded_eval($code);
        $data = $result['return'];
        if (!is_array($data)) {
            throw new \UnexpectedValueException('fetch_code must return an array.');
        }
        $uid = get_current_user_id();
        set_transient('ajax-snippet-batch-data_' . $uid, $data, DAY_IN_SECONDS);
        set_transient('ajax-snippet-batch-index_' . $uid, 0, DAY_IN_SECONDS);
        delete_transient('ajax-snippet-batch-prev_' . $uid);
        return [
            'ok'     => true,
            'output' => (string) $result['output'],
            'count'  => count($data),
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
        $body = $request->get_json_params();
        $uid  = get_current_user_id();
        $data = get_transient('ajax-snippet-batch-data_' . $uid);
        if (!is_array($data)) {
            throw new \RuntimeException('No batch data. Run /batch/init first.');
        }
        $index     = isset($body['index']) ? max(0, (int) $body['index']) : 0;
        $batchSize = isset($body['batch_size']) ? max(1, (int) $body['batch_size']) : 10;
        $total     = count($data);
        if ($index >= $total) {
            delete_transient('ajax-snippet-batch-data_' . $uid);
            delete_transient('ajax-snippet-batch-index_' . $uid);
            delete_transient('ajax-snippet-batch-prev_' . $uid);
            return [
                'ok'     => true,
                'output' => '',
                'done'   => true,
                'index'  => $index,
                'total'  => $total,
            ];
        }

        $prev      = get_transient('ajax-snippet-batch-prev_' . $uid);
        $start     = $index;
        $end       = min($index + $batchSize, $total);
        $messages  = '';
        $return    = null;
        for ($i = $start; $i < $end; $i++) {
            $item   = $data[$i];
            $idx    = $i;
            $result = ajax_snippets_guarded_eval($code, compact('item', 'idx', 'total', 'data', 'prev') + ['index' => $idx]);
            $messages .= $result['output'];
            $return    = $result['return'];
            $prev      = $return;
        }
        $next = $end;
        set_transient('ajax-snippet-batch-index_' . $uid, $next, DAY_IN_SECONDS);
        set_transient('ajax-snippet-batch-prev_' . $uid, $prev, DAY_IN_SECONDS);
        return [
            'ok'     => true,
            'output' => $messages,
            'return' => $return,
            'done'   => $next >= $total,
            'index'  => $next,
            'total'  => $total,
        ];
    });
}

function ajax_snippets_mcp_rest_batch_status(WP_REST_Request $request)
{
    return ajax_snippets_mcp_with_runner($request, 'batch_status', '', function () {
        $uid  = get_current_user_id();
        $data = get_transient('ajax-snippet-batch-data_' . $uid);
        if (!is_array($data)) {
            return ['ok' => true, 'exists' => false];
        }
        $index = get_transient('ajax-snippet-batch-index_' . $uid);
        return [
            'ok'     => true,
            'exists' => true,
            'index'  => (int) ($index === false ? 0 : $index),
            'total'  => count($data),
        ];
    });
}

function ajax_snippets_mcp_rest_search(WP_REST_Request $request)
{
    $source = sanitize_key((string) $request->get_param('source'));
    $term   = sanitize_text_field((string) $request->get_param('q'));

    return ajax_snippets_mcp_with_runner($request, 'search', '', function () use ($source, $term) {
        $results = ajax_snippets_mcp_run_search($source, $term);
        return ['results' => $results];
    });
}

/**
 * Replica of the search logic in includes/ajax-handlers.php — kept here so we
 * can call it without going through admin-ajax. If you change one, mirror the
 * other (or factor both to a shared helper in a future refactor).
 *
 * @return list<array{id:string,text:string}>
 */
function ajax_snippets_mcp_run_search($source, $term)
{
    $results = [];
    if ($source === 'user') {
        $users = get_users([
            'search' => '*' . $term . '*',
            'number' => 20,
            'fields' => ['ID', 'display_name', 'user_login'],
        ]);
        foreach ($users as $u) {
            $results[] = [
                'id'   => (string) $u->ID,
                'text' => $u->display_name . ' (' . $u->user_login . ', #' . $u->ID . ')',
            ];
        }
    } elseif ($source === 'post') {
        $query = new WP_Query([
            's'              => $term,
            'posts_per_page' => 20,
            'post_type'      => 'any',
            'post_status'    => 'any',
        ]);
        foreach ($query->posts as $p) {
            $results[] = ['id' => (string) $p->ID, 'text' => $p->post_title . ' (#' . $p->ID . ')'];
        }
    } elseif ($source === 'order' && class_exists('WC_Order_Query')) {
        $q = new WC_Order_Query(['limit' => 20, 'return' => 'ids', 'search' => $term]);
        foreach ($q->get_orders() as $oid) {
            $o = wc_get_order($oid);
            if (!$o) continue;
            $name = '';
            if (method_exists($o, 'get_formatted_billing_full_name')) {
                $name = $o->get_formatted_billing_full_name();
            }
            $results[] = ['id' => (string) $oid, 'text' => '#' . $oid . ($name !== '' ? ' - ' . $name : '')];
        }
    } elseif ($source === 'product' && function_exists('wc_get_products')) {
        $ids = wc_get_products([
            'limit'  => 20,
            'return' => 'ids',
            'search' => $term,
            'status' => 'any',
            'type'   => ['simple', 'variable', 'variation'],
        ]);
        foreach ($ids as $pid) {
            $p = wc_get_product($pid);
            if (!$p) continue;
            $title = $p->get_name();
            if ($p->is_type('variation')) $title = 'Variation: ' . $title;
            $results[] = ['id' => (string) $pid, 'text' => $title . ' (#' . $pid . ')'];
        }
    } elseif ($source === 'subscription') {
        $query = new WP_Query([
            's'              => $term,
            'posts_per_page' => 20,
            'post_type'      => 'shop_subscription',
            'post_status'    => 'any',
        ]);
        foreach ($query->posts as $p) {
            $results[] = ['id' => (string) $p->ID, 'text' => $p->post_title . ' (#' . $p->ID . ')'];
        }
    }
    return $results;
}
