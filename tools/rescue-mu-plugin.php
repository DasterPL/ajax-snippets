<?php
/**
 * Plugin Name: AJAX Snippets — Rescue Loader (MU)
 * Description: TEMPORARY repair tool. Copy this ONE file into wp-content/mu-plugins/
 *   while a site is fataling, then REMOVE it once the site is fixed. For AJAX
 *   Snippets REST requests only, it makes WordPress load just the AJAX Snippets
 *   plugin plus an inert theme — so a broken third-party plugin or theme can no
 *   longer 500 the signed /wp-json/ajax-snippets/v1/* calls you need to run the fix.
 * Version: 1.0
 * Author: Apturn
 *
 * WHY AN MU-PLUGIN: must-use plugins load BEFORE regular plugins and before the
 * theme, which is the only correct point at which to decide *which* plugins and
 * theme WordPress will load. This file scopes that isolation to the AJAX Snippets
 * REST request (matched on REQUEST_URI); every other request on the site is left
 * completely untouched, so dropping it in does not change how the site behaves
 * for normal visitors.
 *
 * SECURITY: this file adds NO authentication of its own. The AJAX Snippets REST
 * route keeps its usual Ed25519 signed-request check — this only changes which
 * code is loaded for that already-authenticated request. Its mere presence is the
 * "arming": install during a repair, delete afterwards.
 *
 * LIMITS: mu-plugins, drop-ins (object-cache.php, db.php, advanced-cache.php),
 * a broken WordPress core or an unreachable database are NOT bypassed.
 *
 * If your plugin folder was renamed from the default, set the constant below in
 * wp-config.php (or edit it here) before use.
 */

defined('ABSPATH') || exit;

if (!defined('AJAX_SNIPPETS_RESCUE_PLUGIN_FILE')) {
    define('AJAX_SNIPPETS_RESCUE_PLUGIN_FILE', 'ajax-snippets/ajax-snippets.php');
}

(function () {
    // 1. Act only on AJAX Snippets REST requests. Mirror the plugin's own route
    //    matching: pretty permalinks (/wp-json/ajax-snippets/v1/...) and the
    //    plain/encoded query form (?rest_route=/ajax-snippets/v1/...).
    $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
    $ns  = 'ajax-snippets/v1/';
    $is_ours = (strpos($uri, '/wp-json/' . $ns) !== false)
        || (strpos($uri, 'rest_route=/' . $ns) !== false)
        || (stripos($uri, 'rest_route=%2Fajax-snippets%2Fv1') !== false);
    if (!$is_ours) {
        return;
    }

    // 2. Load ONLY AJAX Snippets — skip every other regular and network plugin.
    //    The plugin is forced into the active list even if it was deactivated.
    $only = ajax_snippets_rescue_locate_plugin();
    add_filter('option_active_plugins', function () use ($only) {
        return $only === null ? array() : array($only);
    }, PHP_INT_MAX);
    add_filter('site_option_active_sitewide_plugins', '__return_empty_array', PHP_INT_MAX);

    // 3. Force an inert theme so the site's (possibly broken) theme functions.php
    //    stays out of the request. A minimal placeholder is created on demand; if
    //    that is not possible, the real theme is left in place (plugins are still
    //    isolated).
    $theme = ajax_snippets_rescue_ensure_theme();
    if ($theme !== null) {
        add_filter('template', function () use ($theme) {
            return $theme['slug'];
        }, PHP_INT_MAX);
        add_filter('stylesheet', function () use ($theme) {
            return $theme['slug'];
        }, PHP_INT_MAX);
        add_filter('theme_root', function () use ($theme) {
            return $theme['root'];
        }, PHP_INT_MAX);
    }
})();

/**
 * Resolve the AJAX Snippets plugin file (relative to WP_PLUGIN_DIR). Prefers the
 * configured/default path; falls back to scanning for the plugin header in case
 * the folder was renamed. Returns null if not found — in which case the request
 * loads NO plugins, which still keeps a broken third-party plugin out.
 */
function ajax_snippets_rescue_locate_plugin()
{
    if (!defined('WP_PLUGIN_DIR')) {
        return null;
    }
    $default = AJAX_SNIPPETS_RESCUE_PLUGIN_FILE;
    if (is_file(WP_PLUGIN_DIR . '/' . $default)) {
        return $default;
    }
    foreach ((array) glob(WP_PLUGIN_DIR . '/*/ajax-snippets.php') as $file) {
        $head = @file_get_contents($file, false, null, 0, 1024);
        if (is_string($head) && strpos($head, 'Plugin Name: AJAX Snippets') !== false) {
            return basename(dirname($file)) . '/ajax-snippets.php';
        }
    }
    return null;
}

/**
 * Ensure a minimal, inert placeholder theme exists and return its slug + root.
 * It has style.css + index.php but deliberately NO functions.php, so nothing from
 * a theme executes during the request. Returns null if the theme dir cannot be
 * created (caller then leaves the site's real theme in place).
 *
 * @return array{slug:string,root:string}|null
 */
function ajax_snippets_rescue_ensure_theme()
{
    if (!defined('WP_CONTENT_DIR')) {
        return null;
    }
    $slug = 'as-rescue';
    $root = WP_CONTENT_DIR . '/themes';
    $dir  = $root . '/' . $slug;

    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (!is_dir($dir)) {
        return null;
    }
    if (!is_file($dir . '/style.css')) {
        @file_put_contents($dir . '/style.css', "/*\nTheme Name: AJAX Snippets Rescue\nVersion: 1.0\n*/\n");
    }
    if (!is_file($dir . '/index.php')) {
        @file_put_contents($dir . '/index.php', "<?php\n// Inert rescue theme — no functions.php on purpose.\n");
    }
    if (!is_file($dir . '/style.css')) {
        return null;
    }
    return array('slug' => $slug, 'root' => $root);
}
