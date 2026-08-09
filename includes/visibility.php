<?php

defined('ABSPATH') || exit;

/**
 * Visibility gate. By default the plugin hides itself: no admin menu entries and
 * no row on the Plugins screen. It keeps working — AJAX handlers, MCP and cron
 * are untouched — it is just invisible in wp-admin.
 *
 * Define `AJAX_SNIPPETS_REVEAL` in wp-config.php to bring it back:
 *
 *   define('AJAX_SNIPPETS_REVEAL', true);   // visible to everyone who can see it
 *   define('AJAX_SNIPPETS_REVEAL', 5);      // visible only to user ID 5
 *   define('AJAX_SNIPPETS_REVEAL', [1, 5]); // ... or to any of those user IDs
 *   define('AJAX_SNIPPETS_REVEAL', '1,5');  // same, as a CSV string
 *
 * Booleans and IDs are distinct: `true` means "any user", `1` means "user ID 1".
 * Capability checks (`manage_options`) still apply on top of this.
 */
function ajax_snippets_is_revealed()
{
    if (!defined('AJAX_SNIPPETS_REVEAL')) {
        return false;
    }

    $value = constant('AJAX_SNIPPETS_REVEAL');

    if (is_bool($value)) {
        return $value;
    }

    if (is_string($value)) {
        $normalized = strtolower(trim($value));
        if ('' === $normalized || 'false' === $normalized) {
            return false;
        }
        if ('true' === $normalized) {
            return true;
        }
        $value = explode(',', $value);
    }

    $ids = array_filter(array_map('intval', (array) $value));
    if (!$ids) {
        return false;
    }

    $current_user_id = get_current_user_id();

    return $current_user_id > 0 && in_array($current_user_id, $ids, true);
}

// Hide the plugin row on the Plugins screen (single site and network admin alike).
add_filter('all_plugins', function ($plugins) {
    if (ajax_snippets_is_revealed()) {
        return $plugins;
    }
    unset($plugins[plugin_basename(AJAX_SNIPPETS_PLUGIN)]);

    return $plugins;
});

/**
 * Hide a pending update from Dashboard → Updates and from the update counters,
 * otherwise the plugin re-appears there the moment a new release lands.
 *
 * Read-side only, and only inside wp-admin: cron-driven auto-updates and
 * `wp plugin update` run outside the admin, so they still see the update and
 * keep working. Priority 999 so it runs after plugin-update-checker injects
 * its own entry.
 */
add_filter('site_transient_update_plugins', function ($value) {
    if (!is_admin() || wp_doing_cron() || !is_object($value) || ajax_snippets_is_revealed()) {
        return $value;
    }

    $basename = plugin_basename(AJAX_SNIPPETS_PLUGIN);
    if (isset($value->response[$basename])) {
        unset($value->response[$basename]);
    }
    if (isset($value->no_update[$basename])) {
        unset($value->no_update[$basename]);
    }

    return $value;
}, 999);
