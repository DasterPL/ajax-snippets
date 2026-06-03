<?php

defined('ABSPATH') || exit;

/**
 * Ed25519 keypair management for the plugin's MCP identity, plus signed-request
 * verification. PHP's libsodium gives us everything we need; no extra deps.
 *
 * Layout in wp_options:
 *   ajax_snippets_mcp_secret_key   base64(seed, 32B)     ← sensitive, autoload=no
 *   ajax_snippets_mcp_pubkey       base64(pubkey, 32B)
 *   ajax_snippets_mcp_fp           sha256(pubkey) hex
 *   ajax_snippets_mcp_admin_keys   JSON list of admin pubkeys (cached from registry)
 *   ajax_snippets_mcp_admin_keys_version  monotonic integer from registry
 *
 * The secret key is never autoloaded and never exposed via REST or admin UI.
 */

/**
 * Best-effort zeroing of sensitive material. Some hosts ship without the
 * sodium extension (only sodium_compat in userland), where sodium_memzero
 * either is missing or throws SodiumException because it cannot wipe a
 * PHP string in place. Fall back to overwriting + nulling.
 */
function ajax_snippets_mcp_memzero(&$var)
{
    if (extension_loaded('sodium') && function_exists('sodium_memzero')) {
        try { sodium_memzero($var); return; } catch (\Throwable $e) {}
    }
    if (is_string($var)) { $var = str_repeat("\0", strlen($var)); }
    $var = null;
}

function ajax_snippets_mcp_has_keypair()
{
    return (string) get_option(AJAX_SNIPPETS_MCP_OPT_SECRET_KEY, '') !== '';
}

/**
 * Wipe the keypair and all derived state so a fresh keypair can be generated.
 * Does NOT delete auto-register bookkeeping options — callers that need to
 * reset those (tick, manual snippet) do so themselves.
 */
function ajax_snippets_mcp_wipe_keypair()
{
    delete_option(AJAX_SNIPPETS_MCP_OPT_SECRET_KEY);
    delete_option(AJAX_SNIPPETS_MCP_OPT_PUBKEY);
    delete_option(AJAX_SNIPPETS_MCP_OPT_FP);
    delete_option(AJAX_SNIPPETS_MCP_OPT_ADMIN_KEYS);
    delete_option(AJAX_SNIPPETS_MCP_OPT_KEYS_VERSION);
    delete_option(AJAX_SNIPPETS_MCP_OPT_KEYS_UPDATED);
    delete_option(AJAX_SNIPPETS_MCP_OPT_REGISTERED_URL);
    update_option(AJAX_SNIPPETS_MCP_OPT_STATUS, 'unregistered', true);
}

/**
 * Generate and persist a new Ed25519 keypair. Returns [fp, pubkey_b64].
 * Refuses to overwrite an existing pair — rotation goes through a dedicated
 * UI action.
 */
function ajax_snippets_mcp_generate_keypair()
{
    if (ajax_snippets_mcp_has_keypair()) {
        throw new \RuntimeException('Keypair already exists. Rotate via dedicated action.');
    }
    $kp     = sodium_crypto_sign_keypair();
    $sk     = sodium_crypto_sign_secretkey($kp);
    $pk     = sodium_crypto_sign_publickey($kp);
    // The raw 32-byte seed is enough to derive the full keypair; that's what
    // the bridge stores too. We persist the seed only.
    $seed   = substr($sk, 0, SODIUM_CRYPTO_SIGN_SEEDBYTES);
    $fp     = hash('sha256', $pk);

    // autoload=no so the secret isn't loaded on every page request.
    update_option(AJAX_SNIPPETS_MCP_OPT_SECRET_KEY, base64_encode($seed), false);
    update_option(AJAX_SNIPPETS_MCP_OPT_PUBKEY,     base64_encode($pk),   true);
    update_option(AJAX_SNIPPETS_MCP_OPT_FP,         $fp,                  true);
    update_option(AJAX_SNIPPETS_MCP_OPT_STATUS,     'unregistered',       true);

    ajax_snippets_mcp_memzero($sk);
    update_option(AJAX_SNIPPETS_MCP_OPT_REGISTERED_URL, home_url('/'), false);

    return ['fp' => $fp, 'pubkey_b64' => base64_encode($pk)];
}

/**
 * Returns the full 64-byte secret key reconstructed from the seed in wp_options.
 * Caller must ajax_snippets_mcp_memzero() the returned string after use.
 */
function ajax_snippets_mcp_load_secret_key()
{
    $seedB64 = (string) get_option(AJAX_SNIPPETS_MCP_OPT_SECRET_KEY, '');
    if ($seedB64 === '') {
        throw new \RuntimeException('No MCP keypair configured.');
    }
    $seed = base64_decode($seedB64, true);
    if ($seed === false || strlen($seed) !== SODIUM_CRYPTO_SIGN_SEEDBYTES) {
        throw new \RuntimeException('MCP secret key is corrupt.');
    }
    $kp = sodium_crypto_sign_seed_keypair($seed);
    $sk = sodium_crypto_sign_secretkey($kp);
    ajax_snippets_mcp_memzero($seed);
    return $sk;
}

