<?php

defined('ABSPATH') || exit;

/**
 * Local audit log (full code stored locally) + best-effort async push of a
 * scrubbed (hash + 200-char preview) entry to the central registry.
 */

/**
 * @param array{
 *   caller_fp:?string,
 *   caller_kind:string,
 *   action:string,
 *   code:string,
 *   status:'ok'|'error',
 *   error_type?:?string,
 *   error_msg?:?string,
 *   duration_ms?:?int
 * } $entry
 * @return int audit row id
 */
function ajax_snippets_mcp_audit_record(array $entry)
{
    global $wpdb;
    $table = ajax_snippets_mcp_audit_table();

    $code      = (string) ($entry['code'] ?? '');
    $codeHash  = $code === '' ? null : hash('sha256', $code);
    $now       = time();

    $wpdb->insert(
        $table,
        [
            'ts'          => $now,
            'caller_fp'   => $entry['caller_fp'] ?? null,
            'caller_kind' => $entry['caller_kind'] ?? 'unknown',
            'action'      => substr((string) $entry['action'], 0, 32),
            'code_hash'   => $codeHash,
            'code'        => $code === '' ? null : $code,
            'status'      => $entry['status'],
            'error_type'  => isset($entry['error_type']) ? substr((string) $entry['error_type'], 0, 64) : null,
            'error_msg'   => $entry['error_msg'] ?? null,
            'duration_ms' => isset($entry['duration_ms']) ? (int) $entry['duration_ms'] : null,
            'pushed_at'   => null,
        ],
        ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d']
    );
    $id = (int) $wpdb->insert_id;

    // Audit is a side-effect of the request, so the REST response must NEVER
    // wait on the registry. We persist the row locally (pushed_at=NULL) and
    // hand the actual network push off out-of-band: a single-event cron flush
    // runs right after this request, and the daily heartbeat retries anything
    // still unpushed. No blocking wp_remote_request on the hot path.
    if (!wp_next_scheduled('ajax_snippets_mcp_audit_flush')) {
        wp_schedule_single_event(time(), 'ajax_snippets_mcp_audit_flush');
    }
    return $id;
}

/**
 * Out-of-band flush of locally-stored, not-yet-pushed audit rows. Scheduled as
 * a single cron event by ajax_snippets_mcp_audit_record() so the user-facing
 * REST request never blocks on the registry.
 */
add_action('ajax_snippets_mcp_audit_flush', 'ajax_snippets_mcp_audit_retry_unpushed');

function ajax_snippets_mcp_audit_retry_unpushed($limit = 50)
{
    global $wpdb;
    $table = ajax_snippets_mcp_audit_table();
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE pushed_at IS NULL ORDER BY ts ASC LIMIT %d",
            $limit
        ),
        ARRAY_A
    );
    if (!is_array($rows) || $rows === []) {
        return 0;
    }
    $pushed = 0;
    foreach ($rows as $row) {
        $ok = ajax_snippets_mcp_registry_push_audit([
            'ts'           => (int) $row['ts'],
            'caller_fp'    => $row['caller_fp'] ?: null,
            'caller_kind'  => $row['caller_kind'] ?: 'unknown',
            'action'       => $row['action'],
            'code_hash'    => $row['code_hash'] ?: null,
            // Intentionally do NOT ship snippet source off-site: snippets often
            // begin with an API key / DB credential, and this pushes to the
            // remote registry. code_hash is enough to correlate; the full body
            // stays only in the local audit table. (Was: first 200 chars.)
            'code_preview' => null,
            'status'       => $row['status'],
            'error_type'   => $row['error_type'] ?: null,
            'error_msg'    => $row['error_msg'] ?: null,
            'duration_ms'  => $row['duration_ms'] !== null ? (int) $row['duration_ms'] : null,
        ]);
        if ($ok) {
            $wpdb->update($table, ['pushed_at' => time()], ['id' => $row['id']], ['%d'], ['%d']);
            $pushed++;
        }
    }
    return $pushed;
}

function ajax_snippets_mcp_audit_gc()
{
    global $wpdb;
    $table = ajax_snippets_mcp_audit_table();
    $cutoff = time() - AJAX_SNIPPETS_MCP_AUDIT_RETENTION;
    $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE ts < %d", $cutoff));
}
