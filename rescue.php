<?php
/**
 * AJAX Snippets — standalone rescue endpoint.
 *
 * Boots WordPress core WITHOUT loading any other plugin or the active theme, so
 * an operator can still run a signed rescue snippet when the site is fataling
 * because of a third-party plugin/theme — or when the AJAX Snippets plugin
 * itself has been deactivated. It is a physical file, hit directly by URL, and
 * does not depend on the plugin being active (it loads its own files).
 *
 * SECURITY — this file is web-reachable and can run PHP, so treat it as the
 * plugin's most sensitive surface:
 *
 *   1. DISARMED BY DEFAULT. It returns a plain 404 unless
 *      `define('AJAX_SNIPPETS_MCP_RESCUE', true);` is present in wp-config.php.
 *      The mere presence of this file on disk does NOT create a live endpoint;
 *      the operator arms it deliberately (a constant can't be flipped from the
 *      database alone, and it survives plugin deactivation).
 *   2. It performs NO action until the request passes the SAME Ed25519
 *      signed-request check as the REST surface
 *      (ajax_snippets_mcp_verify_signed): a valid signature from a fingerprint
 *      in the cached admin-key list, a fresh timestamp, and an unused nonce.
 *      Every failure path is fail-closed (exit before any snippet runs).
 *
 * SIGNED PATH TOKEN (must match the bridge, bridge/src/site-client.ts):
 *   /ajax-snippets/rescue/execute
 *
 * LIMITATIONS: mu-plugins and drop-ins (object-cache.php, db.php,
 * advanced-cache.php) always load — this cannot rescue a fatal that lives
 * there, nor a broken WordPress core / unreachable database. Server-level rules
 * that block direct .php access under wp-content/plugins will also block this.
 */

// Keep PHP notices/warnings out of the JSON response channel.
@ini_set('display_errors', '0');

define('AJAX_SNIPPETS_RESCUE_DIR', __DIR__);
// Canonical path the bridge signs over for a rescue call. NOT a real URL route —
// just the agreed string fed into the Ed25519 payload on both sides.
define('AJAX_SNIPPETS_RESCUE_SIGN_PATH', '/ajax-snippets/rescue/execute');

// ── 1. Pre-seed hooks so wp-settings loads ONLY this plugin + no site theme ────
// WordPress' plugin.php runs WP_Hook::build_preinitialized_hooks() over any
// $wp_filter that already exists, so filters registered HERE (before wp-load)
// are live by the time wp-settings reads active_plugins and resolves the theme.
//
// option_active_plugins → [ this plugin only ] makes WordPress boot AJAX Snippets
// through its normal bootstrap (constants, vendor autoload, all includes) while
// every OTHER plugin is skipped. The entry is computed from this file's own
// directory, so a renamed plugin folder still resolves. The sitewide filter
// drops network-activated plugins on multisite. The theme filters redirect the
// active theme to the inert bundled rescue-theme so no third-party functions.php
// can fatal the bootstrap. The stored options are untouched — these filters only
// affect reads during THIS request, so the site's real plugin/theme set is
// restored on the next normal request.
function ajax_snippets_rescue_only_self()
{
    return array(basename(AJAX_SNIPPETS_RESCUE_DIR) . '/ajax-snippets.php');
}
function ajax_snippets_rescue_theme_slug()
{
    return 'rescue-theme';
}
function ajax_snippets_rescue_theme_root()
{
    return AJAX_SNIPPETS_RESCUE_DIR;
}

$GLOBALS['wp_filter'] = array(
    'option_active_plugins'               => array(10 => array(array('function' => 'ajax_snippets_rescue_only_self', 'accepted_args' => 1))),
    'site_option_active_sitewide_plugins' => array(10 => array(array('function' => '__return_empty_array', 'accepted_args' => 1))),
    'template'    => array(10 => array(array('function' => 'ajax_snippets_rescue_theme_slug', 'accepted_args' => 1))),
    'stylesheet'  => array(10 => array(array('function' => 'ajax_snippets_rescue_theme_slug', 'accepted_args' => 1))),
    'theme_root'  => array(10 => array(array('function' => 'ajax_snippets_rescue_theme_root', 'accepted_args' => 1))),
);

// ── 2. Locate and boot WordPress core ─────────────────────────────────────────
$ajax_snippets_rescue_wp_load = ajax_snippets_rescue_find_wp_load(__DIR__);
if ($ajax_snippets_rescue_wp_load === null) {
    ajax_snippets_rescue_fail(500, 'bootstrap', 'Could not locate wp-load.php from the rescue endpoint.');
}
if (!defined('WP_USE_THEMES')) {
    define('WP_USE_THEMES', false);
}
require $ajax_snippets_rescue_wp_load;
// From here WordPress core is up, but no regular plugins and no site theme.

