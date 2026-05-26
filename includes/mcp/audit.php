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

    // Synchronous push — if it fails we keep the row with pushed_at=NULL for
    // retry by the heartbeat. Audit is a side-effect of the request so a
    // failure here must NEVER tank the user-facing response.
    $pushed = ajax_snippets_mcp_audit_push_row($id, $entry, $codeHash, $now);
    if ($pushed) {
        $wpdb->update($table, ['pushed_at' => $now], ['id' => $id], ['%d'], ['%d']);
    }
    return $id;
}

function ajax_snippets_mcp_audit_push_row($id, array $entry, $codeHash, $ts)
{
    $code    = (string) ($entry['code'] ?? '');
    $preview = $code === '' ? null : substr(preg_replace('/\s+/', ' ', $code), 0, 200);

    return ajax_snippets_mcp_registry_push_audit([
        'ts'           => (int) $ts,
        'caller_fp'    => $entry['caller_fp'] ?? null,
        'caller_kind'  => $entry['caller_kind'] ?? 'unknown',
        'action'       => $entry['action'],
        'code_hash'    => $codeHash,
        'code_preview' => $preview,
        'status'       => $entry['status'],
        'error_type'   => $entry['error_type'] ?? null,
        'error_msg'    => $entry['error_msg'] ?? null,
        'duration_ms'  => $entry['duration_ms'] ?? null,
    ]);
}

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
            'code_preview' => $row['code'] !== null
                ? substr(preg_replace('/\s+/', ' ', (string) $row['code']), 0, 200)
                : null,
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