/**
 * Sign the canonical payload that the registry verifier reconstructs.
 *
 * Shared with bridge/src/crypto.ts:signRequest — keep the format in lock-step.
 *
 * @return array{timestamp:int, nonce_b64:string, signature_b64:string}
 */
function ajax_snippets_mcp_sign_request($method, $path, $body)
{
    $timestamp = time();
    $nonce     = random_bytes(16);
    $nonceB64  = base64_encode($nonce);
    $bodyHash  = hash('sha256', $body);
    $payload   = $method . "\n" . $path . "\n" . $timestamp . "\n" . $nonceB64 . "\n" . $bodyHash;

    $sk  = ajax_snippets_mcp_load_secret_key();
    try {
        $sig = sodium_crypto_sign_detached($payload, $sk);
    } finally {
        ajax_snippets_mcp_memzero($sk);
    }

    return [
        'timestamp'     => $timestamp,
        'nonce_b64'     => $nonceB64,
        'signature_b64' => base64_encode($sig),
    ];
}

/**
 * Verify a signed incoming request against the cached admin pubkey list.
 * Returns the matched admin entry (with fp, label) on success, throws on failure.
 *
 * @throws \RuntimeException
 */
function ajax_snippets_mcp_verify_admin_request(WP_REST_Request $request)
{
    $fp        = (string) $request->get_header('x_auth_fp');
    $ts        = (string) $request->get_header('x_auth_timestamp');
    $nonceB64  = (string) $request->get_header('x_auth_nonce');
    $sigB64    = (string) $request->get_header('x_auth_signature');

    if ($fp === '' || $ts === '' || $nonceB64 === '' || $sigB64 === '') {
        throw new \RuntimeException('Missing X-Auth-* header.');
    }
    if (!ctype_xdigit($fp) || strlen($fp) !== 64) {
        throw new \RuntimeException('Bad X-Auth-Fp.');
    }
    if (!ctype_digit($ts)) {
        throw new \RuntimeException('Bad X-Auth-Timestamp.');
    }
    $ts = (int) $ts;
    if (abs(time() - $ts) > AJAX_SNIPPETS_MCP_MAX_SKEW) {
        throw new \RuntimeException('Stale timestamp.');
    }

    $nonce = base64_decode($nonceB64, true);
    if ($nonce === false || strlen($nonce) !== 16) {
        throw new \RuntimeException('Bad nonce.');
    }
    $sig = base64_decode($sigB64, true);
    if ($sig === false || strlen($sig) !== SODIUM_CRYPTO_SIGN_BYTES) {
        throw new \RuntimeException('Bad signature encoding.');
    }

    $admin = ajax_snippets_mcp_find_admin_pubkey($fp);
    if ($admin === null) {
        throw new \RuntimeException('Unknown admin fp. Has the registry approved this device?');
    }

    $pubkey = base64_decode($admin['pubkey'], true);
    if ($pubkey === false || strlen($pubkey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
        throw new \RuntimeException('Cached admin pubkey is corrupt.');
    }

    $bodyHash = hash('sha256', (string) $request->get_body());
    $payload  = $request->get_method() . "\n" . $request->get_route() . "\n"
              . $ts . "\n" . $nonceB64 . "\n" . $bodyHash;

    if (!sodium_crypto_sign_verify_detached($sig, $payload, $pubkey)) {
        throw new \RuntimeException('Signature does not verify.');
    }

    if (!ajax_snippets_mcp_consume_nonce($nonce, $ts + AJAX_SNIPPETS_MCP_NONCE_TTL)) {
        throw new \RuntimeException('Replay detected.');
    }

    return $admin;
}

function ajax_snippets_mcp_find_admin_pubkey($fp)
{
    $keys = ajax_snippets_mcp_get_cached_admin_keys();
    foreach ($keys as $k) {
        if (isset($k['fp']) && strtolower($k['fp']) === strtolower($fp)) {
            return $k;
        }
    }
    return null;
}

/**
 * @return list<array{fp:string,pubkey:string,label:?string}>
 */
function ajax_snippets_mcp_get_cached_admin_keys()
{
    $raw = get_option(AJAX_SNIPPETS_MCP_OPT_ADMIN_KEYS, '[]');
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? array_values($decoded) : [];
}

function ajax_snippets_mcp_store_admin_keys(array $keys, $version)
{
    // autoload=no: these are only read inside the REST permission_callback, so
    // they should not be loaded into memory on every front-end page request.
    update_option(AJAX_SNIPPETS_MCP_OPT_ADMIN_KEYS, wp_json_encode($keys), false);
    update_option(AJAX_SNIPPETS_MCP_OPT_KEYS_VERSION, (int) $version, false);
    update_option(AJAX_SNIPPETS_MCP_OPT_KEYS_UPDATED, time(), false);
}

function ajax_snippets_mcp_consume_nonce($nonce, $expiresAt)
{
    global $wpdb;
    $table = ajax_snippets_mcp_nonce_table();

    if (random_int(0, 99) === 0) {
        $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE expires_at < %d", time()));
    }

    $ok = $wpdb->query(
        $wpdb->prepare(
            "INSERT IGNORE INTO {$table} (nonce, expires_at) VALUES (%s, %d)",
            $nonce,
            $expiresAt
        )
    );
    return $ok === 1;
}
