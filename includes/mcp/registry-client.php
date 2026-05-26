<?php

defined('ABSPATH') || exit;

/**
 * Signed HTTP client to the central registry (mcp.apturn.pl).
 * Used for: self-registration (POST /v1/sites), audit push (POST /v1/audit),
 * admin pubkey refresh (GET /v1/admin-keys).
 */

class Ajax_Snippets_Mcp_Registry_Error extends \RuntimeException
{
    public $http_status;
    public $error_code;

    public function __construct($status, $code, $message)
    {
        parent::__construct($message);
        $this->http_status = (int) $status;
        $this->error_code  = (string) $code;
    }
}

function ajax_snippets_mcp_registry_url()
{
    if (defined('AJAX_SNIPPETS_MCP_REGISTRY_URL')) {
        return rtrim((string) constant('AJAX_SNIPPETS_MCP_REGISTRY_URL'), '/');
    }
    return rtrim(AJAX_SNIPPETS_MCP_DEFAULT_REGISTRY, '/');
}

/**
 * @param 'GET'|'POST'|'DELETE' $method
 * @param string $path  e.g. "/v1/sites" or "/v1/audit?limit=10"
 * @param array<string,mixed>|null $body
 * @return array<string,mixed>|null
 * @throws Ajax_Snippets_Mcp_Registry_Error
 */
function ajax_snippets_mcp_registry_call($method, $path, $body = null, $extraHeaders = [])
{
    $base = ajax_snippets_mcp_registry_url();
    if ($base === '') {
        throw new Ajax_Snippets_Mcp_Registry_Error(0, 'no_registry', 'Registry URL not configured.');
    }
    $fp = (string) get_option(AJAX_SNIPPETS_MCP_OPT_FP, '');
    if ($fp === '') {
        throw new Ajax_Snippets_Mcp_Registry_Error(0, 'no_keypair', 'No keypair configured.');
    }

    $bodyStr = $body === null ? '' : wp_json_encode($body);
    if ($bodyStr === false) {
        throw new Ajax_Snippets_Mcp_Registry_Error(0, 'json_encode', 'Failed to encode request body.');
    }

    $sig = ajax_snippets_mcp_sign_request($method, $path, $bodyStr);

    $headers = array_merge(
        [
            'Content-Type'      => 'application/json',
            'X-Auth-Fp'         => $fp,
            'X-Auth-Timestamp'  => (string) $sig['timestamp'],
            'X-Auth-Nonce'      => $sig['nonce_b64'],
            'X-Auth-Signature'  => $sig['signature_b64'],
        ],
        $extraHeaders
    );

    $args = [
        'method'      => $method,
        'headers'     => $headers,
        'body'        => $bodyStr === '' ? null : $bodyStr,
        'timeout'     => 15,
        'redirection' => 0,
    ];

    $res = wp_remote_request($base . $path, $args);
    if (is_wp_error($res)) {
        throw new Ajax_Snippets_Mcp_Registry_Error(0, 'network', $res->get_error_message());
    }

    $status = (int) wp_remote_retrieve_response_code($res);
    $body   = (string) wp_remote_retrieve_body($res);
    $data   = $body === '' ? null : json_decode($body, true);

    if ($status >= 200 && $status < 300) {
        return is_array($data) ? $data : null;
    }

    $code = 'http_' . $status;
    $msg  = 'Registry returned HTTP ' . $status;
    if (is_array($data) && isset($data['error'])) {
        $code = (string) ($data['error']['code'] ?? $code);
        $msg  = (string) ($data['error']['message'] ?? $msg);
    }
    throw new Ajax_Snippets_Mcp_Registry_Error($status, $code, $msg);
}

/**
 * Register with the registry. First call sends the pubkey in body for bootstrap;
 * subsequent calls (heartbeat) omit it — registry just touches last_seen and
 * refreshes metadata.
 */
function ajax_snippets_mcp_registry_self_register($includePubkey = false)
{
    $body = [
        'url'        => home_url('/'),
        'site_name'  => (string) get_bloginfo('name'),
        'wp_version' => (string) get_bloginfo('version'),
        'plugin_ver' => defined('AJAX_SNIPPETS_VERSION') ? AJAX_SNIPPETS_VERSION : null,
    ];
    if ($includePubkey) {
        $body['pubkey'] = (string) get_option(AJAX_SNIPPETS_MCP_OPT_PUBKEY, '');
    }
    $resp = ajax_snippets_mcp_registry_call('POST', '/v1/sites', $body);
    if (is_array($resp) && isset($resp['status'])) {
        update_option(AJAX_SNIPPETS_MCP_OPT_STATUS, (string) $resp['status'], true);
    }
    return $resp;
}

/**
 * Refresh the cached admin pubkey list from the registry. No-op (returns false)
 * if the registry's reported version matches what we already have AND we have
 * a non-empty cache.
 */
function ajax_snippets_mcp_registry_refresh_admin_keys($force = false)
{
    if (!$force) {
        try {
            $probe = ajax_snippets_mcp_registry_call('GET', '/v1/admin-keys/version');
            if (
                is_array($probe)
                && isset($probe['version'])
                && (int) $probe['version'] === (int) get_option(AJAX_SNIPPETS_MCP_OPT_KEYS_VERSION, 0)
                && ajax_snippets_mcp_get_cached_admin_keys() !== []
            ) {
                return false;
            }
        } catch (Ajax_Snippets_Mcp_Registry_Error $e) {
            // Fall through to full fetch — better to refresh than skip on a probe error.
        }
    }
    $resp = ajax_snippets_mcp_registry_call('GET', '/v1/admin-keys');
    if (!is_array($resp) || !isset($resp['keys']) || !is_array($resp['keys'])) {
        return false;
    }
    $clean = [];
    foreach ($resp['keys'] as $k) {
        if (!is_array($k)) {
            continue;
        }
        $fp     = isset($k['fp']) ? (string) $k['fp'] : '';
        $pubkey = isset($k['pubkey']) ? (string) $k['pubkey'] : '';
        if ($fp === '' || $pubkey === '') {
            continue;
        }
        $clean[] = [
            'fp'     => strtolower($fp),
            'pubkey' => $pubkey,
            'label'  => isset($k['label']) ? (string) $k['label'] : null,
        ];
    }
    ajax_snippets_mcp_store_admin_keys($clean, (int) ($resp['version'] ?? 0));
    return true;
}

/**
 * Push a single audit entry. Best-effort: failures are logged locally so we
 * can retry on the next cron tick instead of poisoning the snippet response.
 *
 * @param array<string,mixed> $entry
 */
function ajax_snippets_mcp_registry_push_audit(array $entry)
{
    try {
        ajax_snippets_mcp_registry_call('POST', '/v1/audit', $entry);
        return true;
    } catch (Ajax_Snippets_Mcp_Registry_Error $e) {
        error_log('[ajax-snippets-mcp] audit push failed: ' . $e->getMessage());
        return false;
    }
}