// ── 3. Arming gate ────────────────────────────────────────────────────────────
// wp-config.php has been loaded by wp-load, so the arming constant is visible.
// Disarmed → respond like an ordinary missing resource. We can't render the
// site's themed 404 (the active theme is deliberately neutralised above, and the
// arming state is only knowable AFTER boot), so we emit a clean WordPress 404
// status with no distinctive body — indistinguishable from a plain missing-file
// hit under wp-content.
if (!defined('AJAX_SNIPPETS_MCP_RESCUE') || !AJAX_SNIPPETS_MCP_RESCUE) {
    if (!headers_sent()) {
        if (function_exists('status_header')) {
            status_header(404);
        } else {
            http_response_code(404);
        }
        if (function_exists('nocache_headers')) {
            nocache_headers();
        }
    }
    exit;
}

// ── 4. Make sure the plugin's MCP code is loaded ──────────────────────────────
// The active_plugins filter above makes WordPress boot ONLY this plugin through
// its normal bootstrap, so in the common case everything is already loaded. Fall
// back to loading the minimal subset by absolute path if WP did not load us
// (e.g. the plugin folder was renamed, network-activated on multisite, or
// validate_file rejected the path).
if (!function_exists('ajax_snippets_core_execute')) {
    if (!defined('AJAX_SNIPPETS_DIR')) {
        define('AJAX_SNIPPETS_DIR', __DIR__ . '/');
    }
    if (!defined('AJAX_SNIPPETS_VERSION')) {
        define('AJAX_SNIPPETS_VERSION', 'rescue');
    }
    $ajax_snippets_rescue_autoload = __DIR__ . '/vendor/autoload.php';
    if (is_file($ajax_snippets_rescue_autoload)) {
        require_once $ajax_snippets_rescue_autoload;
    }
    require_once __DIR__ . '/includes/mcp/setup.php';        // constants + option names
    require_once __DIR__ . '/includes/mcp/snippet-core.php'; // ajax_snippets_core_execute
    require_once __DIR__ . '/includes/mcp/keys.php';         // ajax_snippets_mcp_verify_signed
    require_once __DIR__ . '/includes/ajax-handlers.php';    // guarded_eval + classify_throwable
}

// The snippet body may reference the plugin's table / CSV helpers. The normal
// bootstrap only loads those on demand inside the request handlers (the REST
// path pulls them in via with_runner), and this endpoint calls the execution
// core directly — so load them now in both the normal and fallback cases.
require_once __DIR__ . '/includes/pretty-table.php';
require_once __DIR__ . '/includes/csv-helper.php';

// ── 5. Verify the signed request BEFORE touching any snippet code ─────────────
$ajax_snippets_rescue_method = isset($_SERVER['REQUEST_METHOD'])
    ? strtoupper((string) $_SERVER['REQUEST_METHOD'])
    : 'GET';
if ($ajax_snippets_rescue_method !== 'POST') {
    ajax_snippets_rescue_fail(405, 'method_not_allowed', 'POST required.');
}

$ajax_snippets_rescue_body = file_get_contents('php://input');
if ($ajax_snippets_rescue_body === false) {
    $ajax_snippets_rescue_body = '';
}

try {
    $ajax_snippets_rescue_admin = ajax_snippets_mcp_verify_signed(
        'POST',
        AJAX_SNIPPETS_RESCUE_SIGN_PATH,
        array(
            'fp'        => ajax_snippets_rescue_header('HTTP_X_AUTH_FP'),
            'timestamp' => ajax_snippets_rescue_header('HTTP_X_AUTH_TIMESTAMP'),
            'nonce'     => ajax_snippets_rescue_header('HTTP_X_AUTH_NONCE'),
            'signature' => ajax_snippets_rescue_header('HTTP_X_AUTH_SIGNATURE'),
        ),
        $ajax_snippets_rescue_body
    );
} catch (\Throwable $e) {
    ajax_snippets_rescue_fail(401, 'unauthorized', $e->getMessage());
}

// ── 6. Run the rescue snippet ─────────────────────────────────────────────────
$ajax_snippets_rescue_payload = json_decode($ajax_snippets_rescue_body, true);
$ajax_snippets_rescue_code = is_array($ajax_snippets_rescue_payload) && isset($ajax_snippets_rescue_payload['code'])
    ? (string) $ajax_snippets_rescue_payload['code']
    : '';
if ($ajax_snippets_rescue_code === '') {
    ajax_snippets_rescue_fail(422, 'empty_code', 'Empty code.');
}

// Match the REST path: run as the configured user so administrator-capability
// checks inside the snippet behave the same.
$ajax_snippets_rescue_run_as = (int) get_option(AJAX_SNIPPETS_MCP_OPT_RUN_AS_USER, 1);
if ($ajax_snippets_rescue_run_as > 0 && function_exists('wp_set_current_user')) {
    wp_set_current_user($ajax_snippets_rescue_run_as);
}

$ajax_snippets_rescue_start = (int) (microtime(true) * 1000);
try {
    $ajax_snippets_rescue_result = ajax_snippets_core_execute($ajax_snippets_rescue_code);
    ajax_snippets_rescue_audit(
        $ajax_snippets_rescue_admin,
        $ajax_snippets_rescue_code,
        'ok',
        null,
        null,
        (int) (microtime(true) * 1000) - $ajax_snippets_rescue_start
    );
    ajax_snippets_rescue_send(200, array(
        'ok'     => true,
        'output' => (string) $ajax_snippets_rescue_result['output'],
        'return' => $ajax_snippets_rescue_result['return'],
        'rescue' => true,
    ));
} catch (\Throwable $th) {
    $ajax_snippets_rescue_classified = ajax_snippets_classify_throwable($th);
    ajax_snippets_rescue_audit(
        $ajax_snippets_rescue_admin,
        $ajax_snippets_rescue_code,
        'error',
        $ajax_snippets_rescue_classified['type'],
        $th->getMessage(),
        (int) (microtime(true) * 1000) - $ajax_snippets_rescue_start
    );
    ajax_snippets_rescue_fail(
        $ajax_snippets_rescue_classified['status'],
        $ajax_snippets_rescue_classified['type'],
        $th->getMessage(),
        array('line' => $th->getLine(), 'file' => $th->getFile())
    );
}

// ── Helpers (pure PHP where possible so they work even before wp-load) ────────

/**
 * Walk up from $dir looking for wp-load.php. Bounded depth so a misplaced file
 * can't loop. Returns the path or null.
 */
function ajax_snippets_rescue_find_wp_load($dir)
{
    $dir = rtrim(str_replace('\\', '/', (string) $dir), '/');
    for ($i = 0; $i < 12 && $dir !== ''; $i++) {
        if (is_file($dir . '/wp-load.php')) {
            return $dir . '/wp-load.php';
        }
        $parent = dirname($dir);
        if ($parent === $dir) {
            break;
        }
        $dir = $parent;
    }
    return null;
}

function ajax_snippets_rescue_header($server_key)
{
    return isset($_SERVER[$server_key]) ? (string) $_SERVER[$server_key] : '';
}

/**
 * Emit a JSON body + status and terminate. Uses WP helpers when available,
 * plain PHP otherwise (this can be called before wp-load on a bootstrap error).
 */
function ajax_snippets_rescue_send($status, array $payload)
{
    if (!headers_sent()) {
        if (function_exists('status_header')) {
            status_header((int) $status);
        } else {
            http_response_code((int) $status);
        }
        header('Content-Type: application/json; charset=utf-8');
    }
    echo function_exists('wp_json_encode') ? wp_json_encode($payload) : json_encode($payload);
    exit;
}

function ajax_snippets_rescue_fail($status, $type, $message, array $extra = array())
{
    ajax_snippets_rescue_send($status, array(
        'ok'    => false,
        'error' => array_merge(
            array('code' => (int) $status, 'type' => (string) $type, 'message' => (string) $message),
            $extra
        ),
        'rescue' => true,
    ));
}

/**
 * Best-effort audit of a rescue run. Never lets an audit failure (missing table,
 * missing dependency) affect the response — the rescue path must stay usable
 * even when the surrounding install is half-broken.
 */
function ajax_snippets_rescue_audit($admin, $code, $status, $error_type, $error_msg, $duration_ms)
{
    if (!function_exists('ajax_snippets_mcp_audit_record')) {
        $audit_file = __DIR__ . '/includes/mcp/audit.php';
        if (is_file($audit_file)) {
            try {
                require_once $audit_file;
            } catch (\Throwable $e) {
                return;
            }
        }
    }
    if (!function_exists('ajax_snippets_mcp_audit_record')) {
        return;
    }
    try {
        $entry = array(
            'caller_fp'   => is_array($admin) && isset($admin['fp']) ? $admin['fp'] : null,
            'caller_kind' => 'admin',
            'action'      => 'rescue.execute',
            'code'        => (string) $code,
            'status'      => $status,
            'duration_ms' => (int) $duration_ms,
        );
        if ($error_type !== null) {
            $entry['error_type'] = (string) $error_type;
        }
        if ($error_msg !== null) {
            $entry['error_msg'] = (string) $error_msg;
        }
        ajax_snippets_mcp_audit_record($entry);
    } catch (\Throwable $e) {
        // swallow — audit is a side effect, not part of the rescue contract
    }
}
